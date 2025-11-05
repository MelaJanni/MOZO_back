<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\WaiterCall;
use App\Services\FirebaseService;
use App\Services\UnifiedFirebaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para gestión de notificaciones de mozos
 * 
 * Responsabilidades:
 * - Toggle notificaciones por mesa
 * - Configuración notificaciones globales
 * - Listar notificaciones
 * - Responder notificaciones
 * - Marcar notificaciones como leídas (individual y batch)
 * - Fetch de mesas y notificaciones (legacy)
 * 
 * Migrado desde WaiterController en FASE 3.2
 */
class WaiterNotificationsController extends Controller
{
    private $firebaseService;
    private $unifiedFirebaseService;

    public function __construct(FirebaseService $firebaseService, UnifiedFirebaseService $unifiedFirebaseService)
    {
        $this->firebaseService = $firebaseService;
        $this->unifiedFirebaseService = $unifiedFirebaseService;
    }

    /**
     * Toggle de notificaciones para una mesa específica
     * Permite al mozo activar/desactivar notificaciones por mesa
     */
    public function toggleTableNotifications($tableId): JsonResponse
    {
        $waiter = Auth::user();

        try {
            $table = Table::find($tableId);
            
            if (!$table) {
                return response()->json([
                    'message' => 'Mesa no encontrada'
                ], 404);
            }

            // Validar que el mozo esté asignado a esta mesa
            if ($table->active_waiter_id != $waiter->id) {
                return response()->json([
                    'message' => 'No estás asignado a esta mesa'
                ], 403);
            }

            $table->notifications_enabled = !$table->notifications_enabled;
            $table->save();

            return response()->json([
                'message' => $table->notifications_enabled 
                    ? 'Notificaciones activadas' 
                    : 'Notificaciones desactivadas',
                'notifications_enabled' => $table->notifications_enabled
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling notifications', [
                'table_id' => $tableId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Error al cambiar configuración de notificaciones'
            ], 500);
        }
    }

