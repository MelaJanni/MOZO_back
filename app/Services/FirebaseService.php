<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Models\DeviceToken;
use App\Models\User;

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
        $serviceAccountPath = storage_path('app/firebase/firebase.json');

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
        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
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
     * Send notification to multiple device tokens
     */
    public function sendToMultipleDevices($tokens, $title, $body, $data = [])
    {
        $message = [
            'message' => [
                'registration_ids' => $tokens,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
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

        return $this->sendMessage($message, true);
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

        $results = [];
        foreach ($deviceTokens as $token) {
            $results[] = $this->sendToDevice($token, $title, $body, $data);
        }

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

        // FCM allows maximum 1000 tokens per batch
        $batches = array_chunk($deviceTokens, 1000);
        $results = [];

        foreach ($batches as $batch) {
            $results[] = $this->sendToMultipleDevices($batch, $title, $body, $data);
        }

        return $results;
    }

    /**
     * Send message to FCM API
     */
    private function sendMessage($message, $isMulticast = false)
    {
        if (!$this->accessToken && !$isMulticast) {
            Log::error('Firebase access token not available, cannot send message');
            throw new \Exception('Firebase service not properly initialized');
        }
        
        try {
            $url = $isMulticast 
                ? "https://fcm.googleapis.com/fcm/send"
                : "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ];

            if ($isMulticast) {
                $headers['Authorization'] = 'key=' . config('services.firebase.server_key');
            }

            $response = $this->client->post($url, [
                'headers' => $headers,
                'json' => $message,
            ]);

            $result = json_decode($response->getBody(), true);
            Log::info('FCM notification sent successfully', $result);
            
            return $result;
        } catch (RequestException $e) {
            Log::error('Failed to send FCM notification: ' . $e->getMessage());
            throw new \Exception('Failed to send notification');
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
        $message = [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
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