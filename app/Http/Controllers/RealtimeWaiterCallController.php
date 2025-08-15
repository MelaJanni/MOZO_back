<?php

namespace App\Http\Controllers;

use App\Models\WaiterCall;
use App\Models\Table;
use App\Services\FirebaseRealtimeDatabaseService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RealtimeWaiterCallController extends Controller
{
    private $firebaseService;

    public function __construct(FirebaseRealtimeDatabaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * 🚀 CREAR LLAMADA CON NOTIFICACIÓN EN TIEMPO REAL
     */
    public function createCall(Request $request, Table $table)
    {
        $request->validate([
            'message' => 'nullable|string|max:255',
            'urgency' => 'nullable|in:normal,high'
        ]);

        $startTime = microtime(true);

        try {
            // Verificar que la mesa tenga mozo
            if (!$table->activeWaiter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mesa sin mozo asignado'
                ], 400);
            }

            // 1. 💾 CREAR EN BASE DE DATOS
            $call = WaiterCall::create([
                'table_id' => $table->id,
                'waiter_id' => $table->activeWaiter->user_id,
                'business_id' => $table->business_id,
                'restaurant_id' => $table->restaurant_id,
                'status' => 'pending',
                'message' => $request->message ?? "Mesa {$table->number} solicita atención",
                'called_at' => now(),
                'metadata' => [
                    'urgency' => $request->urgency ?? 'normal',
                    'source' => 'api',
                    'ip' => $request->ip()
                ]
            ]);

            // 2. 🔥 NOTIFICACIÓN EN TIEMPO REAL
            $realtimeSuccess = $this->firebaseService->writeWaiterCall($call);
            
            // 3. 🔥 ESCRIBIR TAMBIÉN EN EL PATH QUE ESCUCHA EL CLIENTE
            try {
                $clientFirebaseData = [
                    'status' => 'pending',
                    'table_id' => (string)$table->id,
                    'waiter_id' => (string)$table->activeWaiter->user_id,
                    'waiter_name' => $table->activeWaiter->name ?? 'Mozo',
                    'called_at' => time() * 1000,
                    'message' => $call->message ?? "Mesa {$table->number} solicita atención"
                ];
                
                $clientFirebaseResponse = \Illuminate\Support\Facades\Http::timeout(3)->put(
                    "https://mozoqr-7d32c-default-rtdb.firebaseio.com/tables/call_status/{$call->id}.json",
                    $clientFirebaseData
                );
                
                Log::info("Firebase client notification created", [
                    'call_id' => $call->id,
                    'table_id' => $table->id,
                    'firebase_url' => "tables/call_status/{$call->id}",
                    'firebase_status' => $clientFirebaseResponse->status(),
                    'firebase_success' => $clientFirebaseResponse->successful()
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to write to client Firebase path', ['error' => $e->getMessage(), 'call_id' => $call->id]);
            }

            $totalTime = (microtime(true) - $startTime) * 1000;

            Log::info("Waiter call created", [
                'call_id' => $call->id,
                'table_id' => $table->id,
                'waiter_id' => $table->activeWaiter->user_id,
                'realtime_success' => $realtimeSuccess,
                'duration_ms' => round($totalTime, 2)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Llamada creada exitosamente',
                'data' => [
                    'call_id' => $call->id,
                    'status' => 'pending',
                    'waiter' => [
                        'id' => $table->activeWaiter->user_id,
                        'name' => $table->activeWaiter->name
                    ],
                    'realtime_enabled' => $realtimeSuccess,
                    'performance_ms' => round($totalTime, 2)
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating waiter call', [
                'error' => $e->getMessage(),
                'table_id' => $table->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear llamada',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ✅ RECONOCER LLAMADA - CON REAL-TIME AL CLIENTE
     */
    public function acknowledgeCall(Request $request, $callId)
    {
        try {
            $call = WaiterCall::with(['table', 'waiter'])->findOrFail($callId);
            
            // Actualizar en BD
            $call->update([
                'status' => 'acknowledged',
                'acknowledged_at' => now()
            ]);

            // 🔥 FIREBASE REAL-TIME PARA MOZO
            \Illuminate\Support\Facades\Http::timeout(3)->put(
                "https://mozoqr-7d32c-default-rtdb.firebaseio.com/waiters/{$call->waiter_id}/calls/{$call->id}.json",
                [
                    'id' => (string)$call->id,
                    'table_number' => (int)$call->table->number,
                    'status' => 'acknowledged',
                    'acknowledged_at' => time() * 1000,
                    'message' => $call->message,
                    'waiter_id' => (string)$call->waiter_id
                ]
            );

            // 🔥 FIREBASE REAL-TIME PARA CLIENTE (ESTRUCTURA CORRECTA v2.0)
            $clientFirebaseData = [
                'status' => 'acknowledged',
                'table_id' => (string)$call->table_id,
                'waiter_id' => (string)$call->waiter_id,
                'waiter_name' => $call->waiter->name ?? 'Mozo',
                'acknowledged_at' => time() * 1000,
                'message' => 'Tu mozo recibió la solicitud'
            ];
            
            $clientFirebaseResponse = \Illuminate\Support\Facades\Http::timeout(3)->put(
                "https://mozoqr-7d32c-default-rtdb.firebaseio.com/tables/call_status/{$call->id}.json",
                $clientFirebaseData
            );
            
            Log::info("Firebase client notification sent", [
                'call_id' => $callId,
                'table_id' => $call->table_id,
                'firebase_url' => "tables/call_status/{$call->id}",
                'firebase_status' => $clientFirebaseResponse->status(),
                'firebase_success' => $clientFirebaseResponse->successful()
            ]);

            // Además, actualizar Firestore / estructuras que otros clientes (por ejemplo la página pública)
            // puedan estar escuchando. Esto evita inconsistencia entre Realtime DB y Firestore listeners.
            try {
                $firestoreService = app(\App\Services\FirebaseRealtimeService::class);
                $firestoreService->writeWaiterCall($call, 'acknowledged');
                Log::info('Firestore updated for acknowledged call', ['call_id' => $callId]);
            } catch (\Exception $e) {
                Log::warning('Failed to update Firestore for acknowledged call', ['error' => $e->getMessage(), 'call_id' => $callId]);
            }

            // También actualizar estado resumido de la mesa en Firestore para que la página pública
            // que escucha `tables/{tableId}/status/current` refleje inmediatamente el ack.
            try {
                $firestoreService = app(\App\Services\FirebaseRealtimeService::class);
                $statusData = [
                    'status' => 'acknowledged',
                    'waiter_id' => (string)$call->waiter_id,
                    'waiter_name' => $call->waiter->name ?? 'Mozo',
                    'acknowledged_at' => time() * 1000,
                    'message' => 'Tu mozo recibió la solicitud'
                ];

                $firestoreService->writeTableStatus($call->table, 'acknowledged', $statusData);
                Log::info('Firestore table status updated for acknowledged call', ['call_id' => $callId, 'table_id' => $call->table_id]);
            } catch (\Exception $e) {
                Log::warning('Failed to update Firestore table status for acknowledged call', ['error' => $e->getMessage(), 'call_id' => $callId]);
            }

            // 🔥 NO PUSH NOTIFICATION - Solo actualización en tiempo real
            // El cliente verá el cambio via Firebase Realtime Database listener

            // 📋 MARCAR NOTIFICACIONES RELACIONADAS COMO LEÍDAS
            $this->markRelatedNotificationsAsRead($call);

            Log::info("Call acknowledged with real-time updates", ['call_id' => $callId]);

            return response()->json([
                'success' => true,
                'message' => 'Llamada reconocida - Cliente notificado en tiempo real'
            ]);

        } catch (\Exception $e) {
            Log::error('Error acknowledging call', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al reconocer llamada'
            ], 500);
        }
    }

    /**
     * ✅ COMPLETAR LLAMADA - CON REAL-TIME AL CLIENTE
     */
    public function completeCall(Request $request, $callId)
    {
        try {
            $call = WaiterCall::with(['table', 'waiter'])->findOrFail($callId);
            
            // Actualizar en BD
            $call->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            // 🔥 ELIMINAR DE FIREBASE REAL-TIME PARA MOZO (completado)
            \Illuminate\Support\Facades\Http::timeout(3)->delete(
                "https://mozoqr-7d32c-default-rtdb.firebaseio.com/waiters/{$call->waiter_id}/calls/{$call->id}.json"
            );

            // 🔥 FIREBASE REAL-TIME PARA CLIENTE (ESTRUCTURA CORRECTA v2.0)
            $clientFirebaseData = [
                'status' => 'completed',
                'table_id' => (string)$call->table_id,
                'waiter_id' => (string)$call->waiter_id,
                'waiter_name' => $call->waiter->name ?? 'Mozo',
                'completed_at' => time() * 1000,
                'message' => 'Servicio completado ✅'
            ];
            
            $clientFirebaseResponse = \Illuminate\Support\Facades\Http::timeout(3)->put(
                "https://mozoqr-7d32c-default-rtdb.firebaseio.com/tables/call_status/{$call->id}.json",
                $clientFirebaseData
            );
            
            Log::info("Firebase client notification sent (completed)", [
                'call_id' => $callId,
                'table_id' => $call->table_id,
                'firebase_url' => "tables/call_status/{$call->id}",
                'firebase_status' => $clientFirebaseResponse->status(),
                'firebase_success' => $clientFirebaseResponse->successful()
            ]);

            // 🔥 NO PUSH NOTIFICATION - Solo actualización en tiempo real
            // El cliente verá el cambio via Firebase Realtime Database listener

            // 📋 MARCAR NOTIFICACIONES RELACIONADAS COMO LEÍDAS
            $this->markRelatedNotificationsAsRead($call);

            // 🕒 AUTO-CLEANUP: Programar eliminación automática después de 30 segundos
            $this->scheduleCallCleanup($call->table_id, $call->id, 30);

            Log::info("Call completed with real-time updates", ['call_id' => $callId]);

            return response()->json([
                'success' => true,
                'message' => 'Servicio completado - Cliente notificado en tiempo real'
            ]);

        } catch (\Exception $e) {
            Log::error('Error completing call', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al completar llamada'
            ], 500);
        }
    }

    /**
     * 📋 OBTENER LLAMADAS PENDIENTES DEL MOZO
     */
    public function getPendingCalls(Request $request)
    {
        try {
            $waiterId = $request->user()->id;
            
            $calls = WaiterCall::with(['table'])
                ->where('waiter_id', $waiterId)
                ->where('status', 'pending')
                ->orderBy('called_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $calls->map(function($call) {
                    return [
                        'id' => $call->id,
                        'table_number' => $call->table->number,
                        'table_name' => $call->table->name,
                        'message' => $call->message,
                        'urgency' => $call->metadata['urgency'] ?? 'normal',
                        'called_at' => $call->called_at,
                        'status' => $call->status
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🧪 TEST DE CONEXIÓN FIREBASE
     */
    public function testFirebase()
    {
        $result = $this->firebaseService->testConnection();
        
        return response()->json([
            'firebase_status' => $result,
            'timestamp' => now()
        ]);
    }

    /**
     * 🔍 DEBUG: OBTENER LLAMADAS RECIENTES PARA TESTING
     */
    public function getRecentCalls()
    {
        try {
            $recentCalls = WaiterCall::with(['table', 'waiter'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->map(function($call) {
                    return [
                        'id' => $call->id,
                        'table_id' => $call->table_id,
                        'table_number' => $call->table->number ?? 'N/A',
                        'waiter_id' => $call->waiter_id,
                        'waiter_name' => $call->waiter->name ?? 'N/A',
                        'status' => $call->status,
                        'message' => $call->message,
                        'created_at' => $call->created_at,
                        'acknowledged_at' => $call->acknowledged_at,
                        'completed_at' => $call->completed_at
                    ];
                });

            return response()->json([
                'success' => true,
                'recent_calls' => $recentCalls,
                'total_found' => $recentCalls->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔔 ENVIAR NOTIFICACIÓN PUSH AL CLIENTE
     */
    private function sendClientNotification($call, $status, $message)
    {
        try {
            // 🔥 NOTIFICACIÓN EN TIEMPO REAL (ya implementada via Firebase Realtime)
            // 🔔 PUSH NOTIFICATION PARA CLIENTES OFFLINE
            $pushService = new PushNotificationService();
            $pushSuccess = $pushService->sendToTable($call->table_id, [
                'title' => $status === 'acknowledged' ? 'Tu mozo está en camino' : 'Servicio completado',
                'body' => $message,
                'data' => [
                    'type' => 'waiter_call_update',
                    'call_id' => (string)$call->id,
                    'status' => $status,
                    'waiter_name' => $call->waiter->name ?? 'Mozo',
                    'table_id' => (string)$call->table_id
                ]
            ]);
            
            Log::info("Client notification sent", [
                'table_id' => $call->table_id,
                'status' => $status,
                'message' => $message,
                'realtime_via_firebase' => true,
                'push_notification_sent' => $pushSuccess
            ]);
            
        } catch (\Exception $e) {
            Log::warning('Failed to send client notification', [
                'error' => $e->getMessage(),
                'table_id' => $call->table_id
            ]);
        }
    }

    /**
     * 🕒 PROGRAMAR LIMPIEZA AUTOMÁTICA DE LLAMADA COMPLETADA
     */
    private function scheduleCallCleanup($tableId, $callId, $delaySeconds = 30)
    {
        try {
            // Usar dispatch con delay para programar eliminación
            dispatch(function() use ($tableId, $callId) {
                try {
                    // Eliminar de Firebase después del delay
                    \Illuminate\Support\Facades\Http::timeout(3)->delete(
                        "https://mozoqr-7d32c-default-rtdb.firebaseio.com/tables/call_status/{$callId}.json"
                    );
                    
                    Log::info("Auto-cleanup completed for call", [
                        'table_id' => $tableId,
                        'call_id' => $callId,
                        'cleanup_delay_seconds' => 30
                    ]);
                    
                } catch (\Exception $e) {
                    Log::warning('Auto-cleanup failed', [
                        'table_id' => $tableId,
                        'call_id' => $callId,
                        'error' => $e->getMessage()
                    ]);
                }
            })->delay(now()->addSeconds($delaySeconds));
            
            Log::info("Auto-cleanup scheduled", [
                'table_id' => $tableId,
                'call_id' => $callId,
                'delay_seconds' => $delaySeconds
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to schedule auto-cleanup', [
                'table_id' => $tableId,
                'call_id' => $callId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 📋 MARCAR NOTIFICACIONES RELACIONADAS COMO LEÍDAS
     */
    private function markRelatedNotificationsAsRead($call)
    {
        try {
            // Buscar notificaciones del mozo relacionadas con esta llamada
            $waiter = \App\Models\User::find($call->waiter_id);
            
            if ($waiter) {
                // Buscar notificaciones no leídas que contengan el ID de la llamada o mesa
                $notifications = $waiter->unreadNotifications()
                    ->where(function($query) use ($call) {
                        $query->where('data->call_id', $call->id)
                              ->orWhere('data->table_id', $call->table_id)
                              ->orWhere('data->table_number', $call->table->number ?? null);
                    })
                    ->get();

                foreach ($notifications as $notification) {
                    $notification->markAsRead();
                }

                Log::info("Marked notifications as read", [
                    'call_id' => $call->id,
                    'waiter_id' => $call->waiter_id,
                    'notifications_marked' => $notifications->count()
                ]);
            }

        } catch (\Exception $e) {
            Log::warning('Failed to mark notifications as read', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}