    /**
     * Configuración global de notificaciones
     * Activa/desactiva notificaciones para todas las mesas asignadas al mozo
     */
    public function globalNotifications(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => 'required|boolean'
        ]);

        $waiter = Auth::user();
        $enabled = $request->enabled;

        try {
            Table::where('active_waiter_id', $waiter->id)
                ->update(['notifications_enabled' => $enabled]);

            return response()->json([
                'message' => $enabled 
                    ? 'Notificaciones activadas para todas tus mesas' 
                    : 'Notificaciones desactivadas para todas tus mesas',
                'notifications_enabled' => $enabled
            ]);
        } catch (\Exception $e) {
            Log::error('Error setting global notifications', [
                'waiter_id' => $waiter->id,
                'enabled' => $enabled,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Error al cambiar configuración global'
            ], 500);
        }
    }

    /**
     * Listar notificaciones del mozo
     * (Legacy endpoint - considera deprecar a favor de fetchWaiterNotifications)
     */
    public function listNotifications(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        // Redirige a fetchWaiterNotifications que tiene lógica más completa
        return $this->fetchWaiterNotifications();
    }

    /**
     * Responder a una notificación
     * Actualiza el estado y opcionalmente envía respuesta al cliente
     */
    public function respondNotification(Request $request): JsonResponse
    {
        $request->validate([
            'notification_id' => 'required|integer',
            'response' => 'sometimes|string|max:500'
        ]);

        $waiter = Auth::user();

        try {
            $call = WaiterCall::find($request->notification_id);
            
            if (!$call || $call->waiter_id != $waiter->id) {
                return response()->json([
                    'message' => 'Notificación no encontrada o no autorizado'
                ], 404);
            }

            $call->status = 'responded';
            $call->responded_at = now();
            
            if ($request->has('response')) {
                $call->response = $request->response;
            }
            
            $call->save();

            return response()->json([
                'message' => 'Respuesta enviada',
                'notification' => $call
            ]);
        } catch (\Exception $e) {
            Log::error('Error responding notification', [
                'notification_id' => $request->notification_id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Error al responder notificación'
            ], 500);
        }
    }

    /**
     * Fetch de mesas del mozo
     * Incluye contadores de llamadas pendientes por mesa
     */
    public function fetchWaiterTables(): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            $tables = Table::where('active_waiter_id', $waiter->id)
                ->with(['business'])
                ->get()
                ->map(function ($table) {
                    $pendingCalls = WaiterCall::where('table_id', $table->id)
                        ->where('status', 'pending')
                        ->count();

                    return [
                        'id' => $table->id,
                        'number' => $table->number,
                        'name' => $table->name,
                        'business' => [
                            'id' => $table->business->id,
                            'name' => $table->business->name
                        ],
                        'notifications_enabled' => $table->notifications_enabled,
                        'pending_calls' => $pendingCalls
                    ];
                });

            return response()->json([
                'success' => true,
                'tables' => $tables,
                'count' => $tables->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching waiter tables', [
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo mesas'
            ], 500);
        }
    }

    /**
     * Fetch de notificaciones del mozo
     * Retorna llamadas pendientes y recientes con metadata completa
     */
    public function fetchWaiterNotifications(): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            $pendingCalls = WaiterCall::where('waiter_id', $waiter->id)
                ->where('status', 'pending')
                ->with(['table'])
                ->orderBy('called_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'notifications' => $pendingCalls->map(function ($call) {
                    return [
                        'id' => $call->id,
                        'table_id' => $call->table_id,
                        'table_number' => $call->table->number,
                        'table_name' => $call->table->name,
                        'message' => $call->message,
                        'called_at' => $call->called_at,
                        'status' => $call->status,
                        'minutes_ago' => $call->called_at->diffInMinutes(now())
                    ];
                }),
                'count' => $pendingCalls->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching waiter notifications', [
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo notificaciones'
            ], 500);
        }
    }

    /**
     * Procesar notificación compleja
     * Endpoint con múltiples responsabilidades (acknowledge, complete, respond)
     * 
     * ⚠️ CONSIDERAR DEPRECAR: Este método hace demasiadas cosas diferentes
     * según el parámetro 'action'. Mejor usar endpoints específicos:
     * - POST /calls/{id}/acknowledge
     * - POST /calls/{id}/complete
     * - POST /notifications/{id}/respond
     */
    public function handleNotification(Request $request, $notificationId): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            $call = WaiterCall::with(['table'])->find($notificationId);
            
            if (!$call) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ], 404);
            }

            // Validar autorización
            if ($call->waiter_id != $waiter->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para esta notificación'
                ], 403);
            }

            // Determinar acción basada en request
            $action = $request->input('action', 'acknowledge');

            switch ($action) {
                case 'acknowledge':
                    if ($call->status === 'pending') {
                        $call->status = 'acknowledged';
                        $call->acknowledged_at = now();
                        $call->save();

                        // Notificar a Firebase
                        try {
                            $this->unifiedFirebaseService->writeCall($call, 'acknowledged');
                        } catch (\Exception $e) {
                            Log::warning('Firebase acknowledge failed', ['call_id' => $call->id]);
                        }

                        return response()->json([
                            'success' => true,
                            'message' => 'Llamada reconocida',
                            'call' => $call
                        ]);
                    }
                    break;

                case 'complete':
                    if (in_array($call->status, ['pending', 'acknowledged'])) {
                        $call->status = 'completed';
                        $call->completed_at = now();
                        $call->save();

                        // Remover de Firebase
                        try {
                            $this->unifiedFirebaseService->removeCall($call);
                        } catch (\Exception $e) {
                            Log::warning('Firebase remove failed', ['call_id' => $call->id]);
                        }

                        return response()->json([
                            'success' => true,
                            'message' => 'Llamada completada',
                            'call' => $call
                        ]);
                    }
                    break;

                case 'respond':
                    $request->validate([
                        'response' => 'required|string|max:500'
                    ]);

                    $call->response = $request->response;
                    $call->responded_at = now();
                    $call->save();

                    return response()->json([
                        'success' => true,
                        'message' => 'Respuesta enviada',
                        'call' => $call
                    ]);
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Acción no válida'
                    ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudo procesar la notificación'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error handling notification', [
                'notification_id' => $notificationId,
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error procesando notificación'
            ], 500);
        }
    }

    /**
     * Marcar notificación individual como leída
     */
    public function markNotificationAsRead(Request $request, $notificationId): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            $call = WaiterCall::find($notificationId);
            
            if (!$call) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ], 404);
            }

            if ($call->waiter_id != $waiter->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            $call->read_at = now();
            $call->save();

            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída'
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking notification as read', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error marcando notificación como leída'
            ], 500);
        }
    }

    /**
     * Marcar múltiples notificaciones como leídas (batch operation)
     */
    public function markMultipleNotificationsAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer'
        ]);

        $waiter = Auth::user();
        
        try {
            $updatedCount = WaiterCall::whereIn('id', $request->notification_ids)
                ->where('waiter_id', $waiter->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => "Se marcaron {$updatedCount} notificaciones como leídas",
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking multiple notifications as read', [
                'waiter_id' => $waiter->id,
                'notification_ids' => $request->notification_ids,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error marcando notificaciones como leídas'
            ], 500);
        }
    }
}
