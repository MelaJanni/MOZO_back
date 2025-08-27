<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class StaffNotificationService
{
    private $baseUrl = 'https://mozoqr-7d32c-default-rtdb.firebaseio.com';
    
    /**
     * 🔥 CREAR/ACTUALIZAR SOLICITUD DE STAFF EN FIREBASE
     */
    public function writeStaffRequest(Staff $staff, string $eventType = 'created'): bool
    {
        try {
            // Datos unificados de la solicitud de staff
            $requestData = [
                // Información básica
                'id' => (string)$staff->id,
                'business_id' => (string)$staff->business_id,
                'user_id' => $staff->user_id ? (string)$staff->user_id : null,
                'name' => $staff->name,
                'email' => $staff->email,
                'phone' => $staff->phone,
                
                // Estado y posición
                'status' => $staff->status,
                'position' => $staff->position,
                'salary' => $staff->salary,
                'hire_date' => $staff->hire_date?->timestamp * 1000,
                
                // Información personal
                'birth_date' => $staff->birth_date?->timestamp * 1000,
                'height' => $staff->height,
                'weight' => $staff->weight,
                'gender' => $staff->gender,
                'experience_years' => $staff->experience_years,
                'employment_type' => $staff->employment_type,
                'current_schedule' => $staff->current_schedule,
                
                // Sistema de invitaciones
                'invitation_token' => $staff->invitation_token,
                'invitation_sent_at' => $staff->invitation_sent_at?->timestamp * 1000,
                
                // Timestamps
                'created_at' => $staff->created_at->timestamp * 1000,
                'updated_at' => $staff->updated_at->timestamp * 1000,
                'last_updated' => now()->timestamp * 1000,
                'event_type' => $eventType,
                
                // Notas adicionales
                'notes' => $staff->notes,
            ];

            // Escrituras según evento
            $promises = [];
            if (in_array($eventType, ['confirmed', 'rejected'])) {
                // Al confirmar o rechazar, eliminar del listado de solicitudes
                $promises[] = $this->deleteFromPath("staff_requests/{$staff->id}");
            } else {
                // En eventos 'created' o 'invited' mantenemos el nodo activo
                $promises[] = $this->writeToPath("staff_requests/{$staff->id}", $requestData);
            }
            // Actualizar índices
            $promises[] = $this->updateBusinessStaffIndex($staff);
            $promises[] = $this->updateUserStaffIndex($staff);

            $this->executeParallel($promises);
            
            Log::info("Staff request written to Firebase", [
                'staff_id' => $staff->id,
                'event_type' => $eventType,
                'status' => $staff->status
            ]);

            // Enviar notificación FCM solo para eventos importantes
            if (in_array($eventType, ['created', 'confirmed', 'rejected'])) {
                $this->sendStaffNotification($staff, $eventType);
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to write staff request to Firebase", [
                'staff_id' => $staff->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 🏢 ACTUALIZAR ÍNDICE DE STAFF DEL NEGOCIO
     */
    private function updateBusinessStaffIndex(Staff $staff): string
    {
        try {
            // Obtener todas las solicitudes del negocio
            $allRequests = \App\Models\Staff::where('business_id', $staff->business_id)
                ->pluck('id')
                ->map(fn($id) => (string)$id)
                ->toArray();
            
            // Contar por estado
            $statusCounts = \App\Models\Staff::where('business_id', $staff->business_id)
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
                    'last_update' => now()->timestamp * 1000
                ],
                'recent_activity' => [
                    'last_request_id' => (string)$staff->id,
                    'last_request_status' => $staff->status,
                    'last_update' => now()->timestamp * 1000
                ]
            ];

            return $this->writeToPath("businesses_staff/{$staff->business_id}", $businessData);

        } catch (\Exception $e) {
            Log::warning("Failed to update business staff index", [
                'business_id' => $staff->business_id,
                'error' => $e->getMessage()
            ]);
            return "error";
        }
    }

    /**
     * 👤 ACTUALIZAR ÍNDICE DE STAFF DEL USUARIO
     */
    private function updateUserStaffIndex(Staff $staff): string
    {
        if (!$staff->user_id) {
            return "skipped"; // No hay usuario asociado
        }

        try {
            // Obtener todas las solicitudes del usuario
            $userRequests = \App\Models\Staff::where('user_id', $staff->user_id)
                ->pluck('id')
                ->map(fn($id) => (string)$id)
                ->toArray();

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
                    'position' => $staff->position
                ]
            ];

            return $this->writeToPath("users_staff/{$staff->user_id}", $userData);

        } catch (\Exception $e) {
            Log::warning("Failed to update user staff index", [
                'user_id' => $staff->user_id,
                'error' => $e->getMessage()
            ]);
            return "error";
        }
    }

    /**
     * 📡 ENVIAR NOTIFICACIÓN FCM PARA EVENTOS DE STAFF
     */
    private function sendStaffNotification(Staff $staff, string $eventType): void
    {
        try {
            $firebaseService = app(\App\Services\FirebaseService::class);
            $dbNotify = function(array $data) use ($staff) {
                // Persist a database notification for involved users with a stable key
                $notificationKey = $data['notification_key'] ?? ($data['key'] ?? null);
                if (!$notificationKey) {
                    $notificationKey = 'user_staff_' . $staff->id;
                }
                $payload = [
                    'type' => $data['type'] ?? 'staff_request',
                    'event_type' => $data['event_type'] ?? 'info',
                    'staff_id' => (string)$staff->id,
                    'business_id' => (string)$staff->business_id,
                    'user_id' => $staff->user_id ? (string)$staff->user_id : null,
                    'status' => $staff->status,
                    'position' => $staff->position,
                    'notification_key' => $notificationKey,
                    'key' => $notificationKey,
                    'title' => $data['title'] ?? null,
                    'body' => $data['body'] ?? null,
                    'source' => 'staff_system',
                ];
                try {
                    // Notify the staff user if present
                    if ($staff->user_id && ($user = \App\Models\User::find($staff->user_id))) {
                        $user->notify(new \App\Notifications\GenericDataNotification($payload));
                    }
                    // Notify business admins
                    $admins = \App\Models\Business::find($staff->business_id)?->admins()->where('business_admins.is_active', true)->get();
                    if ($admins) {
                        foreach ($admins as $admin) {
                            $admin->notify(new \App\Notifications\GenericDataNotification($payload));
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Failed to persist DB notification for staff event', [
                        'staff_id' => $staff->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            };

            // Determinar a quién enviar la notificación
            $tokens = [];
            $title = '';
            $body = '';

            switch ($eventType) {
                case 'created':
                    // Notificar a administradores del negocio
                    $tokens = $this->getBusinessAdminTokens($staff->business_id);
                    $title = 'Nueva solicitud de mozo';
                    $body = "{$staff->name} ha solicitado unirse como {$staff->position}";
                    $dbNotify([
                        'type' => 'staff_request_admin',
                        'event_type' => $eventType,
                        'title' => $title,
                        'body' => $body,
                        'notification_key' => 'user_staff_' . $staff->id,
                    ]);
                    break;

                case 'confirmed':
                    // Notificar al mozo si tiene usuario
                    if ($staff->user_id) {
                        $tokens = $this->getUserTokens($staff->user_id);
                        $title = '¡Solicitud aprobada!';
                        $body = "Tu solicitud para {$staff->position} ha sido aprobada";
                        $dbNotify([
                            'type' => 'staff_request',
                            'event_type' => $eventType,
                            'title' => $title,
                            'body' => $body,
                            'notification_key' => 'user_staff_' . $staff->id,
                        ]);
                        if (!empty($tokens)) {
                            $data = [
                                'type' => 'staff_request',
                                'event_type' => $eventType,
                                'staff_id' => (string)$staff->id,
                                'business_id' => (string)$staff->business_id,
                                'user_id' => (string)$staff->user_id,
                                'status' => $staff->status,
                                'position' => $staff->position,
                                'timestamp' => (string) now()->timestamp,
                                'source' => 'staff_system'
                            ];
                            $firebaseService->sendUnifiedGenericToTokens(
                                $tokens,
                                $title,
                                $body,
                                $data
                            );
                        }
                    }
                    // Además, notificar a administradores del negocio que la solicitud fue aceptada/confirmada
                    $adminTokens = $this->getBusinessAdminTokens($staff->business_id);
                    if (!empty($adminTokens)) {
                        $adminTitle = 'Solicitud aceptada';
                        $staffName = $staff->name ?: 'Un mozo';
                        $adminBody = "$staffName confirmó su ingreso como {$staff->position}";
                        $dbNotify([
                            'type' => 'staff_request_admin',
                            'event_type' => $eventType,
                            'title' => $adminTitle,
                            'body' => $adminBody,
                            'notification_key' => 'user_staff_' . $staff->id,
                        ]);
                        $adminData = [
                            'type' => 'staff_request_admin',
                            'event_type' => $eventType,
                            'staff_id' => (string)$staff->id,
                            'business_id' => (string)$staff->business_id,
                            'user_id' => $staff->user_id ? (string)$staff->user_id : null,
                            'status' => $staff->status,
                            'position' => $staff->position,
                            'timestamp' => (string) now()->timestamp,
                            'source' => 'staff_system'
                        ];
                        $firebaseService->sendUnifiedGenericToTokens(
                            $adminTokens,
                            $adminTitle,
                            $adminBody,
                            $adminData
                        );
                    }
                    return; // Ya enviamos ambos tipos de notificación

                case 'rejected':
                    // Notificar al mozo si tiene usuario
                    if ($staff->user_id) {
                        $tokens = $this->getUserTokens($staff->user_id);
                        $title = 'Solicitud rechazada';
                        $body = "Tu solicitud para {$staff->position} ha sido rechazada";
                        $dbNotify([
                            'type' => 'staff_request',
                            'event_type' => $eventType,
                            'title' => $title,
                            'body' => $body,
                            'notification_key' => 'user_staff_' . $staff->id,
                        ]);
                    }
                    break;

                case 'invited':
                    // Notificar al mozo por email/sms Y también FCM si tiene usuario registrado
                    $this->sendInvitationNotification($staff);
                    
                    // Si tiene usuario registrado, también enviar FCM
                    if ($staff->user_id) {
                        $tokens = $this->getUserTokens($staff->user_id);
                        $title = '¡Invitación recibida!';
                        $body = "Has sido invitado a trabajar en un negocio. Revisa tu email para más detalles.";
                        $dbNotify([
                            'type' => 'staff_invitation',
                            'event_type' => $eventType,
                            'title' => $title,
                            'body' => $body,
                            'notification_key' => 'user_staff_' . $staff->id,
                        ]);
                        
                        if (!empty($tokens)) {
                            $data = [
                                'type' => 'staff_invitation',
                                'event_type' => $eventType,
                                'staff_id' => (string)$staff->id,
                                'business_id' => (string)$staff->business_id,
                                'user_id' => (string)$staff->user_id,
                                'status' => $staff->status,
                                'position' => $staff->position,
                                'invitation_token' => $staff->invitation_token,
                                'timestamp' => (string) now()->timestamp,
                                'source' => 'staff_system'
                            ];
                                $firebaseService->sendUnifiedGenericToTokens(
                                    $tokens,
                                    $title,
                                    $body,
                                    $data
                                );
                        }
                    }
                    return;
            }

            if (empty($tokens)) {
                Log::info('Staff notification skipped (no tokens)', [
                    'staff_id' => $staff->id,
                    'event_type' => $eventType
                ]);
                return;
            }

            $data = [
                'type' => 'staff_request',
                'event_type' => $eventType,
                'staff_id' => (string)$staff->id,
                'business_id' => (string)$staff->business_id,
                'user_id' => $staff->user_id ? (string)$staff->user_id : null,
                'status' => $staff->status,
                'position' => $staff->position,
                'timestamp' => (string) now()->timestamp,
                'source' => 'staff_system'
            ];

            $firebaseService->sendUnifiedGenericToTokens(
                $tokens,
                $title,
                $body,
                $data
            );

            Log::info('Staff notification sent', [
                'staff_id' => $staff->id,
                'event_type' => $eventType,
                'tokens' => count($tokens)
            ]);

        } catch (\Exception $e) {
            Log::warning('Failed to send staff notification', [
                'staff_id' => $staff->id,
                'event_type' => $eventType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 📧 ENVIAR NOTIFICACIÓN DE INVITACIÓN (email/sms)
     */
    private function sendInvitationNotification(Staff $staff): void
    {
        try {
            $business = \App\Models\Business::find($staff->business_id);
            
            if (!$business) {
                Log::error('Business not found for staff invitation', [
                    'staff_id' => $staff->id,
                    'business_id' => $staff->business_id
                ]);
                return;
            }

            // Generar URL de invitación
            $invitationUrl = config('app.url') . "/staff/join/{$staff->invitation_token}";
            
            // Enviar email de invitación
            try {
                \Illuminate\Support\Facades\Mail::to($staff->email)
                    ->send(new \App\Mail\StaffInvitationMail($staff, $business, $invitationUrl));
                
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

            // Enviar WhatsApp/SMS si está configurado
            $this->sendInvitationWhatsApp($staff, $business, $invitationUrl);
            
        } catch (\Exception $e) {
            Log::error('Failed to send invitation notification', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 📱 ENVIAR NOTIFICACIÓN DE INVITACIÓN POR WHATSAPP
     */
    private function sendInvitationWhatsApp(Staff $staff, $business, string $invitationUrl): void
    {
        try {
            // Limpiar número de teléfono
            $phone = preg_replace('/[^0-9]/', '', $staff->phone);
            
            if (strlen($phone) < 10) {
                Log::warning('Invalid phone number for WhatsApp invitation', [
                    'staff_id' => $staff->id,
                    'phone' => $staff->phone
                ]);
                return;
            }

            // Mensaje de WhatsApp
            $message = "🍽️ ¡Hola {$staff->name}!\n\n";
            $message .= "Has sido invitado/a a trabajar en *{$business->name}* como *{$staff->position}*.\n\n";
            $message .= "📋 *Detalles:*\n";
            $message .= "• Negocio: {$business->name}\n";
            $message .= "• Posición: {$staff->position}\n";
            $message .= "• Dirección: {$business->address}\n\n";
            $message .= "✅ *Acepta tu invitación aquí:*\n{$invitationUrl}\n\n";
            $message .= "⏰ *Importante:* Esta invitación expira en 24 horas.\n\n";
            $message .= "Si tienes preguntas, contáctanos al {$business->phone}";

            // Generar URL de WhatsApp
            $whatsappUrl = "https://wa.me/{$phone}?text=" . urlencode($message);
            
            Log::info('WhatsApp invitation URL generated', [
                'staff_id' => $staff->id,
                'phone' => $phone,
                'whatsapp_url' => $whatsappUrl,
                'message_length' => strlen($message)
            ]);

            // En un entorno real, aquí podrías integrar con una API de WhatsApp Business
            // Por ahora solo guardamos la URL para que el admin pueda enviarla manualmente
            
        } catch (\Exception $e) {
            Log::error('Failed to generate WhatsApp invitation', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 🔍 OBTENER TOKENS DE ADMINISTRADORES DEL NEGOCIO
     */
    private function getBusinessAdminTokens(int $businessId): array
    {
        try {
            $tokens = DeviceToken::whereHas('user', function($query) use ($businessId) {
                $query->where('role', 'admin')
                      ->whereHas('businesses', function($b) use ($businessId) {
                          $b->where('business_id', $businessId);
                      });
            })
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

            return $tokens;
        } catch (\Exception $e) {
            Log::error('Failed to get business admin tokens', [
                'business_id' => $businessId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * 🔍 OBTENER TOKENS DE UN USUARIO ESPECÍFICO
     */
    private function getUserTokens(int $userId): array
    {
        try {
            $tokens = DeviceToken::where('user_id', $userId)
                ->pluck('token')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            return $tokens;
        } catch (\Exception $e) {
            Log::error('Failed to get user tokens', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * 🚀 ESCRITURA A FIREBASE
     */
    private function writeToPath(string $path, array $data): string
    {
        try {
            $url = "{$this->baseUrl}/{$path}.json";
            $response = Http::timeout(3)->put($url, $data);
            
            return $response->successful() ? "success" : "failed";

        } catch (\Exception $e) {
            Log::warning("Firebase write failed", [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return "error";
        }
    }

    /**
     * ⚡ EJECUTAR OPERACIONES EN PARALELO
     */
    private function executeParallel(array $promises): array
    {
        // Por simplicidad, ejecutamos secuencialmente 
        return $promises;
    }

    /**
     * 🗑️ ELIMINAR SOLICITUD DE STAFF COMPLETADA
     */
    public function removeStaffRequest(Staff $staff): bool
    {
        try {
            $promises = [
                $this->deleteFromPath("staff_requests/{$staff->id}"),
                $this->updateBusinessStaffIndex($staff),
                $this->updateUserStaffIndex($staff)
            ];

            $this->executeParallel($promises);
            
            Log::info("Staff request removed from Firebase", ['staff_id' => $staff->id]);
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to remove staff request from Firebase", [
                'staff_id' => $staff->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 🗑️ ELIMINAR DE FIREBASE
     */
    private function deleteFromPath(string $path): string
    {
        try {
            $url = "{$this->baseUrl}/{$path}.json";
            $response = Http::timeout(3)->delete($url);
            
            return $response->successful() ? "success" : "failed";

        } catch (\Exception $e) {
            Log::warning("Firebase delete failed", [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return "error";
        }
    }

    /**
     * 🧪 TEST DE CONECTIVIDAD
     */
    public function testConnection(): array
    {
        try {
            $testData = [
                'test' => true,
                'timestamp' => now()->timestamp,
                'message' => 'Staff notification system test'
            ];

            $result = $this->writeToPath('test/staff_notifications', $testData);

            return [
                'status' => $result === 'success' ? 'connected' : 'failed',
                'timestamp' => now()->toISOString(),
                'service' => 'staff_notifications'
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }
}