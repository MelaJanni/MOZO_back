<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private $serverKey;
    private $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->serverKey = config('services.firebase.server_key');
    }

    /**
     * 📱 ENVIAR PUSH NOTIFICATION A CLIENTES DE UNA MESA
     */
    public function sendToTable($tableId, $payload)
    {
        try {
            // Obtener tokens FCM de clientes en esta mesa
            $tokens = $this->getTableDeviceTokens($tableId);
            
            if (empty($tokens)) {
                Log::info("No FCM tokens found for table {$tableId}");
                return false;
            }
            
            $notification = [
                'title' => $payload['title'],
                'body' => $payload['body'],
                'icon' => '/favicon.ico',
                'click_action' => 'FCM_PLUGIN_ACTIVITY'
            ];
            
            $data = array_merge($payload['data'] ?? [], [
                'type' => 'waiter_update',
                'table_id' => (string)$tableId,
                'timestamp' => time() * 1000
            ]);
            
            $success = 0;
            foreach ($tokens as $token) {
                if ($this->sendToDevice($token, $notification, $data)) {
                    $success++;
                }
            }
            
            Log::info("Push notifications sent", [
                'table_id' => $tableId,
                'total_tokens' => count($tokens),
                'successful_sends' => $success
            ]);
            
            return $success > 0;
            
        } catch (\Exception $e) {
            Log::error('Failed to send push notification to table', [
                'table_id' => $tableId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * 🔥 ENVIAR NOTIFICACIÓN UNIFIED (para app Android con FCM)
     */
    public function sendUnifiedNotification($waiterTokens, $tableNumber, $message = null)
    {
        // 🔄 Migrado a FirebaseService (HTTP v1). Este método se mantiene por compatibilidad.
        try {
            $firebaseService = app(\App\Services\FirebaseService::class);
            $result = $firebaseService->sendUnifiedNotificationToTokens(
                $waiterTokens ?? [],
                (int)$tableNumber,
                $message ?: 'Nueva llamada UNIFIED'
            );
            return $result['sent'] > 0;
        } catch (\Exception $e) {
            Log::error('Unified notification delegation failed', [
                'table_number' => $tableNumber,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 🔥 ENVIAR NOTIFICACIÓN UNIFIED A DISPOSITIVO ESPECÍFICO
     */
    private function sendUnifiedToDevice($token, $data)
    {
        if (empty($this->serverKey)) {
            Log::warning('Firebase server key not configured');
            return false;
        }
        
        try {
            // Para notificaciones UNIFIED: solo enviar DATA sin notification
            // Esto permite que MyFirebaseMessagingService.java maneje la notificación
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json'
            ])->timeout(10)->post($this->fcmUrl, [
                'to' => $token,
                'data' => $data, // Solo data, sin notification
                'priority' => 'high',
                'android' => [
                    'priority' => 'high'
                ]
            ]);
            
            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['success']) && $result['success'] === 1) {
                    return true;
                } else {
                    Log::warning('FCM UNIFIED send partially failed', [
                        'token' => substr($token, 0, 20) . '...',
                        'response' => $result
                    ]);
                }
            } else {
                Log::error('FCM UNIFIED HTTP request failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('FCM UNIFIED send exception', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ]);
            return false;
        }
    }

    /**
     * 📱 ENVIAR PUSH NOTIFICATION A UN DISPOSITIVO ESPECÍFICO
     */
    public function sendToDevice($token, $notification, $data = [])
    {
        if (empty($this->serverKey)) {
            Log::warning('Firebase server key not configured');
            return false;
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json'
            ])->timeout(10)->post($this->fcmUrl, [
                'to' => $token,
                'notification' => $notification,
                'data' => $data,
                'priority' => 'high',
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'waiter_urgent',
                        'sound' => 'default'
                    ]
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1
                        ]
                    ]
                ]
            ]);
            
            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['success']) && $result['success'] === 1) {
                    return true;
                } else {
                    Log::warning('FCM send partially failed', [
                        'token' => substr($token, 0, 20) . '...',
                        'response' => $result
                    ]);
                }
            } else {
                Log::error('FCM HTTP request failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('FCM send exception', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ]);
            return false;
        }
    }
    
    /**
     * 🔍 OBTENER TOKENS FCM DE CLIENTES EN UNA MESA
     */
    private function getTableDeviceTokens($tableId)
    {
        // TODO: Implementar modelo ClientDeviceToken cuando se tenga app móvil
        // Por ahora devolver array vacío ya que los clientes usan navegador web
        
        // Ejemplo de implementación futura:
        /*
        return \App\Models\ClientDeviceToken::where('table_id', $tableId)
            ->where('is_active', true)
            ->where('created_at', '>', now()->subHours(24)) // Tokens recientes
            ->pluck('fcm_token')
            ->toArray();
        */
        
        return [];
    }
    
    /**
     * 📝 REGISTRAR TOKEN FCM DE CLIENTE (para futura app móvil)
     */
    public function registerClientToken($tableId, $token, $deviceInfo = [])
    {
        // TODO: Implementar cuando se tenga app móvil para clientes
        // Por ahora los clientes usan navegador web con Firebase Realtime
        
        Log::info('Client FCM token registration requested', [
            'table_id' => $tableId,
            'token_preview' => substr($token, 0, 20) . '...',
            'device_info' => $deviceInfo
        ]);
        
        return true;
    }

    /**
     * 🔍 OBTENER TOKENS FCM DE MOZOS DE UN NEGOCIO
     */
    public function getWaiterTokens($businessId)
    {
        try {
            $tokens = \App\Models\DeviceToken::whereHas('user', function($query) use ($businessId) {
                $query->where('role', 'waiter')
                      ->whereHas('businesses', function($b) use ($businessId) {
                          $b->where('business_id', $businessId);
                      });
            })
            ->orderByDesc('updated_at')
            ->limit(500) // seguridad
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

            Log::info('Retrieved waiter tokens (unified)', [
                'business_id' => $businessId,
                'count' => count($tokens)
            ]);

            return $tokens;
        } catch (\Exception $e) {
            Log::error('Failed to get waiter tokens', [
                'business_id' => $businessId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * 🧪 TESTEAR ENVÍO DE NOTIFICACIÓN UNIFIED
     */
    public function testUnifiedNotification($businessId, $tableNumber = 99)
    {
        try {
            $tokens = $this->getWaiterTokens($businessId);
            
            if (empty($tokens)) {
                return [
                    'status' => 'error',
                    'message' => 'No waiter tokens found for business ' . $businessId
                ];
            }
            
            $result = $this->sendUnifiedNotification($tokens, $tableNumber, 'Test UNIFIED notification');
            
            return [
                'status' => $result ? 'success' : 'failed',
                'message' => $result ? 'Test UNIFIED notification sent' : 'Failed to send test notification',
                'token_count' => count($tokens),
                'table_number' => $tableNumber
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Test failed: ' . $e->getMessage()
            ];
        }
    }
}