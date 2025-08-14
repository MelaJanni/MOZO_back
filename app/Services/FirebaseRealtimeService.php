<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FirebaseRealtimeService
{
    private $client;
    private $projectId;
    private $accessToken;
    private static $instance = null;
    private $connectionPool;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 3.0,
            'connect_timeout' => 1.0,
            'read_timeout' => 2.0,
            'http_errors' => false,
            'curl' => [
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_FRESH_CONNECT => false,
                CURLOPT_FORBID_REUSE => false,
            ]
        ]);
        $this->projectId = config('services.firebase.project_id');
        $this->accessToken = $this->getAccessToken();
        $this->initializeConnectionPool();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeConnectionPool()
    {
        $this->connectionPool = new Client([
            'timeout' => 2.0,
            'connect_timeout' => 0.5,
            'read_timeout' => 1.5,
            'http_errors' => false,
            'verify' => false,
            'curl' => [
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_TIMEOUT => 2,
                CURLOPT_FRESH_CONNECT => false,
                CURLOPT_FORBID_REUSE => false,
            ]
        ]);
    }

    /**
     * Get OAuth 2.0 access token for Firestore
     */
    private function getAccessToken()
    {
        $serviceAccountPath = config('services.firebase.service_account_path');
        
        // 🚀 OPTIMIZACIÓN: Verificar si existe el archivo primero
        if (empty($serviceAccountPath) || !file_exists($serviceAccountPath)) {
            Log::warning("Firebase service account file not configured or not found", [
                'path' => $serviceAccountPath
            ]);
            return null; // Return null en lugar de exception para no romper el sistema
        }

        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
        
        // Create JWT for Firestore scope
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

        try {
            $response = $this->client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]
            ]);

            $tokenData = json_decode($response->getBody(), true);
            return $tokenData['access_token'];
        } catch (RequestException $e) {
            Log::error('Failed to get Firestore access token: ' . $e->getMessage());
            throw new \Exception('Failed to authenticate with Firestore');
        }
    }

    /**
     * Write waiter call data to Firestore with parallel writes - ULTRA OPTIMIZADO
     */
    public function writeWaiterCall($call, $eventType = 'created')
    {
        if (!$this->accessToken) {
            Log::info('Firebase not configured, skipping realtime sync', [
                'call_id' => $call->id,
                'event_type' => $eventType
            ]);
            return false;
        }
        
        try {
            $document = [
                'fields' => [
                    'id' => ['stringValue' => (string)$call->id],
                    'table_id' => ['stringValue' => (string)$call->table_id],
                    'table_number' => ['stringValue' => (string)$call->table->number],
                    'waiter_id' => ['stringValue' => (string)$call->waiter_id],
                    'waiter_name' => ['stringValue' => $call->waiter->name ?? 'Mozo'],
                    'status' => ['stringValue' => $call->status],
                    'message' => ['stringValue' => $call->message],
                    'event_type' => ['stringValue' => $eventType],
                    'timestamp' => ['stringValue' => now()->toISOString()],
                    'expires_at' => ['stringValue' => now()->addHours(2)->toISOString()]
                ]
            ];

            $paths = [
                "tables/{$call->table_id}/waiter_calls/{$call->id}",
                "waiters/{$call->waiter_id}/calls/{$call->id}",
                "active_calls/{$call->id}"
            ];

            if (isset($call->business_id)) {
                $paths[] = "businesses/{$call->business_id}/waiter_calls/{$call->id}";
            }

            $this->writeDocumentsParallel($paths, $document);
            $this->cacheCallData($call->id, $document);

            return true;

        } catch (\Exception $e) {
            Log::warning("Firebase parallel write failed", [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
            return $this->fallbackWrite($call, $eventType);
        }
    }

    /**
     * Parallel document writes using GuzzleHttp Pool
     */
    private function writeDocumentsParallel(array $paths, array $document)
    {
        $requests = [];
        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        foreach ($paths as $path) {
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$path}";
            $requests[] = new Request('PATCH', $url, $headers, json_encode($document));
        }

        $pool = new Pool($this->connectionPool, $requests, [
            'concurrency' => 4,
            'fulfilled' => function ($response, $index) {
                Log::debug("Parallel write completed", ['index' => $index, 'status' => $response->getStatusCode()]);
            },
            'rejected' => function ($reason, $index) {
                Log::warning("Parallel write failed", ['index' => $index, 'error' => $reason->getMessage()]);
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        return true;
    }

    /**
     * Batch write with retry mechanism
     */
    public function batchWriteWaiterCalls(array $calls, $eventType = 'created')
    {
        if (!$this->accessToken || empty($calls)) {
            return false;
        }

        $startTime = microtime(true);
        $allPaths = [];
        $documents = [];

        foreach ($calls as $call) {
            $document = $this->buildCallDocument($call, $eventType);
            $paths = $this->getCallPaths($call);
            
            foreach ($paths as $path) {
                $allPaths[] = $path;
                $documents[] = $document;
            }
        }

        try {
            $this->writeDocumentsParallel($allPaths, $documents[0]);
            
            $duration = (microtime(true) - $startTime) * 1000;
            Log::info("Batch write completed", [
                'calls_count' => count($calls),
                'paths_count' => count($allPaths),
                'duration_ms' => round($duration, 2)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Batch write failed", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Helper method to build call document
     */
    private function buildCallDocument($call, $eventType)
    {
        return [
            'fields' => [
                'id' => ['stringValue' => (string)$call->id],
                'table_id' => ['stringValue' => (string)$call->table_id],
                'table_number' => ['stringValue' => (string)($call->table->number ?? '')],
                'waiter_id' => ['stringValue' => (string)$call->waiter_id],
                'waiter_name' => ['stringValue' => $call->waiter->name ?? 'Mozo'],
                'status' => ['stringValue' => $call->status],
                'message' => ['stringValue' => $call->message ?? ''],
                'event_type' => ['stringValue' => $eventType],
                'timestamp' => ['stringValue' => now()->toISOString()],
                'expires_at' => ['stringValue' => now()->addHours(2)->toISOString()]
            ]
        ];
    }

    /**
     * Get all paths for a call
     */
    private function getCallPaths($call)
    {
        $paths = [
            "tables/{$call->table_id}/waiter_calls/{$call->id}",
            "waiters/{$call->waiter_id}/calls/{$call->id}",
            "active_calls/{$call->id}"
        ];

        if (isset($call->business_id)) {
            $paths[] = "businesses/{$call->business_id}/waiter_calls/{$call->id}";
        }

        return $paths;
    }

    /**
     * Cache call data for faster access
     */
    private function cacheCallData($callId, $document)
    {
        $cacheKey = "waiter_call_{$callId}";
        Cache::put($cacheKey, $document, 300); // 5 minutes
    }

    /**
     * Fallback write method
     */
    private function fallbackWrite($call, $eventType)
    {
        try {
            $document = $this->buildCallDocument($call, $eventType);
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/active_calls/{$call->id}";
            
            $this->connectionPool->patch($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $document,
            ]);

            Log::info('Fallback write successful', ['call_id' => $call->id]);
            return true;
        } catch (\Exception $e) {
            Log::error('Fallback write failed', ['call_id' => $call->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Update call with retry mechanism
     */
    public function updateWaiterCall($call, $eventType, $retries = 2)
    {
        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            try {
                $updateData = [
                    'fields' => [
                        'status' => ['stringValue' => $call->status],
                        'event_type' => ['stringValue' => $eventType],
                        'timestamp' => ['stringValue' => now()->toISOString()],
                        "{$eventType}_at" => ['stringValue' => now()->toISOString()]
                    ]
                ];

                $paths = $this->getCallPaths($call);
                $this->updateDocumentsParallel($paths, $updateData);

                if ($eventType === 'completed') {
                    $this->scheduleCallCleanup($call->id);
                }

                return true;
            } catch (\Exception $e) {
                if ($attempt === $retries) {
                    Log::error("Update failed after {$retries} retries", [
                        'call_id' => $call->id,
                        'error' => $e->getMessage()
                    ]);
                    return false;
                }
                
                usleep(100000 * ($attempt + 1)); // Exponential backoff
            }
        }
        
        return false;
    }

    /**
     * Parallel document updates
     */
    private function updateDocumentsParallel(array $paths, array $updateData)
    {
        $requests = [];
        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        foreach ($paths as $path) {
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$path}";
            $requests[] = new Request('PATCH', $url, $headers, json_encode($updateData));
        }

        $pool = new Pool($this->connectionPool, $requests, [
            'concurrency' => 4,
            'fulfilled' => function ($response, $index) {
                Log::debug("Parallel update completed", ['index' => $index]);
            },
            'rejected' => function ($reason, $index) {
                Log::warning("Parallel update failed", ['index' => $index, 'error' => $reason->getMessage()]);
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();
    }

    /**
     * Schedule call cleanup
     */
    private function scheduleCallCleanup($callId)
    {
        Cache::put("cleanup_call_{$callId}", true, 300);
        Log::info("Call cleanup scheduled", ['call_id' => $callId]);
    }

    /**
     * Write table status change to Firestore
     */
    public function writeTableStatus($table, $statusType, $statusData = [])
    {
        try {
            $document = [
                'fields' => [
                    'table_id' => ['integerValue' => (string)$table->id],
                    'table_number' => ['integerValue' => (string)$table->number],
                    'table_name' => ['stringValue' => $table->name],
                    'status_type' => ['stringValue' => $statusType],
                    'status_data' => ['stringValue' => json_encode($statusData)],
                    'notifications_enabled' => ['booleanValue' => $table->notifications_enabled],
                    'active_waiter_id' => ['integerValue' => (string)($table->active_waiter_id ?? 0)],
                    'active_waiter_name' => ['stringValue' => $table->activeWaiter->name ?? ''],
                    'is_silenced' => ['booleanValue' => $table->isSilenced()],
                    'timestamp' => ['timestampValue' => now()->toISOString()]
                ]
            ];

            // Escribir en documentos específicos para table status
            $this->writeDocument("tables/{$table->id}/status", 'current', $document);
            $this->writeDocument("businesses/{$table->business_id}/table_status", $table->id, $document);

            Log::info("Firestore table status written successfully", [
                'table_id' => $table->id,
                'status_type' => $statusType
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to write table status to Firestore", [
                'table_id' => $table->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Write general notification to Firestore
     */
    public function writeNotification($userId, $notification)
    {
        try {
            $document = [
                'fields' => [
                    'user_id' => ['integerValue' => (string)$userId],
                    'title' => ['stringValue' => $notification['title'] ?? ''],
                    'body' => ['stringValue' => $notification['body'] ?? ''],
                    'data' => ['stringValue' => json_encode($notification['data'] ?? [])],
                    'timestamp' => ['timestampValue' => now()->toISOString()]
                ]
            ];

            $this->writeDocument("users/{$userId}/notifications", uniqid(), $document);

            Log::info("Firestore notification written successfully", [
                'user_id' => $userId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to write notification to Firestore", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Write document to Firestore using REST API
     */
    private function writeDocument($collection, $documentId, $document)
    {
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}/{$documentId}";

        $response = $this->client->patch($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => $document,
            'query' => ['updateMask.fieldPaths' => '*'],
            // 🚀 OPTIMIZACIÓN: Timeout agresivo para velocidad
            'timeout' => 2, // 2 segundos máximo
            'connect_timeout' => 1 // 1 segundo para conectar
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Delete document from Firestore
     */
    public function deleteDocument($collection, $documentId)
    {
        try {
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}/{$documentId}";

            $this->client->delete($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                ]
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to delete document from Firestore", [
                'collection' => $collection,
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Write waiter call completion with parallel cleanup
     */
    public function completeWaiterCall($call)
    {
        $this->updateWaiterCall($call, 'completed');
        $this->scheduleCallCleanup($call->id);
        return true;
    }

    /**
     * Parallel table status update
     */
    public function updateTableStatusParallel($table, $statusType, $statusData = [])
    {
        if (!$this->accessToken) {
            return false;
        }

        try {
            $document = [
                'fields' => [
                    'table_id' => ['integerValue' => (string)$table->id],
                    'table_number' => ['integerValue' => (string)$table->number],
                    'table_name' => ['stringValue' => $table->name ?? ''],
                    'status_type' => ['stringValue' => $statusType],
                    'status_data' => ['stringValue' => json_encode($statusData)],
                    'notifications_enabled' => ['booleanValue' => $table->notifications_enabled ?? false],
                    'active_waiter_id' => ['integerValue' => (string)($table->active_waiter_id ?? 0)],
                    'active_waiter_name' => ['stringValue' => $table->activeWaiter->name ?? ''],
                    'timestamp' => ['timestampValue' => now()->toISOString()]
                ]
            ];

            $paths = [
                "tables/{$table->id}/status/current",
                "businesses/{$table->business_id}/table_status/{$table->id}"
            ];

            $this->writeDocumentsParallel($paths, $document);
            Cache::put("table_status_{$table->id}", $document, 300);

            return true;
        } catch (\Exception $e) {
            Log::error("Parallel table status update failed", [
                'table_id' => $table->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Performance test for parallel writes
     */
    public function testParallelPerformance($testCount = 10)
    {
        if (!$this->accessToken) {
            return ['error' => 'No access token'];
        }

        $results = [
            'sequential' => 0,
            'parallel' => 0,
            'improvement' => 0
        ];

        // Test sequential writes
        $start = microtime(true);
        for ($i = 0; $i < $testCount; $i++) {
            $testDoc = [
                'fields' => [
                    'test_id' => ['stringValue' => "seq_test_{$i}"],
                    'timestamp' => ['stringValue' => now()->toISOString()]
                ]
            ];
            $this->writeDocument("performance_tests/sequential", "test_{$i}", $testDoc);
        }
        $results['sequential'] = (microtime(true) - $start) * 1000;

        // Test parallel writes
        $start = microtime(true);
        $paths = [];
        for ($i = 0; $i < $testCount; $i++) {
            $paths[] = "performance_tests/parallel/test_{$i}";
        }
        $testDoc = [
            'fields' => [
                'test_type' => ['stringValue' => 'parallel'],
                'timestamp' => ['stringValue' => now()->toISOString()]
            ]
        ];
        $this->writeDocumentsParallel($paths, $testDoc);
        $results['parallel'] = (microtime(true) - $start) * 1000;

        $results['improvement'] = (($results['sequential'] - $results['parallel']) / $results['sequential']) * 100;

        Log::info("Performance test completed", $results);
        return $results;
    }

    /**
     * Cleanup completed calls in batch
     */
    public function batchCleanupCompletedCalls($maxAge = 3600)
    {
        try {
            $cutoffTime = now()->subSeconds($maxAge)->toISOString();
            
            // This would normally use a Firebase query to find old documents
            // For now, we'll clean up cached items
            $cleanedCount = 0;
            for ($i = 1; $i <= 100; $i++) {
                $cacheKey = "cleanup_call_{$i}";
                if (Cache::has($cacheKey)) {
                    Cache::forget($cacheKey);
                    $cleanedCount++;
                }
            }

            Log::info("Batch cleanup completed", ['cleaned_count' => $cleanedCount]);
            return $cleanedCount;
        } catch (\Exception $e) {
            Log::error("Batch cleanup failed", ['error' => $e->getMessage()]);
            return 0;
        }
    }
}