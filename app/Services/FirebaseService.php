<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\DeviceToken;
use App\Models\User;
use App\Notifications\FcmDatabaseNotification;

class FirebaseService
{
    private $client;
    private $projectId;
    private $accessToken;

    public function __construct()
    {
        $this->client = new Client();
        $this->projectId = config('services.firebase.project_id');
        
        try {
            $this->accessToken = $this->getAccessToken();
            Log::info('Firebase service initialized successfully', [
                'project_id' => $this->projectId,
                'access_token' => $this->accessToken ? 'available' : 'not available',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase service: ' . $e->getMessage());
            $this->accessToken = null;
        }
    }

    /**
     * Get OAuth 2.0 access token for Firebase
     */
    private function getAccessToken()
    {
        $serviceAccountPath = config('services.firebase.service_account_path');

        if (!file_exists($serviceAccountPath)) {
            Log::error('Firebase service account file not found', [
                'path' => $serviceAccountPath,
                'storage_path' => storage_path(),
                'app_path' => app_path(),
            ]);
            throw new \Exception("Firebase service account file not found at: {$serviceAccountPath}");
        }

        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
        
        // Create JWT
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
            Log::error('Failed to get Firebase access token: ' . $e->getMessage());
            throw new \Exception('Failed to authenticate with Firebase');
        }
    }

    /**
     * Send notification to specific device token
     */
    public function sendToDevice($token, $title, $body, $data = [], $priority = 'normal')
    {
        // FCM HTTP v1 API requiere que todos los valores en 'data' sean strings
        $formattedData = (object)[];  // Siempre debe ser un objeto, nunca array
        if (!empty($data) && is_array($data)) {
            foreach ($data as $key => $value) {
                $formattedData->{$key} = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
            }
        }

        // 🚀 OPTIMIZACIÓN: Configuración de prioridad para delivery inmediato
        $isHighPriority = $priority === 'high';
        
    // Detectar si es notificación unified para forzar canal estándar
    $isUnified = isset($data['type']) && $data['type'] === 'unified';
    $forcedChannel = $isUnified ? 'mozo_waiter' : null;

    $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $formattedData,
                // Web push configuration for browsers (ensures proper delivery when app is in background)
                'webpush' => [
                    'headers' => [
                        'Urgency' => $isHighPriority ? 'high' : 'normal'
                    ],
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'icon' => '/logo192.png',
                        'badge' => '/badge-72x72.png'
                    ],
                    'fcm_options' => [
                        // Link opened when user clicks the notification in browser
                        'link' => rtrim(config('app.url', '/'), '/') . '/'
                    ]
                ],
                'android' => [
                    'priority' => $isHighPriority ? 'high' : 'normal',
                    // collapse_key y ttl ayudan a evitar duplicados y descartar mensajes viejos
                    'collapse_key' => isset($data['call_id']) ? 'call_' . $data['call_id'] : (isset($data['notification_id']) ? $data['notification_id'] : null),
                    'ttl' => '60s',
                    'notification' => [
                        'priority' => $isHighPriority ? 'high' : 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'sound' => 'default',
                        // Canal unificado para type=unified; mantiene compatibilidad waiter_* para resto
                        'channel_id' => $forcedChannel ?? (isset($data['channel_id']) ? $data['channel_id'] : ($isHighPriority ? 'waiter_urgent' : 'waiter_normal')),
                        'tag' => isset($data['notification_id']) ? $data['notification_id'] : (isset($data['call_id']) ? $data['call_id'] : null),
                        'notification_id' => isset($data['notification_id']) ? $data['notification_id'] : (isset($data['call_id']) ? $data['call_id'] : null)
                    ],
                    'data' => [
                        'priority' => $isHighPriority ? 'high' : 'normal',
                        'notification_id' => isset($data['notification_id']) ? $data['notification_id'] : (isset($data['call_id']) ? $data['call_id'] : null),
                        'channel_id' => $forcedChannel ?? (isset($data['channel_id']) ? $data['channel_id'] : null)
                    ]
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => $isHighPriority ? '10' : '5',
                        'apns-push-type' => 'alert'
                    ],
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'sound' => 'default',
                            'badge' => 1,
                            'content-available' => $isHighPriority ? 1 : 0,
                            'interruption-level' => $isHighPriority ? 'critical' : 'active'
                        ],
                    ],
                ],
            ]
        ];

        return $this->sendMessage($message);
    }

    /**
     * Send notification to multiple device tokens using HTTP v1 API
     */
    public function sendToMultipleDevices($tokens, $title, $body, $data = [], $priority = 'normal')
    {
        $results = [];
        
        // HTTP v1 API no soporta envío a múltiples tokens en una sola request
        // Necesitamos enviar individualmente o usar sendToAllUsers con topic
        foreach ($tokens as $token) {
            try {
                $result = $this->sendToDevice($token, $title, $body, $data, $priority);
                $results[] = ['token' => $token, 'success' => true, 'result' => $result];
            } catch (\Exception $e) {
                $results[] = ['token' => $token, 'success' => false, 'error' => $e->getMessage()];
            }
        }
        
        return $results;
    }

    /**
     * Send notification to specific user
     */
    public function sendToUser($userId, $title, $body, $data = [], $priority = 'normal')
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }
        // Filtrado por rol: evitar enviar notificaciones de waiter a usuarios que no sean mozos
        if (isset($data['only_waiters']) && $data['only_waiters'] === true) {
            if (!$user->isWaiter()) {
                Log::warning("Skipping notification: user {$userId} is not a waiter");
                return false;
            }
        }

        // Si se especifica platform en $data, filtrar tokens por plataforma
        if (!empty($data['platform'])) {
            $deviceTokens = DeviceToken::where('user_id', $userId)
                ->where('platform', $data['platform'])
                ->pluck('token')
                ->toArray();
        } else {
            $deviceTokens = DeviceToken::where('user_id', $userId)->pluck('token')->toArray();
        }
        
        if (empty($deviceTokens)) {
            Log::warning("No device tokens found for user ID: {$userId}");
            return false;
        }

        // 🔥 ANDROID ESPECÍFICO: Configurar notification_id único para permitir cancelación
        if (isset($data['type']) && $data['type'] === 'waiter_call') {
            $data['notification_id'] = 'waiter_call_' . ($data['call_id'] ?? uniqid());
            $data['channel_id'] = $priority === 'high' ? 'waiter_urgent' : 'waiter_normal';
        }

        // 🚀 OPTIMIZACIÓN 1: Solo FCM inmediato, DB notification solo si es normal priority
        $results = [];
        foreach ($deviceTokens as $token) {
            $results[] = $this->sendToDevice($token, $title, $body, $data, $priority);
        }

        // 🚀 OPTIMIZACIÓN 2: Solo guardar en BD si NO es high priority (reduce latencia)
        if ($priority !== 'high') {
            $user->notify(new FcmDatabaseNotification($title, $body, $data));
        }

        return $results;
    }

    /**
     * Send notification to all users
     */
    public function sendToAllUsers($title, $body, $data = [], $priority = 'normal')
    {
        $deviceTokens = DeviceToken::pluck('token')->toArray();
        
        if (empty($deviceTokens)) {
            Log::warning('No device tokens found for broadcast');
            return false;
        }

        // 1. Enviar FCM push notification
        // FCM allows maximum 1000 tokens per batch
        $batches = array_chunk($deviceTokens, 1000);
        $results = [];

        foreach ($batches as $batch) {
            $results[] = $this->sendToMultipleDevices($batch, $title, $body, $data, $priority);
        }

        // 2. Guardar notificación en BD solo si NO es high priority
        if ($priority !== 'high') {
            $userIds = DeviceToken::distinct('user_id')->pluck('user_id');
            $users = User::whereIn('id', $userIds)->get();
            
            Notification::send($users, new FcmDatabaseNotification($title, $body, $data));
        }

        return $results;
    }

    /**
     * Send message to FCM HTTP v1 API
     */
    private function sendMessage($message)
    {
        if (!$this->accessToken) {
            Log::error('Firebase access token not available, cannot send message');
            throw new \Exception('Firebase service not properly initialized');
        }
        
        try {
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ];

            $response = $this->client->post($url, [
                'headers' => $headers,
                'json' => $message,
            ]);

            $result = json_decode($response->getBody(), true);
            Log::info('FCM notification sent successfully', $result);
            
            return $result;
        } catch (RequestException $e) {
            Log::error('Failed to send FCM notification', [
                'error' => $e->getMessage(),
                'response_body' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                'url' => $url,
                'headers' => $headers,
                'message' => $message
            ]);
            throw new \Exception('Failed to send notification: ' . $e->getMessage());
        }
    }

    /**
     * Subscribe token to topic
     */
    public function subscribeToTopic($tokens, $topic)
    {
        try {
            $response = $this->client->post('https://iid.googleapis.com/iid/v1:batchAdd', [
                'headers' => [
                    'Authorization' => 'key=' . config('services.firebase.server_key'),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'to' => "/topics/{$topic}",
                    'registration_tokens' => is_array($tokens) ? $tokens : [$tokens],
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Failed to subscribe to topic: ' . $e->getMessage());
            throw new \Exception('Failed to subscribe to topic');
        }
    }

    /**
     * Send notification to topic
     */
    public function sendToTopic($topic, $title, $body, $data = [], $priority = 'normal')
    {
        // FCM HTTP v1 API requiere que todos los valores en 'data' sean strings
        $formattedData = (object)[];  // Siempre debe ser un objeto, nunca array
        if (!empty($data) && is_array($data)) {
            foreach ($data as $key => $value) {
                $formattedData->{$key} = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
            }
        }

        $isHighPriority = $priority === 'high';
        
        $message = [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $formattedData,
                'webpush' => [
                    'headers' => [
                        'Urgency' => $isHighPriority ? 'high' : 'normal'
                    ],
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'icon' => '/logo192.png',
                        'badge' => '/badge-72x72.png'
                    ],
                    'fcm_options' => [
                        'link' => rtrim(config('app.url', '/'), '/') . '/'
                    ]
                ],
                'android' => [
                    'priority' => $isHighPriority ? 'high' : 'normal',
                    'notification' => [
                        'priority' => $isHighPriority ? 'high' : 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'sound' => 'default',
                        'channel_id' => $isHighPriority ? 'waiter_urgent' : 'waiter_normal',
                    ],
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => $isHighPriority ? '10' : '5'
                    ],
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'sound' => 'default',
                            'badge' => 1,
                            'content-available' => $isHighPriority ? 1 : 0
                        ],
                    ],
                ],
            ]
        ];

        return $this->sendMessage($message);
    }

    /**
     * 🔥 CANCELAR NOTIFICACIÓN PUSH EN ANDROID
     * Envía una notificación "silenciosa" para cancelar la anterior
     */
    public function cancelNotification($userId, $notificationId, $callId = null)
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                throw new \Exception('User not found');
            }

            // Obtener tokens Android únicamente
            $androidTokens = DeviceToken::where('user_id', $userId)
                ->where('platform', 'android')
                ->pluck('token')
                ->toArray();
            
            if (empty($androidTokens)) {
                Log::info("No Android tokens found for user {$userId} to cancel notification");
                return false;
            }

            // Data para cancelar la notificación anterior
            $cancelData = [
                'action' => 'cancel_notification',
                'notification_id' => $notificationId,
                'call_id' => (string)($callId ?? ''),
                'cancel_previous' => 'true'
            ];

            // Enviar mensaje de datos únicamente (sin notification payload)
            foreach ($androidTokens as $token) {
                $message = [
                    'message' => [
                        'token' => $token,
                        'data' => array_map('strval', $cancelData), // FCM requiere strings
                        'android' => [
                            'priority' => 'high',
                            'data' => array_map('strval', $cancelData)
                        ]
                    ]
                ];

                $this->sendMessage($message);
            }

            Log::info("Cancel notification sent", [
                'user_id' => $userId,
                'notification_id' => $notificationId,
                'call_id' => $callId,
                'android_tokens_count' => count($androidTokens)
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send cancel notification', [
                'user_id' => $userId,
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 🔥 REFRESH TOKEN DEL USUARIO
     * Para manejar tokens expirados o perdidos
     */
    public function refreshUserToken($userId, $newToken, $platform = 'android')
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                throw new \Exception('User not found');
            }

            // Eliminar tokens antiguos del mismo usuario y plataforma
            DeviceToken::where('user_id', $userId)
                ->where('platform', $platform)
                ->delete();

            // Crear nuevo token
            DeviceToken::create([
                'user_id' => $userId,
                'token' => $newToken,
                'platform' => $platform,
                'expires_at' => now()->addDays(60) // 60 días de validez
            ]);

            Log::info("User token refreshed", [
                'user_id' => $userId,
                'platform' => $platform,
                'token_preview' => substr($newToken, 0, 20) . '...'
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to refresh user token', [
                'user_id' => $userId,
                'platform' => $platform,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 🚀 UNIFIED: Enviar notificación a múltiples tokens usando HTTP v1.
     * WEB: Solo data (para que se ejecute onBackgroundMessage)
     * MÓVIL: notification + data (para mejor UX móvil)
     */
    public function sendUnifiedNotificationToTokens(array $tokens, int $tableNumber, string $message, array $extraData = []): array
    {
        if (empty($tokens)) {
            Log::info('Unified notification skipped: empty token list', [
                'table_number' => $tableNumber
            ]);
            return [
                'sent' => 0,
                'total' => 0,
                'results' => []
            ];
        }

        $title = "🔔 Mesa {$tableNumber}";
        $body = $message ?: 'Nueva llamada';
        
        // Data base obligatoria
        $baseData = [
            'type' => 'unified',
            'source' => 'unified',
            'title' => $title,
            'message' => $body,
            'table_number' => (string)$tableNumber,
            'timestamp' => (string) now()->timestamp,
            'channel_id' => 'mozo_waiter',
        ];
        
        $data = array_merge($baseData, $extraData);
        
        // Normalizar call_id
        if (isset($data['callId']) && !isset($data['call_id'])) {
            $data['call_id'] = $data['callId'];
        }
        if (isset($data['call_id']) && !isset($data['callId'])) {
            $data['callId'] = $data['call_id'];
        }

        // 🎯 SEPARAR TOKENS POR PLATAFORMA
        $tokensByPlatform = DeviceToken::whereIn('token', $tokens)
            ->get()
            ->groupBy('platform');
        
        // 🧪 DEBUG: Verificar tokens encontrados por plataforma
        Log::info('🧪 DEBUG: Tokens separated by platform', [
            'table_number' => $tableNumber,
            'input_tokens_count' => count($tokens),
            'found_tokens_count' => $tokensByPlatform->sum->count(),
            'web_found' => count($tokensByPlatform['web'] ?? []),
            'android_found' => count($tokensByPlatform['android'] ?? []),
            'ios_found' => count($tokensByPlatform['ios'] ?? []),
            'platforms_detected' => $tokensByPlatform->keys()->toArray()
        ]);
        
        $sent = 0;
        $results = [];
        
        // WEB: Solo data (para service worker background)
        if (isset($tokensByPlatform['web'])) {
            foreach ($tokensByPlatform['web'] as $deviceToken) {
                try {
                    $resp = $this->sendDataOnlyToDevice($deviceToken->token, $data);
                    $results[] = [
                        'token' => substr($deviceToken->token, 0, 15) . '...',
                        'platform' => 'web',
                        'success' => true,
                        'id' => $resp['name'] ?? null
                    ];
                    $sent++;
                } catch (\Exception $e) {
                    $results[] = [
                        'token' => substr($deviceToken->token, 0, 15) . '...',
                        'platform' => 'web',
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }
        
        // MÓVIL (Android/iOS): notification + data
        $mobileTokens = collect(['android', 'ios'])
            ->flatMap(fn($platform) => $tokensByPlatform[$platform] ?? [])
            ->pluck('token')
            ->toArray();
            
        foreach ($mobileTokens as $token) {
            try {
                $resp = $this->sendToDevice($token, $title, $body, $data, 'high');
                $results[] = [
                    'token' => substr($token, 0, 15) . '...',
                    'platform' => 'mobile',
                    'success' => true,
                    'id' => $resp['name'] ?? null
                ];
                $sent++;
            } catch (\Exception $e) {
                $results[] = [
                    'token' => substr($token, 0, 15) . '...',
                    'platform' => 'mobile',
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info('Unified notifications dispatched', [
            'table_number' => $tableNumber,
            'sent' => $sent,
            'total' => count($tokens),
            'web_tokens' => count($tokensByPlatform['web'] ?? []),
            'mobile_tokens' => count($mobileTokens)
        ]);
        
        return [
            'sent' => $sent,
            'total' => count($tokens),
            'results' => $results
        ];
    }

    /**
     * 🌐 Enviar SOLO data a token web (para service worker background)
     * Sin campo notification, para que Firebase no maneje automáticamente
     */
    private function sendDataOnlyToDevice($token, array $data = [])
    {
        // FCM HTTP v1 API requiere que todos los valores en 'data' sean strings
        $formattedData = (object)[];
        if (!empty($data) && is_array($data)) {
            foreach ($data as $key => $value) {
                $formattedData->{$key} = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
            }
        }

        $message = [
            'message' => [
                'token' => $token,
                // 🎯 SOLO DATA - Sin notification para forzar service worker
                'data' => $formattedData,
                'webpush' => [
                    'headers' => [
                        'Urgency' => 'high'
                    ],
                    // 🎯 SIN notification en webpush tampoco
                    'fcm_options' => [
                        'link' => rtrim(config('app.url', '/'), '/') . '/'
                    ]
                ]
            ]
        ];

        return $this->sendMessage($message);
    }
}