<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FirebaseRealtimeNotificationService
{
    private $projectId;
    private $accessToken;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', 'mozoqr-7d32c');
        $this->accessToken = $this->getAccessToken();
        
        if ($this->accessToken) {
            Log::info('Firebase HTTP client initialized successfully');
        } else {
            Log::warning('Firebase not configured properly');
        }
    }

    /**
     * Obtener access token para Firebase
     */
    private function getAccessToken()
    {
        $serviceAccountPath = storage_path('app/firebase/mozoqr-7d32c-firebase-adminsdk-fbsvc-0a90bdb0a0.json');
        
        if (!file_exists($serviceAccountPath)) {
            Log::warning("Firebase service account file not found: {$serviceAccountPath}");
            return null;
        }

        try {
            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
            
            // Create JWT for Firestore
            $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
            $now = time();
            $payload = json_encode([
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/datastore',
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
     * 🚀 ESCRIBIR NUEVA LLAMADA DE MOZO
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

            // ✅ ESCRITURA HTTP: Solo en la colección del mozo
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/waiters/{$call->waiter_id}/calls/{$call->id}";
            
            $response = Http::timeout(5)->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->patch($url, [
                'fields' => $this->convertToFirestoreFields($callData)
            ]);

            if ($response->successful()) {
                Log::info("✅ Real-time notification sent", [
                    'call_id' => $call->id,
                    'waiter_id' => $call->waiter_id,
                    'table_number' => $call->table->number
                ]);
                return true;
            } else {
                Log::error('Firebase HTTP write failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Firebase write failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Convertir datos PHP a formato Firestore
     */
    private function convertToFirestoreFields($data)
    {
        $fields = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $fields[$key] = ['stringValue' => $value];
            } elseif (is_int($value) || is_numeric($value)) {
                $fields[$key] = ['integerValue' => (string)$value];
            } elseif (is_bool($value)) {
                $fields[$key] = ['booleanValue' => $value];
            } elseif (is_array($value)) {
                $fields[$key] = ['stringValue' => json_encode($value)];
            } elseif (is_null($value)) {
                $fields[$key] = ['nullValue' => null];
            } else {
                $fields[$key] = ['stringValue' => (string)$value];
            }
        }
        
        return $fields;
    }

    /**
     * 🔄 ACTUALIZAR ESTADO DE LLAMADA
     */
    public function updateWaiterCall($call, $status)
    {
        if (!$this->accessToken) {
            return false;
        }

        try {
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/waiters/{$call->waiter_id}/calls/{$call->id}";

            if ($status === 'completed') {
                // ❌ ELIMINAR cuando se completa
                $response = Http::timeout(5)->withHeaders([
                    'Authorization' => 'Bearer ' . $this->accessToken,
                ])->delete($url);
                    
                Log::info("✅ Call removed from real-time", ['call_id' => $call->id]);
            } else {
                // 🔄 ACTUALIZAR estado
                $updateData = [
                    'status' => $status,
                    'timestamp' => now()->toIso8601String(),
                    'event_type' => $status
                ];

                if ($status === 'acknowledged') {
                    $updateData['acknowledged_at'] = now()->toIso8601String();
                }

                $response = Http::timeout(5)->withHeaders([
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ])->patch($url, [
                    'fields' => $this->convertToFirestoreFields($updateData)
                ]);
                    
                Log::info("✅ Call status updated", ['call_id' => $call->id, 'status' => $status]);
            }

            return $response->successful();

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
                'timestamp' => now()->toIso8601String()
            ];

            // Escribir
            $start = microtime(true);
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/connection_tests/{$testId}";
            
            $writeResponse = Http::timeout(5)->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->patch($url, [
                'fields' => $this->convertToFirestoreFields($testData)
            ]);
            
            $writeTime = (microtime(true) - $start) * 1000;

            if (!$writeResponse->successful()) {
                return [
                    'success' => false,
                    'error' => 'Write failed: ' . $writeResponse->body(),
                    'status_code' => $writeResponse->status()
                ];
            }

            // Leer
            $start = microtime(true);
            $readResponse = Http::timeout(5)->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
            ])->get($url);
            $readTime = (microtime(true) - $start) * 1000;

            // Limpiar
            Http::timeout(5)->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
            ])->delete($url);

            return [
                'success' => true,
                'write_ms' => round($writeTime, 2),
                'read_ms' => round($readTime, 2),
                'total_ms' => round($writeTime + $readTime, 2),
                'project_id' => $this->projectId
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}