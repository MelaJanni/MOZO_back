<?php

namespace App\Http\Controllers;

use App\Models\WaiterCall;
use App\Models\Table;
use App\Services\FirebaseRealtimeDatabaseService;
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
     * ✅ RECONOCER LLAMADA
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

            // Actualizar en tiempo real
            $this->firebaseService->updateWaiterCall($call, 'acknowledged');

            Log::info("Call acknowledged", ['call_id' => $callId]);

            return response()->json([
                'success' => true,
                'message' => 'Llamada reconocida'
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
     * ✅ COMPLETAR LLAMADA
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

            // Eliminar de tiempo real
            $this->firebaseService->updateWaiterCall($call, 'completed');

            Log::info("Call completed", ['call_id' => $callId]);

            return response()->json([
                'success' => true,
                'message' => 'Servicio completado'
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
}