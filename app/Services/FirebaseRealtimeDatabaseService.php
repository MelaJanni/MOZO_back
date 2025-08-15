<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FirebaseRealtimeDatabaseService
{
    private $projectId;
    private $databaseUrl;
    private $accessToken;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', 'mozoqr-7d32c');
        $this->databaseUrl = "https://{$this->projectId}-default-rtdb.firebaseio.com";
        $this->accessToken = $this->getAccessToken();
        
        if ($this->accessToken) {
            Log::info('Firebase Realtime Database initialized successfully');
        } else {
            Log::warning('Firebase Realtime Database not configured properly');
        }
    }

    /**
     * Obtener access token para Firebase
     */
    private function getAccessToken()
    {
        // Probar múltiples ubicaciones del service account
        $possiblePaths = [
            storage_path('app/firebase/mozoqr-7d32c-firebase-adminsdk-fbsvc-0a90bdb0a0.json'),
            storage_path('app/firebase/firebase.json'),
            config('services.firebase.service_account_path')
        ];
        
        $serviceAccountPath = null;
        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path)) {
                $serviceAccountPath = $path;
                break;
            }
        }
        
        if (!$serviceAccountPath) {
            Log::warning("Firebase service account file not found in any location", [
                'tried_paths' => $possiblePaths
            ]);
            return null;
        }

        try {
            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
            
            // Create JWT for Realtime Database
            $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
            $now = time();
            $payload = json_encode([
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.database https://www.googleapis.com/auth/userinfo.email',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]);

            $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

            $signature = '';
            openssl_sign($base64Header . '.' . $base64Payload, $signature, $serviceAccount['private_key'], 'SHA256');
            $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;

            $response = Http::post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            $tokenData = $response->json();
            return $tokenData['access_token'] ?? null;
            
        } catch (\Exception $e) {
            Log::error('Failed to get Firebase access token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 🚀 ESCRIBIR NUEVA LLAMADA DE MOZO (ULTRA RÁPIDO)
     */
    public function writeWaiterCall($call)
    {
        if (!$this->accessToken) {
            Log::warning('Firebase not available, skipping real-time notification');
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

            // ✅ ESCRITURA ULTRA RÁPIDA: Direct JSON to Realtime DB
            $url = "{$this->databaseUrl}/waiters/{$call->waiter_id}/calls/{$call->id}.json?auth={$this->accessToken}";
            
            $response = Http::timeout(3)->put($url, $callData);

            if ($response->successful()) {
                Log::info("⚡ Real-time notification sent ULTRA FAST", [
                    'call_id' => $call->id,
                    'waiter_id' => $call->waiter_id,
                    'table_number' => $call->table->number,
                    'latency_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2)
                ]);
                return true;
            } else {
                Log::error('Firebase Realtime DB write failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Firebase Realtime DB write failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 🔄 ACTUALIZAR ESTADO DE LLAMADA (ULTRA RÁPIDO)
     */
    public function updateWaiterCall($call, $status)
    {
        if (!$this->accessToken) {
            return false;
        }

        try {
            if ($status === 'completed') {
                // ❌ ELIMINAR cuando se completa (ULTRA RÁPIDO)
                $url = "{$this->databaseUrl}/waiters/{$call->waiter_id}/calls/{$call->id}.json?auth={$this->accessToken}";
                $response = Http::timeout(3)->delete($url);
                    
                Log::info("⚡ Call removed from real-time ULTRA FAST", ['call_id' => $call->id]);
            } else {
                // 🔄 ACTUALIZAR estado (ULTRA RÁPIDO)
                $updateData = [
                    'status' => $status,
                    'timestamp' => now()->toIso8601String(),
                    'event_type' => $status
                ];

                if ($status === 'acknowledged') {
                    $updateData['acknowledged_at'] = now()->toIso8601String();
                }

                $url = "{$this->databaseUrl}/waiters/{$call->waiter_id}/calls/{$call->id}.json?auth={$this->accessToken}";
                $response = Http::timeout(3)->patch($url, $updateData);
                    
                Log::info("⚡ Call status updated ULTRA FAST", ['call_id' => $call->id, 'status' => $status]);
            }

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Firebase Realtime DB update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 🧪 TEST DE CONEXIÓN ULTRA RÁPIDO
     */
    public function testConnection()
    {
        if (!$this->accessToken) {
            return [
                'success' => false, 
                'error' => 'Firebase access token not available',
                'service_account_exists' => file_exists(storage_path('app/firebase/mozoqr-7d32c-firebase-adminsdk-fbsvc-0a90bdb0a0.json'))
            ];
        }

        try {
            $testId = 'test_' . uniqid();
            $testData = [
                'test' => true,
                'timestamp' => now()->toIso8601String(),
                'speed' => 'ULTRA_FAST'
            ];

            // Escribir (ULTRA RÁPIDO)
            $start = microtime(true);
            $url = "{$this->databaseUrl}/connection_tests/{$testId}.json?auth={$this->accessToken}";
            
            $writeResponse = Http::timeout(3)->put($url, $testData);
            $writeTime = (microtime(true) - $start) * 1000;

            if (!$writeResponse->successful()) {
                return [
                    'success' => false,
                    'error' => 'Write failed: ' . $writeResponse->body(),
                    'status_code' => $writeResponse->status()
                ];
            }

            // Leer (ULTRA RÁPIDO)
            $start = microtime(true);
            $readResponse = Http::timeout(3)->get($url);
            $readTime = (microtime(true) - $start) * 1000;

            // Limpiar
            Http::timeout(3)->delete($url);

            return [
                'success' => true,
                'write_ms' => round($writeTime, 2),
                'read_ms' => round($readTime, 2),
                'total_ms' => round($writeTime + $readTime, 2),
                'project_id' => $this->projectId,
                'database_url' => $this->databaseUrl,
                'speed' => 'ULTRA_FAST_REALTIME_DB'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 📊 ESCRIBIR MÚLTIPLES LLAMADAS (BATCH ULTRA RÁPIDO)
     */
    public function batchWriteWaiterCalls(array $calls)
    {
        if (!$this->accessToken || empty($calls)) {
            return false;
        }

        try {
            $batchData = [];
            
            foreach ($calls as $call) {
                $callData = [
                    'id' => (string)$call->id,
                    'table_id' => (string)$call->table_id,
                    'table_number' => (string)$call->table->number,
                    'waiter_id' => (string)$call->waiter_id,
                    'waiter_name' => $call->waiter->name ?? 'Mozo',
                    'status' => $call->status,
                    'message' => $call->message ?? "Mesa {$call->table->number} solicita atención",
                    'timestamp' => now()->toIso8601String(),
                    'event_type' => 'created'
                ];
                
                $batchData["waiters/{$call->waiter_id}/calls/{$call->id}"] = $callData;
            }

            // ⚡ BATCH WRITE ULTRA RÁPIDO
            $url = "{$this->databaseUrl}/.json?auth={$this->accessToken}";
            $response = Http::timeout(5)->patch($url, $batchData);

            if ($response->successful()) {
                Log::info("⚡ Batch write ULTRA FAST completed", [
                    'calls_count' => count($calls),
                    'batch_size' => count($batchData)
                ]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Firebase Realtime DB batch write failed: ' . $e->getMessage());
            return false;
        }
    }
}