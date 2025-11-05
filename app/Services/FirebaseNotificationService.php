<?php

namespace App\Services;

use App\Services\Concerns\FirebaseHttpClient;
use App\Services\Concerns\FirebaseIndexManager;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\DeviceToken;

/**
 * FirebaseNotificationService - Servicio base para FCM + Firebase RTDB
 *
 * Responsabilidades:
 * 1. Envío de notificaciones FCM con access token auto-refresh
 * 2. Batch processing paralelo con Guzzle Pool
 * 3. Manejo automático de tokens inválidos (404/410)
 * 4. Operaciones Firebase Realtime Database (writeToPath, deleteFromPath)
 * 
 * V2: Usa traits FirebaseHttpClient y FirebaseIndexManager
 */
class FirebaseNotificationService
{
    use FirebaseHttpClient, FirebaseIndexManager;

    private Client $client;
    private string $projectId;
    private ?string $accessToken = null;
    private ?\Carbon\Carbon $tokenExpiresAt = null;
    private TokenManager $tokenManager;

    public function __construct(TokenManager $tokenManager)
    {
        $this->client = new Client();
        $this->projectId = config('services.firebase.project_id');
        $this->tokenManager = $tokenManager;

        // Inicializar token si Firebase está habilitado
        if (config('services.firebase.enabled', true)) {
            try {
                $this->accessToken = $this->getAccessToken();
                $this->tokenExpiresAt = now()->addMinutes(50);

                Log::info('FirebaseNotificationService initialized', [
                    'project_id' => $this->projectId,
                    'token_expires_at' => $this->tokenExpiresAt->toISOString()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to initialize Firebase service', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    // ========================================================================
    // FCM - FIREBASE CLOUD MESSAGING
    // ========================================================================

    /**
     * Enviar notificación a un usuario específico
     * Maneja automáticamente separación de plataformas (web vs mobile)
     *
     * @param int $userId
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string $priority 'normal' | 'high'
     * @return array Resultados del envío
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = [], string $priority = 'normal'): array
    {
        try {
            // Obtener tokens válidos del usuario
            $tokens = $this->tokenManager->getUserTokens($userId);

            if (empty($tokens)) {
                Log::warning('No tokens found for user', ['user_id' => $userId]);
                return ['sent' => 0, 'total' => 0, 'results' => []];
            }

            // Agrupar por plataforma
            $grouped = $this->tokenManager->groupByPlatform($tokens);

            $results = [];
            $sent = 0;

            // Web: Data-only (service worker maneja UI)
            foreach ($grouped['web'] as $token) {
                try {
                    $message = $this->buildDataOnlyMessage($token, $title, $body, $data);
                    $result = $this->sendMessage($message);
                    $results[] = ['token' => substr($token, 0, 15) . '...', 'platform' => 'web', 'success' => true];
                    $sent++;
                } catch (\Exception $e) {
                    $results[] = ['token' => substr($token, 0, 15) . '...', 'platform' => 'web', 'success' => false, 'error' => $e->getMessage()];
                }
            }

            // Mobile (Android/iOS): notification + data
            $mobileTokens = array_merge($grouped['android'], $grouped['ios']);
            foreach ($mobileTokens as $token) {
                try {
                    $message = $this->buildNotificationMessage($token, $title, $body, $data, $priority);
                    $result = $this->sendMessage($message);
                    $results[] = ['token' => substr($token, 0, 15) . '...', 'platform' => 'mobile', 'success' => true];
                    $sent++;
                } catch (\Exception $e) {
                    $results[] = ['token' => substr($token, 0, 15) . '...', 'platform' => 'mobile', 'success' => false, 'error' => $e->getMessage()];
                }
            }

            Log::info('Notifications sent to user', [
                'user_id' => $userId,
                'sent' => $sent,
                'total' => count($tokens)
            ]);

            return [
                'sent' => $sent,
                'total' => count($tokens),
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send notifications to user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return ['sent' => 0, 'total' => 0, 'results' => []];
        }
    }

    /**
     * Enviar notificaciones a múltiples usuarios
     * FIX CRÍTICO: Usa batch paralelo con Guzzle Pool
     *
     * @param array $userIds
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendToMultipleUsers(array $userIds, string $title, string $body, array $data = []): array
    {
        // Obtener todos los tokens de todos los usuarios
        $allTokens = [];
        foreach ($userIds as $userId) {
            $tokens = $this->tokenManager->getUserTokens($userId);
            $allTokens = array_merge($allTokens, $tokens);
        }

        if (empty($allTokens)) {
            return ['sent' => 0, 'total' => 0, 'results' => []];
        }

        // Usar batch paralelo
        return $this->sendBatch($allTokens, $title, $body, $data);
    }

    /**
     * Enviar notificaciones en batch (PARALELO con Guzzle Pool)
     * FIX CRÍTICO: 10 requests paralelos en lugar de secuenciales
     *
     * @param array $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string $priority
     * @return array
     */
    public function sendBatch(array $tokens, string $title, string $body, array $data = [], string $priority = 'normal'): array
    {
        if (empty($tokens)) {
            return ['sent' => 0, 'total' => 0, 'results' => []];
        }

        try {
            $results = [];
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
            $accessToken = $this->getValidAccessToken();

            if (!$accessToken) {
                Log::error('Cannot send batch: no access token');
                return ['sent' => 0, 'total' => count($tokens), 'results' => []];
            }

            // Agrupar tokens por plataforma
            $grouped = $this->tokenManager->groupByPlatform($tokens);

            // Crear requests asíncronos
            $requests = function () use ($url, $accessToken, $title, $body, $data, $priority, $grouped) {
                // Requests para web (data-only)
                foreach ($grouped['web'] as $token) {
                    $message = $this->buildDataOnlyMessage($token, $title, $body, $data);
                    yield function() use ($url, $accessToken, $message) {
                        return $this->client->postAsync($url, [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $accessToken,
                                'Content-Type' => 'application/json',
                            ],
                            'json' => $message
                        ]);
                    };
                }

                // Requests para mobile (notification + data)
                $mobileTokens = array_merge($grouped['android'], $grouped['ios']);
                foreach ($mobileTokens as $token) {
                    $message = $this->buildNotificationMessage($token, $title, $body, $data, $priority);
                    yield function() use ($url, $accessToken, $message) {
                        return $this->client->postAsync($url, [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $accessToken,
                                'Content-Type' => 'application/json',
                            ],
                            'json' => $message
                        ]);
                    };
                }
            };

            // Pool de 10 requests paralelos
            $pool = new Pool($this->client, $requests(), [
                'concurrency' => 10,
                'fulfilled' => function ($response, $index) use (&$results, $tokens) {
                    $tokenArray = array_values($tokens);
                    $results[] = [
                        'token' => substr($tokenArray[$index] ?? 'unknown', 0, 15) . '...',
                        'success' => true
                    ];
                },
                'rejected' => function ($reason, $index) use (&$results, $tokens) {
                    $tokenArray = array_values($tokens);
                    $errorMessage = $reason instanceof \Exception ? $reason->getMessage() : 'Unknown error';

                    $results[] = [
                        'token' => substr($tokenArray[$index] ?? 'unknown', 0, 15) . '...',
                        'success' => false,
                        'error' => $errorMessage
                    ];

                    // Manejar errores FCM (404/410 = token inválido)
                    if ($reason instanceof RequestException && $reason->hasResponse()) {
                        $statusCode = $reason->getResponse()->getStatusCode();
                        if (in_array($statusCode, [404, 410])) {
                            $token = $tokenArray[$index] ?? null;
                            if ($token) {
                                $this->tokenManager->markTokenAsInvalid($token);
                            }
                        }
                    }
                },
            ]);

            // Ejecutar todas las requests en paralelo
            $pool->promise()->wait();

            $sent = collect($results)->where('success', true)->count();

            Log::info('Batch notifications sent', [
                'total' => count($tokens),
                'sent' => $sent,
                'failed' => count($tokens) - $sent
            ]);

            return [
                'sent' => $sent,
                'total' => count($tokens),
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send batch notifications', [
                'error' => $e->getMessage(),
                'tokens_count' => count($tokens)
            ]);
            return ['sent' => 0, 'total' => count($tokens), 'results' => []];
        }
    }

    // ========================================================================
    // FIREBASE REALTIME DATABASE
    // ========================================================================

    /**
     * Escribir datos en Firebase Realtime Database (delegado al trait)
     *
     * @param string $path Ruta en Firebase (ej: "active_calls/123")
     * @param array $data Datos a escribir
     * @return bool
     */
    public function writeToPath(string $path, array $data): bool
    {
        return $this->writeToFirebase($path, $data);
    }

    /**
     * Eliminar datos de Firebase Realtime Database (delegado al trait)
     *
     * @param string $path Ruta en Firebase
     * @return bool
     */
    public function deleteFromPath(string $path): bool
    {
        return $this->deleteFromFirebase($path);
    }

    /**
     * Leer datos de Firebase Realtime Database
     *
     * @param string $path
     * @return array|null
     */
    public function readFromPath(string $path): ?array
    {
        try {
            $url = "{$this->baseUrl}/{$path}.json";
            $response = Http::timeout(3)->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Firebase RTDB read error', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    // ========================================================================
    // MÉTODOS PRIVADOS - HELPERS
    // ========================================================================

    /**
     * Obtener access token válido con auto-refresh
     * FIX CRÍTICO: Renueva automáticamente cada 50 minutos
     *
     * @return string|null
     */
    private function getValidAccessToken(): ?string
    {
        // Si no hay token o ya expiró, renovar
        if (!$this->accessToken || !$this->tokenExpiresAt || now()->isAfter($this->tokenExpiresAt)) {
            Log::info('Access token expired or missing, refreshing...', [
                'had_token' => $this->accessToken ? 'yes' : 'no',
                'expires_at' => $this->tokenExpiresAt?->toISOString()
            ]);

            $this->accessToken = $this->getAccessToken();
            $this->tokenExpiresAt = now()->addMinutes(50); // Renovar antes de que expire (60min)
        }

        return $this->accessToken;
    }

    /**
     * Obtener OAuth 2.0 access token de Firebase
     *
     * @return string|null
     */
    private function getAccessToken(): ?string
    {
        $serviceAccountPath = config('services.firebase.service_account_path');

        // Normalizar path y buscar archivo
        $normalized = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $serviceAccountPath);
        $fallbackPath = storage_path('app' . DIRECTORY_SEPARATOR . 'firebase' . DIRECTORY_SEPARATOR . 'firebase.json');
        $candidatePath = $normalized && file_exists($normalized) ? $normalized : $fallbackPath;

        if (!file_exists($candidatePath)) {
            Log::warning('Firebase service account file not found', [
                'path_env' => $serviceAccountPath,
                'fallback' => $fallbackPath
            ]);
            return null;
        }

        $serviceAccount = json_decode(file_get_contents($candidatePath), true);

        // Crear JWT
        $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
        $now = time();
        $payload = json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
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
            Log::error('Failed to get Firebase access token', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Construir mensaje de notificación completo (mobile)
     *
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string $priority
     * @return array
     */
    private function buildNotificationMessage(string $token, string $title, string $body, array $data, string $priority): array
    {
        $isHighPriority = $priority === 'high';
        $formattedData = $this->formatDataForFcm($data);

        // Detectar canal urgente para llamadas de mesa
        $channelId = ($data['type'] ?? null) === 'waiter_call' ? 'waiter_urgent' : 'waiter_normal';

        return [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $formattedData,
                'android' => [
                    'priority' => $isHighPriority ? 'high' : 'normal',
                    'notification' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'sound' => 'default',
                        'channel_id' => $channelId,
                    ],
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => $isHighPriority ? '10' : '5',
                    ],
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ],
            ]
        ];
    }

    /**
     * Construir mensaje data-only (web)
     *
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    private function buildDataOnlyMessage(string $token, string $title, string $body, array $data): array
    {
        $formattedData = $this->formatDataForFcm(array_merge($data, [
            'title' => $title,
            'message' => $body,
            'body' => $body
        ]));

        return [
            'message' => [
                'token' => $token,
                // Web necesita notification para que llegue en background
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => $formattedData,
                'webpush' => [
                    'headers' => [
                        'Urgency' => 'high'
                    ],
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'icon' => '/logo192.png',
                        'badge' => '/badge-72x72.png',
                    ],
                ],
            ]
        ];
    }

    /**
     * Formatear data para FCM (todos los valores deben ser strings)
     *
     * @param array $data
     * @return array
     */
    private function formatDataForFcm(array $data): array
    {
        $formatted = [];
        foreach ($data as $key => $value) {
            $formatted[$key] = is_array($value) || is_object($value)
                ? json_encode($value)
                : (string)$value;
        }
        return $formatted;
    }

    /**
     * Enviar mensaje a FCM API
     *
     * @param array $message
     * @return array
     * @throws \Exception
     */
    private function sendMessage(array $message): array
    {
        $accessToken = $this->getValidAccessToken();

        if (!$accessToken) {
            throw new \Exception('Firebase access token not available');
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $message,
            ]);

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            // FIX CRÍTICO: Manejar tokens inválidos (404/410)
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();

                if (in_array($statusCode, [404, 410])) {
                    $token = $message['message']['token'] ?? null;
                    if ($token) {
                        $this->tokenManager->markTokenAsInvalid($token);
                    }
                }
            }

            Log::error('FCM notification failed', [
                'error' => $e->getMessage(),
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
            ]);

            throw $e;
        }
    }
}
