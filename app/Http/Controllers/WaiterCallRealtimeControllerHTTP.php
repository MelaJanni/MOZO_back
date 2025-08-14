<?php

namespace App\Http\Controllers;

use App\Models\WaiterCall;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WaiterCallRealtimeControllerHTTP extends Controller
{
    protected $projectId;
    protected $accessToken;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', 'mozoqr-7d32c');
        $this->accessToken = $this->getAccessToken();
    }

    /**
     * 🚀 CREAR LLAMADA INSTANTÁNEA - SOLUCIÓN AL DELAY DE 20 SEGUNDOS
     */
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

            // 🚀 ESCRITURA INSTANTÁNEA A FIRESTORE VIA HTTP
            $this->writeToFirestoreInstantHTTP($firestoreData);

            $firestoreTime = (microtime(true) - $startTime) * 1000;
            Log::info("Firestore HTTP write completed in {$firestoreTime}ms");

            // Background tasks (DB + FCM)
            dispatch(function() use ($tableModel, $firestoreData, $request) {
                WaiterCall::create([
                    'id' => $firestoreData['id'],
                    'table_id' => $tableModel->id,
                    'waiter_id' => $tableModel->activeWaiter->user_id,
                    'business_id' => $tableModel->business_id,
                    'restaurant_id' => $tableModel->restaurant_id ?? null,
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

    /**
     * 🚀 ESCRIBIR A FIRESTORE VIA HTTP (SIN DEPENDENCIAS GRPC)
     */
    protected function writeToFirestoreInstantHTTP($data)
    {
        if (!$this->accessToken) {
            Log::warning('No access token available for Firestore');
            return false;
        }

        try {
            $baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ];

            // Convertir datos al formato Firestore
            $firestoreDocument = [
                'fields' => $this->convertToFirestoreFields($data)
            ];

            // 🚀 ESCRITURAS PARALELAS usando HTTP
            $writes = [
                // 1. Documento principal de la mesa
                Http::timeout(3)->withHeaders($headers)
                    ->patchAsync("{$baseUrl}/tables/{$data['table_id']}/waiter_calls/{$data['id']}", $firestoreDocument),

                // 2. Documento del mozo
                Http::timeout(3)->withHeaders($headers)
                    ->patchAsync("{$baseUrl}/waiters/{$data['waiter_id']}/calls/{$data['id']}", $firestoreDocument),

                // 3. Documento de llamadas activas
                Http::timeout(3)->withHeaders($headers)
                    ->patchAsync("{$baseUrl}/active_calls/{$data['id']}", [
                        'fields' => $this->convertToFirestoreFields(array_merge($data, [
                            'expires_at' => now()->addHours(2)->toIso8601String()
                        ]))
                    ]),

                // 4. Estado de la mesa
                Http::timeout(3)->withHeaders($headers)
                    ->patchAsync("{$baseUrl}/tables/{$data['table_id']}/status/current", [
                        'fields' => $this->convertToFirestoreFields([
                            'has_active_call' => true,
                            'last_call_id' => $data['id'],
                            'last_call_time' => $data['timestamp'],
                            'status' => 'calling_waiter'
                        ])
                    ])
            ];

            // Esperar todas las escrituras
            $results = [];
            foreach ($writes as $write) {
                $results[] = $write->wait();
            }

            Log::info("Firestore HTTP batch write successful for call {$data['id']}");
            return true;

        } catch (\Exception $e) {
            Log::error('Firestore HTTP write failed: ' . $e->getMessage());
            
            // Fallback: solo escribir en active_calls
            try {
                Http::timeout(5)->withHeaders([
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ])->patch(
                    "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/active_calls/{$data['id']}",
                    ['fields' => $this->convertToFirestoreFields($data)]
                );
                Log::info("Fallback Firestore write successful");
                return true;
            } catch (\Exception $fallbackError) {
                Log::error('Fallback Firestore write also failed: ' . $fallbackError->getMessage());
                return false;
            }
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
     * Obtener access token para Firebase
     */
    private function getAccessToken()
    {
        $serviceAccountPath = storage_path('app/firebase/mozoqr-7d32c-firebase-adminsdk-fbsvc-0a90bdb0a0.json');
        
        if (!file_exists($serviceAccountPath)) {
            Log::warning("Firebase service account file not found at: {$serviceAccountPath}");
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
     * Test de latencia HTTP
     */
    public function testLatency(Request $request)
    {
        $startTime = microtime(true);

        try {
            $testId = 'test_' . uniqid();
            $testData = [
                'id' => $testId,
                'timestamp' => now()->toIso8601String(),
                'test' => true
            ];

            if (!$this->accessToken) {
                return response()->json([
                    'error' => 'No access token available',
                    'firestore_configured' => false
                ]);
            }

            // Write test
            $response = Http::timeout(5)->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->patch(
                "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/latency_tests/{$testId}",
                ['fields' => $this->convertToFirestoreFields($testData)]
            );

            $writeTime = (microtime(true) - $startTime) * 1000;

            // Read test
            $readStart = microtime(true);
            $readResponse = Http::timeout(5)->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
            ])->get(
                "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/latency_tests/{$testId}"
            );
            $readTime = (microtime(true) - $readStart) * 1000;

            // Cleanup
            Http::timeout(5)->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
            ])->delete(
                "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/latency_tests/{$testId}"
            );

            return response()->json([
                'firestore_configured' => true,
                'write_latency_ms' => round($writeTime, 2),
                'read_latency_ms' => round($readTime, 2),
                'total_ms' => round($writeTime + $readTime, 2),
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'firestore_configured' => false,
                'status' => 'error'
            ], 500);
        }
    }

    protected function sendFCMNotification($table, $callData)
    {
        try {
            if (class_exists('App\Services\FirebaseService')) {
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
            }
        } catch (\Exception $e) {
            Log::error('FCM notification failed: ' . $e->getMessage());
        }
    }
}