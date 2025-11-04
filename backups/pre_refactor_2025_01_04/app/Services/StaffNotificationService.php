<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\Business;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * StaffNotificationService - Notificaciones de solicitudes de staff
 *
 * Responsabilidades:
 * 1. Procesar eventos de staff (created, confirmed, rejected, invited, unlinked)
 * 2. Escribir datos en Firebase Realtime Database
 * 3. Actualizar índices de negocio y usuario
 * 4. Enviar notificaciones FCM según el evento
 * 5. Enviar invitaciones por email/WhatsApp
 *
 * V2: Refactorizado - Método gigante de 233 líneas dividido en métodos pequeños
 */
class StaffNotificationService
{
    private FirebaseNotificationService $firebase;
    private TokenManager $tokenManager;

    public function __construct(
        FirebaseNotificationService $firebase,
        TokenManager $tokenManager
    ) {
        $this->firebase = $firebase;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Procesar evento de staff (método principal)
     *
     * @param Staff $staff
     * @param string $eventType created|confirmed|rejected|invited|unlinked
     * @return bool
     */
    public function processStaffEvent(Staff $staff, string $eventType): bool
    {
        try {
            // 1. Escribir en Firebase RTDB
            $this->writeStaffToFirebase($staff, $eventType);

            // 2. Actualizar índices
            $this->updateBusinessStaffIndex($staff, $eventType);
            if ($staff->user_id) {
                $this->updateUserStaffIndex($staff, $eventType);
            }

            // 3. Enviar notificaciones según evento usando match()
            match($eventType) {
                'created' => $this->handleCreatedEvent($staff),
                'confirmed' => $this->handleConfirmedEvent($staff),
                'rejected' => $this->handleRejectedEvent($staff),
                'invited' => $this->handleInvitedEvent($staff),
                'unlinked' => $this->handleUnlinkedEvent($staff),
                default => null
            };

            Log::info('Staff event processed successfully', [
                'staff_id' => $staff->id,
                'event_type' => $eventType,
                'business_id' => $staff->business_id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to process staff event', [
                'staff_id' => $staff->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    // ========================================================================
    // HANDLERS POR EVENTO (En lugar de 1 método de 233 líneas)
    // ========================================================================

    /**
     * Manejar evento CREATED - Nueva solicitud de mozo
     * Notificar a administradores del negocio
     *
     * @param Staff $staff
     * @return void
     */
    private function handleCreatedEvent(Staff $staff): void
    {
        try {
            $tokens = $this->tokenManager->getBusinessAdminTokens($staff->business_id);

            if (empty($tokens)) {
                Log::info('No admin tokens found for business', [
                    'business_id' => $staff->business_id
                ]);
                return;
            }

            $title = 'Nueva solicitud de mozo';
            $body = "{$staff->name} ha solicitado unirse como {$staff->position}";

            $data = [
                'type' => 'staff_request_admin',
                'event_type' => 'created',
                'staff_id' => (string)$staff->id,
                'business_id' => (string)$staff->business_id,
                'user_id' => $staff->user_id ? (string)$staff->user_id : null,
                'status' => $staff->status,
                'position' => $staff->position,
                'timestamp' => (string)now()->timestamp,
                'source' => 'staff_system'
            ];

            $this->firebase->sendBatch($tokens, $title, $body, $data, 'normal');

            // También guardar en BD para admins
            $this->persistDatabaseNotification($staff, 'created', $title, $body);

        } catch (\Exception $e) {
            Log::error('Failed to handle created event', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Manejar evento CONFIRMED - Solicitud aceptada
     * Notificar al staff Y a los administradores
     *
     * @param Staff $staff
     * @return void
     */
    private function handleConfirmedEvent(Staff $staff): void
    {
        try {
            // 1. Notificar al mozo que fue aceptado
            if ($staff->user_id) {
                $tokens = $this->tokenManager->getUserTokens($staff->user_id);

                if (!empty($tokens)) {
                    $title = '¡Solicitud aprobada!';
                    $body = "Tu solicitud para {$staff->position} ha sido aprobada";

                    $data = [
                        'type' => 'staff_request',
                        'event_type' => 'confirmed',
                        'staff_id' => (string)$staff->id,
                        'business_id' => (string)$staff->business_id,
                        'user_id' => (string)$staff->user_id,
                        'status' => $staff->status,
                        'position' => $staff->position,
                        'timestamp' => (string)now()->timestamp,
                        'source' => 'staff_system'
                    ];

                    $this->firebase->sendBatch($tokens, $title, $body, $data, 'normal');
                }

                // Guardar notificación en BD para el staff
                $this->persistDatabaseNotification($staff, 'confirmed', $title ?? '', $body ?? '');
            }

            // 2. Notificar a administradores que la solicitud fue confirmada
            $adminTokens = $this->tokenManager->getBusinessAdminTokens($staff->business_id);

            if (!empty($adminTokens)) {
                $adminTitle = 'Solicitud aceptada';
                $staffName = $staff->name ?: 'Un mozo';
                $adminBody = "$staffName confirmó su ingreso como {$staff->position}";

                $adminData = [
                    'type' => 'staff_request_admin',
                    'event_type' => 'confirmed',
                    'staff_id' => (string)$staff->id,
                    'business_id' => (string)$staff->business_id,
                    'user_id' => $staff->user_id ? (string)$staff->user_id : null,
                    'status' => $staff->status,
                    'position' => $staff->position,
                    'timestamp' => (string)now()->timestamp,
                    'source' => 'staff_system'
                ];

                $this->firebase->sendBatch($adminTokens, $adminTitle, $adminBody, $adminData, 'normal');

                // Guardar notificación en BD para admins
                $this->persistDatabaseNotification($staff, 'confirmed_admin', $adminTitle, $adminBody);
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle confirmed event', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Manejar evento REJECTED - Solicitud rechazada
     * Notificar al mozo
     *
     * @param Staff $staff
     * @return void
     */
    private function handleRejectedEvent(Staff $staff): void
    {
        try {
            if (!$staff->user_id) {
                return;
            }

            $tokens = $this->tokenManager->getUserTokens($staff->user_id);

            if (empty($tokens)) {
                return;
            }

            $title = 'Solicitud rechazada';
            $body = "Tu solicitud para {$staff->position} ha sido rechazada";

            $data = [
                'type' => 'staff_request',
                'event_type' => 'rejected',
                'staff_id' => (string)$staff->id,
                'business_id' => (string)$staff->business_id,
                'user_id' => (string)$staff->user_id,
                'status' => $staff->status,
                'position' => $staff->position,
                'timestamp' => (string)now()->timestamp,
                'source' => 'staff_system'
            ];

            $this->firebase->sendBatch($tokens, $title, $body, $data, 'normal');

            // Guardar notificación en BD
            $this->persistDatabaseNotification($staff, 'rejected', $title, $body);

        } catch (\Exception $e) {
            Log::error('Failed to handle rejected event', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Manejar evento INVITED - Invitación enviada
     * Enviar email y notificación FCM si tiene usuario
     *
     * @param Staff $staff
     * @return void
     */
    private function handleInvitedEvent(Staff $staff): void
    {
        try {
            // 1. Enviar invitación por email
            $this->sendInvitationEmail($staff);

            // 2. Generar mensaje de WhatsApp (solo genera URL, no envía automáticamente)
            $this->sendInvitationWhatsApp($staff);

            // 3. Si tiene usuario registrado, también enviar FCM
            if ($staff->user_id) {
                $tokens = $this->tokenManager->getUserTokens($staff->user_id);

                if (!empty($tokens)) {
                    $title = '¡Invitación recibida!';
                    $body = "Has sido invitado a trabajar en un negocio. Revisa tu email para más detalles.";

                    $data = [
                        'type' => 'staff_invitation',
                        'event_type' => 'invited',
                        'staff_id' => (string)$staff->id,
                        'business_id' => (string)$staff->business_id,
                        'user_id' => (string)$staff->user_id,
                        'status' => $staff->status,
                        'position' => $staff->position,
                        'invitation_token' => $staff->invitation_token,
                        'timestamp' => (string)now()->timestamp,
                        'source' => 'staff_system'
                    ];

                    $this->firebase->sendBatch($tokens, $title, $body, $data, 'normal');
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle invited event', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Manejar evento UNLINKED - Staff desvinculado
     *
     * @param Staff $staff
     * @return void
     */
    private function handleUnlinkedEvent(Staff $staff): void
    {
        try {
            // Eliminar de Firebase
            $this->firebase->deleteFromPath("staff_requests/{$staff->id}");

            // Actualizar índices con estado unlinked
            $this->updateBusinessStaffIndex($staff, 'unlinked', $staff->id);
            if ($staff->user_id) {
                $this->updateUserStaffIndex($staff, 'unlinked', $staff->id);
            }

            Log::info('Staff unlinked and removed from Firebase', [
                'staff_id' => $staff->id,
                'business_id' => $staff->business_id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle unlinked event', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ========================================================================
    // FIREBASE REALTIME DATABASE
    // ========================================================================

    /**
     * Escribir solicitud de staff en Firebase RTDB
     *
     * @param Staff $staff
     * @param string $eventType
     * @return void
     */
    private function writeStaffToFirebase(Staff $staff, string $eventType): void
    {
        $requestData = [
            'id' => (string)$staff->id,
            'business_id' => (string)$staff->business_id,
            'user_id' => $staff->user_id ? (string)$staff->user_id : null,
            'name' => $staff->name,
            'email' => $staff->email,
            'phone' => $staff->phone,
            'status' => $staff->status,
            'position' => $staff->position,
            'salary' => $staff->salary,
            'hire_date' => $staff->hire_date?->timestamp * 1000,
            'birth_date' => $staff->birth_date?->timestamp * 1000,
            'height' => $staff->height,
            'weight' => $staff->weight,
            'gender' => $staff->gender,
            'experience_years' => $staff->experience_years,
            'employment_type' => $staff->employment_type,
            'current_schedule' => $staff->current_schedule,
            'invitation_token' => $staff->invitation_token,
            'invitation_sent_at' => $staff->invitation_sent_at?->timestamp * 1000,
            'created_at' => $staff->created_at->timestamp * 1000,
            'updated_at' => $staff->updated_at->timestamp * 1000,
            'last_updated' => now()->timestamp * 1000,
            'event_type' => $eventType,
            'notes' => $staff->notes,
        ];

        // Si el evento es confirmed o rejected, eliminar de solicitudes
        if (in_array($eventType, ['confirmed', 'rejected'])) {
            $this->firebase->deleteFromPath("staff_requests/{$staff->id}");
        } else {
            $this->firebase->writeToPath("staff_requests/{$staff->id}", $requestData);
        }
    }

    /**
     * Actualizar índice de staff del negocio
     *
     * @param Staff $staff
     * @param string $eventType
     * @param int|null $excludeStaffId
     * @return void
     */
    private function updateBusinessStaffIndex(Staff $staff, string $eventType, ?int $excludeStaffId = null): void
    {
        try {
            $baseQuery = \App\Models\Staff::where('business_id', $staff->business_id);
            if ($excludeStaffId) {
                $baseQuery->where('id', '!=', $excludeStaffId);
            }

            $allRequests = (clone $baseQuery)->pluck('id')->map(fn($id) => (string)$id)->toArray();

            $statusCounts = (clone $baseQuery)
                ->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status')
                ->toArray();

            $businessData = [
                'all_requests' => array_values($allRequests),
                'stats' => [
                    'total_requests' => count($allRequests),
                    'pending_count' => $statusCounts['pending'] ?? 0,
                    'confirmed_count' => $statusCounts['confirmed'] ?? 0,
                    'rejected_count' => $statusCounts['rejected'] ?? 0,
                    'invited_count' => $statusCounts['invited'] ?? 0,
                    'unlinked_count' => $statusCounts['unlinked'] ?? 0,
                    'last_update' => now()->timestamp * 1000
                ],
                'recent_activity' => [
                    'last_request_id' => (string)$staff->id,
                    'last_request_status' => $staff->status,
                    'event_type' => $eventType,
                    'last_update' => now()->timestamp * 1000
                ]
            ];

            $this->firebase->writeToPath("businesses_staff/business_{$staff->business_id}", $businessData);

        } catch (\Exception $e) {
            Log::warning('Failed to update business staff index', [
                'business_id' => $staff->business_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar índice de staff del usuario
     *
     * @param Staff $staff
     * @param string $eventType
     * @param int|null $excludeStaffId
     * @return void
     */
    private function updateUserStaffIndex(Staff $staff, string $eventType, ?int $excludeStaffId = null): void
    {
        if (!$staff->user_id) {
            return;
        }

        try {
            $baseQuery = \App\Models\Staff::where('user_id', $staff->user_id);
            if ($excludeStaffId) {
                $baseQuery->where('id', '!=', $excludeStaffId);
            }

            $userRequests = (clone $baseQuery)->pluck('id')->map(fn($id) => (string)$id)->toArray();

            $userData = [
                'user_requests' => array_values($userRequests),
                'stats' => [
                    'total_requests' => count($userRequests),
                    'last_update' => now()->timestamp * 1000
                ],
                'current_request' => [
                    'id' => (string)$staff->id,
                    'business_id' => (string)$staff->business_id,
                    'status' => $staff->status,
                    'event_type' => $eventType,
                    'position' => $staff->position,
                    'last_update' => now()->timestamp * 1000
                ]
            ];

            $this->firebase->writeToPath("users_staff/{$staff->user_id}", $userData);

        } catch (\Exception $e) {
            Log::warning('Failed to update user staff index', [
                'user_id' => $staff->user_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ========================================================================
    // COMUNICACIONES EXTERNAS (Email, WhatsApp)
    // ========================================================================

    /**
     * Enviar invitación por email
     *
     * @param Staff $staff
     * @return void
     */
    private function sendInvitationEmail(Staff $staff): void
    {
        try {
            $business = Business::find($staff->business_id);

            if (!$business) {
                Log::error('Business not found for staff invitation', [
                    'staff_id' => $staff->id,
                    'business_id' => $staff->business_id
                ]);
                return;
            }

            $invitationUrl = config('app.url') . "/staff/join/{$staff->invitation_token}";

            Mail::to($staff->email)->send(
                new \App\Mail\StaffInvitationMail($staff, $business, $invitationUrl)
            );

            Log::info('Staff invitation email sent successfully', [
                'staff_id' => $staff->id,
                'email' => $staff->email,
                'business_name' => $business->name
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send staff invitation email', [
                'staff_id' => $staff->id,
                'email' => $staff->email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generar mensaje de invitación por WhatsApp
     * Solo genera la URL, no envía automáticamente
     *
     * @param Staff $staff
     * @return void
     */
    private function sendInvitationWhatsApp(Staff $staff): void
    {
        try {
            $business = Business::find($staff->business_id);

            if (!$business) {
                return;
            }

            $phone = preg_replace('/[^0-9]/', '', $staff->phone);

            if (strlen($phone) < 10) {
                Log::warning('Invalid phone number for WhatsApp invitation', [
                    'staff_id' => $staff->id,
                    'phone' => $staff->phone
                ]);
                return;
            }

            $invitationUrl = config('app.url') . "/staff/join/{$staff->invitation_token}";

            $message = "🍽️ ¡Hola {$staff->name}!\n\n";
            $message .= "Has sido invitado/a a trabajar en *{$business->name}* como *{$staff->position}*.\n\n";
            $message .= "📋 *Detalles:*\n";
            $message .= "• Negocio: {$business->name}\n";
            $message .= "• Posición: {$staff->position}\n";
            $message .= "• Dirección: {$business->address}\n\n";
            $message .= "✅ *Acepta tu invitación aquí:*\n{$invitationUrl}\n\n";
            $message .= "⏰ *Importante:* Esta invitación expira en 24 horas.\n\n";
            $message .= "Si tienes preguntas, contáctanos al {$business->phone}";

            $whatsappUrl = "https://wa.me/{$phone}?text=" . urlencode($message);

            Log::info('WhatsApp invitation URL generated', [
                'staff_id' => $staff->id,
                'phone' => $phone,
                'whatsapp_url' => $whatsappUrl
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate WhatsApp invitation', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Persistir notificación en base de datos
     *
     * @param Staff $staff
     * @param string $eventType
     * @param string $title
     * @param string $body
     * @return void
     */
    private function persistDatabaseNotification(Staff $staff, string $eventType, string $title, string $body): void
    {
        try {
            $notificationKey = 'user_staff_' . $staff->id;

            $payload = [
                'type' => 'staff_request',
                'event_type' => $eventType,
                'staff_id' => (string)$staff->id,
                'business_id' => (string)$staff->business_id,
                'user_id' => $staff->user_id ? (string)$staff->user_id : null,
                'status' => $staff->status,
                'position' => $staff->position,
                'notification_key' => $notificationKey,
                'key' => $notificationKey,
                'title' => $title,
                'body' => $body,
                'source' => 'staff_system',
            ];

            // Notificar a admins del negocio
            $admins = Business::find($staff->business_id)?->admins()
                ->where('business_admins.is_active', true)
                ->get();

            if ($admins) {
                foreach ($admins as $admin) {
                    // No notificar al staff de su propia solicitud
                    if ($admin->id !== $staff->user_id) {
                        $admin->notify(new \App\Notifications\GenericDataNotification($payload));
                    }
                }
            }

            // Si es evento de confirmación/rechazo, notificar también al staff
            if (in_array($eventType, ['confirmed', 'rejected']) && $staff->user_id) {
                $user = \App\Models\User::find($staff->user_id);
                if ($user) {
                    $user->notify(new \App\Notifications\GenericDataNotification($payload));
                }
            }

        } catch (\Exception $e) {
            Log::warning('Failed to persist database notification', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
