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
    public function sendToDevice($token, $title, $body, $data = [])
    {
        // FCM HTTP v1 API requiere que todos los valores en 'data' sean strings
        $formattedData = (object)[];  // Siempre debe ser un objeto, nunca array
        if (!empty($data) && is_array($data)) {
            foreach ($data as $key => $value) {
                $formattedData->{$key} = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
            }
        }

        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $formattedData,
                'android' => [
                    'notification' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
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

        return $this->sendMessage($message);
    }

    /**
     * Send notification to multiple device tokens using HTTP v1 API
     */
    public function sendToMultipleDevices($tokens, $title, $body, $data = [])
    {
        $results = [];
        
        // HTTP v1 API no soporta envío a múltiples tokens en una sola request
        // Necesitamos enviar individualmente o usar sendToAllUsers con topic
        foreach ($tokens as $token) {
            try {
                $result = $this->sendToDevice($token, $title, $body, $data);
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
    public function sendToUser($userId, $title, $body, $data = [])
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        $deviceTokens = DeviceToken::where('user_id', $userId)->pluck('token')->toArray();
        
        if (empty($deviceTokens)) {
            Log::warning("No device tokens found for user ID: {$userId}");
            return false;
        }

        // 1. Enviar FCM push notification
        $results = [];
        foreach ($deviceTokens as $token) {
            $results[] = $this->sendToDevice($token, $title, $body, $data);
        }

        // 2. Guardar notificación en BD para que aparezca en el historial
        $user->notify(new FcmDatabaseNotification($title, $body, $data));

        return $results;
    }

    /**
     * Send notification to all users
     */
    public function sendToAllUsers($title, $body, $data = [])
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
            $results[] = $this->sendToMultipleDevices($batch, $title, $body, $data);
        }

        // 2. Guardar notificación en BD para todos los usuarios que tienen tokens
        $userIds = DeviceToken::distinct('user_id')->pluck('user_id');
        $users = User::whereIn('id', $userIds)->get();
        
        Notification::send($users, new FcmDatabaseNotification($title, $body, $data));

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
    public function sendToTopic($topic, $title, $body, $data = [])
    {
        // FCM HTTP v1 API requiere que todos los valores en 'data' sean strings
        $formattedData = (object)[];  // Siempre debe ser un objeto, nunca array
        if (!empty($data) && is_array($data)) {
            foreach ($data as $key => $value) {
                $formattedData->{$key} = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
            }
        }

        $message = [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $formattedData,
                'android' => [
                    'notification' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
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

        return $this->sendMessage($message);
    }
}