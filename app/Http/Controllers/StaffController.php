<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Business;
use App\Models\User;
use App\Services\StaffNotificationService;
use App\Http\Requests\StoreStaffRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    protected $staffNotificationService;

    public function __construct(StaffNotificationService $staffNotificationService)
    {
        $this->staffNotificationService = $staffNotificationService;
    }

    /**
     * 📋 LISTAR SOLICITUDES DE STAFF PARA UN NEGOCIO
     */
    public function index(Request $request)
    {
        $businessId = $request->get('business_id');
        
        if (!$businessId) {
            return response()->json([
                'success' => false,
                'message' => 'business_id is required'
            ], 400);
        }

        try {
            $staffRequests = Staff::where('business_id', $businessId)
                ->with('user')
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $staffRequests->map(function($staff) {
                    return [
                        'id' => $staff->id,
                        'name' => $staff->name,
                        'email' => $staff->email,
                        'phone' => $staff->phone,
                        'position' => $staff->position,
                        'status' => $staff->status,
                        'experience_years' => $staff->experience_years,
                        'employment_type' => $staff->employment_type,
                        'salary' => $staff->salary,
                        'hire_date' => $staff->hire_date,
                        'created_at' => $staff->created_at,
                        'user' => $staff->user ? [
                            'id' => $staff->user->id,
                            'name' => $staff->user->name,
                            'email' => $staff->user->email
                        ] : null
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch staff requests', [
                'business_id' => $businessId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch staff requests'
            ], 500);
        }
    }

    /**
     * 📝 CREAR NUEVA SOLICITUD DE STAFF
     */
    public function store(StoreStaffRequest $request)
    {
        try {
            $staff = Staff::create([
                'business_id' => $request->business_id,
                'user_id' => $request->user_id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'position' => $request->position ?? 'Mozo',
                'status' => 'pending',
                'experience_years' => $request->experience_years,
                'employment_type' => $request->employment_type,
                'current_schedule' => $request->current_schedule,
                'birth_date' => $request->birth_date,
                'height' => $request->height,
                'weight' => $request->weight,
                'gender' => $request->gender,
            ]);

            // Enviar notificación Firebase
            $this->staffNotificationService->writeStaffRequest($staff, 'created');

            Log::info('Staff request created successfully', [
                'staff_id' => $staff->id,
                'business_id' => $request->business_id,
                'user_id' => $request->user_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de staff creada exitosamente',
                'data' => $staff
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create staff request', [
                'business_id' => $request->business_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la solicitud de staff'
            ], 500);
        }
    }

    /**
     * 👁️ VER DETALLES DE UNA SOLICITUD DE STAFF
     */
    public function show($id)
    {
        try {
            $staff = Staff::with(['user', 'business'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'email' => $staff->email,
                    'phone' => $staff->phone,
                    'position' => $staff->position,
                    'status' => $staff->status,
                    'salary' => $staff->salary,
                    'hire_date' => $staff->hire_date,
                    'birth_date' => $staff->birth_date,
                    'height' => $staff->height,
                    'weight' => $staff->weight,
                    'gender' => $staff->gender,
                    'experience_years' => $staff->experience_years,
                    'employment_type' => $staff->employment_type,
                    'current_schedule' => $staff->current_schedule,
                    'notes' => $staff->notes,
                    'invitation_token' => $staff->invitation_token,
                    'invitation_sent_at' => $staff->invitation_sent_at,
                    'created_at' => $staff->created_at,
                    'updated_at' => $staff->updated_at,
                    'user' => $staff->user,
                    'business' => $staff->business
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitud de staff no encontrada'
            ], 404);
        }
    }

    /**
     * ✅ APROBAR SOLICITUD DE STAFF
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'salary' => 'nullable|numeric|min:0',
            'hire_date' => 'nullable|date',
            'notes' => 'nullable|string'
        ]);

        try {
            $staff = Staff::findOrFail($id);
            
            $staff->update([
                'status' => 'confirmed',
                'salary' => $request->salary,
                'hire_date' => $request->hire_date ?? now(),
                'notes' => $request->notes
            ]);

            // Enviar notificación Firebase
            $this->staffNotificationService->writeStaffRequest($staff, 'confirmed');

            Log::info('Staff request approved', [
                'staff_id' => $staff->id,
                'business_id' => $staff->business_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de staff aprobada exitosamente',
                'data' => $staff
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to approve staff request', [
                'staff_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la solicitud de staff'
            ], 500);
        }
    }

    /**
     * ❌ RECHAZAR SOLICITUD DE STAFF
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string'
        ]);

        try {
            $staff = Staff::findOrFail($id);
            
            $staff->update([
                'status' => 'rejected',
                'notes' => $request->notes
            ]);

            // Enviar notificación Firebase
            $this->staffNotificationService->writeStaffRequest($staff, 'rejected');

            Log::info('Staff request rejected', [
                'staff_id' => $staff->id,
                'business_id' => $staff->business_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de staff rechazada',
                'data' => $staff
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reject staff request', [
                'staff_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la solicitud de staff'
            ], 500);
        }
    }

    /**
     * 📧 ENVIAR INVITACIÓN POR CÓDIGO
     */
    public function sendInvitation(Request $request, $id)
    {
        try {
            $staff = Staff::findOrFail($id);
            
            // Generar token de invitación único
            $invitationToken = Str::random(32);
            
            $staff->update([
                'status' => 'invited',
                'invitation_token' => $invitationToken,
                'invitation_sent_at' => now()
            ]);

            // Enviar notificación Firebase
            $this->staffNotificationService->writeStaffRequest($staff, 'invited');

            Log::info('Staff invitation sent', [
                'staff_id' => $staff->id,
                'business_id' => $staff->business_id,
                'invitation_token' => $invitationToken
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invitación enviada exitosamente',
                'data' => [
                    'invitation_token' => $invitationToken,
                    'invitation_url' => url("/staff/join/{$invitationToken}"),
                    'email_sent' => true,
                    'whatsapp_url' => $this->generateWhatsAppInvitation($staff)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send staff invitation', [
                'staff_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la invitación'
            ], 500);
        }
    }

    /**
     * 🔗 UNIRSE A NEGOCIO CON TOKEN DE INVITACIÓN
     */
    public function joinWithToken(Request $request, $token)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        try {
            $staff = Staff::where('invitation_token', $token)
                ->where('status', 'invited')
                ->firstOrFail();

            // Verificar que el token no haya expirado (24 horas)
            if ($staff->invitation_sent_at->addHours(24) < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de invitación expirado'
                ], 400);
            }

            $staff->update([
                'user_id' => $request->user_id,
                'status' => 'confirmed',
                'hire_date' => now(),
                'invitation_token' => null
            ]);

            // Asociar usuario al negocio
            $user = User::find($request->user_id);
            $user->businesses()->syncWithoutDetaching($staff->business_id);
            $user->update(['active_business_id' => $staff->business_id]);

            // Enviar notificación Firebase
            $this->staffNotificationService->writeStaffRequest($staff, 'confirmed');

            Log::info('User joined business with token', [
                'staff_id' => $staff->id,
                'user_id' => $request->user_id,
                'business_id' => $staff->business_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Te has unido al negocio exitosamente',
                'data' => $staff
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to join business with token', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token de invitación inválido o expirado'
            ], 400);
        }
    }

    /**
     * 🗑️ ELIMINAR SOLICITUD DE STAFF
     */
    public function destroy($id)
    {
        try {
            $staff = Staff::findOrFail($id);
            
            // Remover de Firebase antes de eliminar
            $this->staffNotificationService->removeStaffRequest($staff);
            
            $staff->delete();

            Log::info('Staff request deleted', ['staff_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de staff eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete staff request', [
                'staff_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la solicitud de staff'
            ], 500);
        }
    }

    /**
     * 🧪 PROBAR NOTIFICACIONES DE STAFF
     */
    public function testNotifications(Request $request)
    {
        $request->validate([
            'business_id' => 'required|exists:businesses,id'
        ]);

        try {
            $result = $this->staffNotificationService->testConnection();

            return response()->json([
                'success' => true,
                'message' => 'Test de notificaciones de staff completado',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en test de notificaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📱 GENERAR URL DE WHATSAPP PARA INVITACIÓN
     */
    private function generateWhatsAppInvitation(Staff $staff): ?string
    {
        try {
            $business = Business::find($staff->business_id);
            
            if (!$business) {
                return null;
            }

            // Limpiar número de teléfono
            $phone = preg_replace('/[^0-9]/', '', $staff->phone);
            
            if (strlen($phone) < 10) {
                return null;
            }

            // Generar URL de invitación
            $invitationUrl = url("/staff/join/{$staff->invitation_token}");

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
            return "https://wa.me/{$phone}?text=" . urlencode($message);
            
        } catch (\Exception $e) {
            Log::error('Failed to generate WhatsApp invitation URL', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 📱 OBTENER URL DE WHATSAPP PARA UNA INVITACIÓN EXISTENTE
     */
    public function getWhatsAppInvitation($id)
    {
        try {
            $staff = Staff::findOrFail($id);
            
            if ($staff->status !== 'invited' || !$staff->invitation_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay una invitación activa para este staff'
                ], 400);
            }

            $whatsappUrl = $this->generateWhatsAppInvitation($staff);

            if (!$whatsappUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo generar la URL de WhatsApp'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'whatsapp_url' => $whatsappUrl,
                    'phone' => $staff->phone,
                    'invitation_token' => $staff->invitation_token,
                    'expires_at' => $staff->invitation_sent_at->addHours(24)->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get WhatsApp invitation URL', [
                'staff_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la URL de WhatsApp'
            ], 500);
        }
    }
}