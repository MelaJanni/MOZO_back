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

            // 🔥 FIREBASE REAL-TIME PARA CLIENTE (ESTRUCTURA CORRECTA)
            \Illuminate\Support\Facades\Http::timeout(3)->put(
                "https://mozoqr-7d32c-default-rtdb.firebaseio.com/tables/{$call->table_id}/call_status/{$call->id}.json",
                [
                    'status' => 'acknowledged',
                    'waiter_id' => (string)$call->waiter_id,
                    'waiter_name' => $call->waiter->name ?? 'Mozo',
                    'acknowledged_at' => time() * 1000,
                    'message' => 'Tu mozo recibió la solicitud'
                ]
            );

            // 🔔 PUSH NOTIFICATION AL CLIENTE
            $this->sendClientNotification($call, 'acknowledged', 'Tu mozo recibió la solicitud');

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

            // 🔥 FIREBASE REAL-TIME PARA CLIENTE (ESTRUCTURA CORRECTA)
            \Illuminate\Support\Facades\Http::timeout(3)->put(
                "https://mozoqr-7d32c-default-rtdb.firebaseio.com/tables/{$call->table_id}/call_status/{$call->id}.json",
                [
                    'status' => 'completed',
                    'waiter_id' => (string)$call->waiter_id,
                    'waiter_name' => $call->waiter->name ?? 'Mozo',
                    'completed_at' => time() * 1000,
                    'message' => 'Servicio completado ✅'
                ]
            );

            // 🔔 PUSH NOTIFICATION AL CLIENTE
            $this->sendClientNotification($call, 'completed', 'Servicio completado ✅');

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
                        "https://mozoqr-7d32c-default-rtdb.firebaseio.com/tables/{$tableId}/call_status/{$callId}.json"
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
}