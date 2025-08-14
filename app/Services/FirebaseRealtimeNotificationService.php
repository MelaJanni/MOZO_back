<?php

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Facades\Log;

class FirebaseRealtimeNotificationService
{
    private $firestore;
    private $projectId;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', 'mozoqr-7d32c');
        
        try {
            $this->firestore = new FirestoreClient([
                'projectId' => $this->projectId,
                'keyFilePath' => storage_path('app/firebase/mozoqr-7d32c-firebase-adminsdk-fbsvc-0a90bdb0a0.json'),
            ]);
            Log::info('Firebase Firestore initialized successfully');
        } catch (\Exception $e) {
            Log::error('Firebase initialization failed: ' . $e->getMessage());
            $this->firestore = null;
        }
    }

    /**
     * 🚀 ESCRIBIR NUEVA LLAMADA DE MOZO
     */
    public function writeWaiterCall($call)
    {
        if (!$this->firestore) {
            Log::warning('Firestore not available, skipping real-time notification');
            return false;
        }

        try {
            $callData = [
                'id' => (string)$call->id,
                'table_id' => (string)$call->table_id,
                'table_number' => (string)$call->table->number,
                'table_name' => $call->table->name ?? "Mesa {$call->table->number}",
                'waiter_id' => (string)$call->waiter_id,
                'waiter_name' => $call->waiter->name ?? 'Mozo',
                'status' => $call->status,
                'message' => $call->message ?? "Mesa {$call->table->number} solicita atención",
                'urgency' => $call->metadata['urgency'] ?? 'normal',
                'called_at' => $call->called_at->toIso8601String(),
                'timestamp' => now()->toIso8601String(),
                'event_type' => 'created'
            ];

            // ✅ ESCRITURA SIMPLE: Solo en la colección del mozo
            $this->firestore
                ->collection('waiters')
                ->document($call->waiter_id)
                ->collection('calls')
                ->document($call->id)
                ->set($callData);

            Log::info("✅ Real-time notification sent", [
                'call_id' => $call->id,
                'waiter_id' => $call->waiter_id,
                'table_number' => $call->table->number
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Firebase write failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 🔄 ACTUALIZAR ESTADO DE LLAMADA
     */
    public function updateWaiterCall($call, $status)
    {
        if (!$this->firestore) {
            return false;
        }

        try {
            $updateData = [
                'status' => $status,
                'timestamp' => now()->toIso8601String(),
                'event_type' => $status
            ];

            if ($status === 'acknowledged') {
                $updateData['acknowledged_at'] = now()->toIso8601String();
            } elseif ($status === 'completed') {
                $updateData['completed_at'] = now()->toIso8601String();
            }

            if ($status === 'completed') {
                // ❌ ELIMINAR cuando se completa
                $this->firestore
                    ->collection('waiters')
                    ->document($call->waiter_id)
                    ->collection('calls')
                    ->document($call->id)
                    ->delete();
                    
                Log::info("✅ Call removed from real-time", ['call_id' => $call->id]);
            } else {
                // 🔄 ACTUALIZAR estado
                $this->firestore
                    ->collection('waiters')
                    ->document($call->waiter_id)
                    ->collection('calls')
                    ->document($call->id)
                    ->update($updateData);
                    
                Log::info("✅ Call status updated", ['call_id' => $call->id, 'status' => $status]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Firebase update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 🧪 TEST DE CONEXIÓN
     */
    public function testConnection()
    {
        if (!$this->firestore) {
            return ['success' => false, 'error' => 'Firestore not initialized'];
        }

        try {
            $testId = 'test_' . uniqid();
            $testData = [
                'test' => true,
                'timestamp' => now()->toIso8601String()
            ];

            // Escribir
            $start = microtime(true);
            $this->firestore->collection('connection_tests')->document($testId)->set($testData);
            $writeTime = (microtime(true) - $start) * 1000;

            // Leer
            $start = microtime(true);
            $doc = $this->firestore->collection('connection_tests')->document($testId)->snapshot();
            $readTime = (microtime(true) - $start) * 1000;

            // Limpiar
            $this->firestore->collection('connection_tests')->document($testId)->delete();

            return [
                'success' => true,
                'write_ms' => round($writeTime, 2),
                'read_ms' => round($readTime, 2),
                'total_ms' => round($writeTime + $readTime, 2)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}