<?php

namespace App\Http\Controllers;

use App\Models\WaiterCall;
use App\Models\Table;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WaiterCallRealtimeController extends Controller
{
    protected $firestore;

    public function __construct()
    {
        $this->firestore = new FirestoreClient([
            'projectId' => 'mozoqr-7d32c',
            'keyFilePath' => storage_path('app/firebase/mozoqr-7d32c-firebase-adminsdk-fbsvc-0a90bdb0a0.json'),
        ]);
    }

    public function createInstantCall(Request $request, $table = null)
    {
        // Compatibilidad con ambas rutas
        $tableId = $table ? $table->id : $request->input('table_id');
        
        $request->validate([
            'table_id' => $table ? 'nullable' : 'required|exists:tables,id',
            'message' => 'nullable|string|max:255',
            'urgency' => 'nullable|in:normal,high'
        ]);

        $startTime = microtime(true);

        try {
            $tableModel = $table ?: Table::select('id', 'number', 'name', 'active_waiter_id', 'business_id', 'restaurant_id')
                ->with(['activeWaiter:id,name,user_id'])
                ->findOrFail($tableId);

            if (!$tableModel->activeWaiter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mesa sin mozo asignado'
                ], 400);
            }

            $callId = uniqid('call_', true);
            $timestamp = now()->toIso8601String();

            $firestoreData = [
                'id' => $callId,
                'table_id' => (string)$tableModel->id,
                'table_number' => (string)$tableModel->number,
                'table_name' => $tableModel->name ?? "Mesa {$tableModel->number}",
                'waiter_id' => (string)$tableModel->activeWaiter->user_id,
                'waiter_name' => $tableModel->activeWaiter->name,
                'business_id' => (string)$tableModel->business_id,
                'status' => 'pending',
                'message' => $request->message ?? "Mesa {$tableModel->number} solicita atención",
                'urgency' => $request->urgency ?? 'normal',
                'event_type' => 'created',
                'called_at' => $timestamp,
                'timestamp' => $timestamp,
                'acknowledged_at' => null,
                'completed_at' => null,
                'metadata' => [
                    'source' => 'api',
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]
            ];

            // 🚀 ESCRITURA INSTANTÁNEA A FIRESTORE
            $this->writeToFirestoreInstant($firestoreData);

            $firestoreTime = (microtime(true) - $startTime) * 1000;
            Log::info("Firestore write completed in {$firestoreTime}ms");

            // Background tasks (DB + FCM)
            dispatch(function() use ($tableModel, $firestoreData, $request) {
                WaiterCall::create([
                    'id' => $firestoreData['id'],
                    'table_id' => $tableModel->id,
                    'waiter_id' => $tableModel->activeWaiter->user_id,
                    'business_id' => $tableModel->business_id,
                    'restaurant_id' => $tableModel->restaurant_id,
                    'status' => 'pending',
                    'called_at' => now(),
                    'metadata' => [
                        'message' => $firestoreData['message'],
                        'urgency' => $firestoreData['urgency'],
                        'firestore_written' => true
                    ]
                ]);

                $this->sendFCMNotification($tableModel, $firestoreData);
            })->afterResponse();

            $totalTime = (microtime(true) - $startTime) * 1000;

            return response()->json([
                'success' => true,
                'message' => 'Llamada creada exitosamente',
                'data' => [
                    'call_id' => $callId,
                    'status' => 'pending',
                    'waiter' => [
                        'id' => $tableModel->activeWaiter->user_id,
                        'name' => $tableModel->activeWaiter->name
                    ],
                    'performance' => [
                        'firestore_ms' => round($firestoreTime, 2),
                        'total_ms' => round($totalTime, 2)
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating instant call: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear llamada',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    protected function writeToFirestoreInstant($data)
    {
        try {
            $batch = $this->firestore->batch();

            // 1. Documento principal de la mesa
            $tableCallRef = $this->firestore
                ->collection('tables')
                ->document($data['table_id'])
                ->collection('waiter_calls')
                ->document($data['id']);
            $batch->set($tableCallRef, $data);

            // 2. Documento del mozo
            $waiterCallRef = $this->firestore
                ->collection('waiters')
                ->document($data['waiter_id'])
                ->collection('calls')
                ->document($data['id']);
            $batch->set($waiterCallRef, $data);

            // 3. Documento de llamadas activas (para QR y dashboard)
            $activeCallRef = $this->firestore
                ->collection('active_calls')
                ->document($data['id']);
            $batch->set($activeCallRef, array_merge($data, [
                'expires_at' => date('c', strtotime('+2 hours'))
            ]));

            // 4. Estado actual de la mesa
            $tableStatusRef = $this->firestore
                ->collection('tables')
                ->document($data['table_id'])
                ->collection('status')
                ->document('current');
            $batch->set($tableStatusRef, [
                'has_active_call' => true,
                'last_call_id' => $data['id'],
                'last_call_time' => $data['timestamp'],
                'status' => 'calling_waiter'
            ], ['merge' => true]);

            $batch->commit();
            Log::info("Firestore batch write successful for call {$data['id']}");

        } catch (\Exception $e) {
            Log::error('Firestore write failed: ' . $e->getMessage());
            // Fallback: solo escribir en active_calls
            $this->firestore->collection('active_calls')->document($data['id'])->set($data);
        }
    }

    public function acknowledgeCall($callId)
    {
        try {
            $timestamp = now()->toIso8601String();
            $updateData = [
                'status' => 'acknowledged',
                'event_type' => 'acknowledged',
                'acknowledged_at' => $timestamp,
                'timestamp' => $timestamp
            ];

            $batch = $this->firestore->batch();

            $callDoc = $this->firestore->collection('active_calls')->document($callId)->snapshot();
            
            if ($callDoc->exists()) {
                $callData = $callDoc->data();

                // Actualizar en todas las colecciones
                $batch->update(
                    $this->firestore
                        ->collection('tables')
                        ->document($callData['table_id'])
                        ->collection('waiter_calls')
                        ->document($callId),
                    $updateData
                );

                $batch->update(
                    $this->firestore
                        ->collection('waiters')
                        ->document($callData['waiter_id'])
                        ->collection('calls')
                        ->document($callId),
                    $updateData
                );

                $batch->update(
                    $this->firestore->collection('active_calls')->document($callId),
                    $updateData
                );

                $batch->commit();

                // Actualizar DB en background
                WaiterCall::where('id', $callId)->update([
                    'status' => 'acknowledged',
                    'acknowledged_at' => now()
                ]);

                return response()->json(['success' => true, 'message' => 'Llamada reconocida']);
            }

            return response()->json(['success' => false, 'message' => 'Llamada no encontrada'], 404);

        } catch (\Exception $e) {
            Log::error('Error acknowledging call: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al reconocer llamada'], 500);
        }
    }

    public function completeCall($callId)
    {
        try {
            $timestamp = now()->toIso8601String();

            $callDoc = $this->firestore->collection('active_calls')->document($callId)->snapshot();
            
            if (!$callDoc->exists()) {
                return response()->json(['success' => false, 'message' => 'Llamada no encontrada'], 404);
            }

            $callData = $callDoc->data();
            $updateData = [
                'status' => 'completed',
                'event_type' => 'completed',
                'completed_at' => $timestamp,
                'timestamp' => $timestamp
            ];

            $batch = $this->firestore->batch();

            // Actualizar historial
            $batch->update(
                $this->firestore
                    ->collection('tables')
                    ->document($callData['table_id'])
                    ->collection('waiter_calls')
                    ->document($callId),
                $updateData
            );

            $batch->update(
                $this->firestore
                    ->collection('waiters')
                    ->document($callData['waiter_id'])
                    ->collection('calls')
                    ->document($callId),
                $updateData
            );

            // Eliminar de activas
            $batch->delete($this->firestore->collection('active_calls')->document($callId));

            // Actualizar estado de mesa
            $batch->update(
                $this->firestore
                    ->collection('tables')
                    ->document($callData['table_id'])
                    ->collection('status')
                    ->document('current'),
                [
                    'has_active_call' => false,
                    'status' => 'idle'
                ]
            );

            $batch->commit();

            // Actualizar DB
            WaiterCall::where('id', $callId)->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            return response()->json(['success' => true, 'message' => 'Servicio completado']);

        } catch (\Exception $e) {
            Log::error('Error completing call: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al completar llamada'], 500);
        }
    }

    protected function sendFCMNotification($table, $callData)
    {
        try {
            app('App\Services\FirebaseService')->sendToUserWithOptions(
                $callData['waiter_id'],
                "🔔 Mesa {$table->number}",
                $callData['message'],
                [
                    'type' => 'waiter_call',
                    'call_id' => $callData['id'],
                    'table_id' => $callData['table_id'],
                    'urgency' => $callData['urgency']
                ],
                ['priority' => 'high']
            );
        } catch (\Exception $e) {
            Log::error('FCM notification failed: ' . $e->getMessage());
        }
    }

    public function testLatency(Request $request)
    {
        $startTime = microtime(true);

        $testId = 'test_' . uniqid();
        $testData = [
            'id' => $testId,
            'timestamp' => now()->toIso8601String(),
            'test' => true
        ];

        $this->firestore->collection('latency_tests')->document($testId)->set($testData);
        $writeTime = (microtime(true) - $startTime) * 1000;

        $readStart = microtime(true);
        $doc = $this->firestore->collection('latency_tests')->document($testId)->snapshot();
        $readTime = (microtime(true) - $readStart) * 1000;

        $this->firestore->collection('latency_tests')->document($testId)->delete();

        return response()->json([
            'write_latency_ms' => round($writeTime, 2),
            'read_latency_ms' => round($readTime, 2),
            'total_ms' => round($writeTime + $readTime, 2)
        ]);
    }
}