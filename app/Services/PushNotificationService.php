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
                        'channel_id' => 'waiter_calls',
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
}