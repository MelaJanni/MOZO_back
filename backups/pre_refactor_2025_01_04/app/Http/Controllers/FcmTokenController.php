<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use App\Services\FirebaseNotificationService;
use App\Services\TokenManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * FcmTokenController - Gestión de tokens FCM
 *
 * V2: Usa TokenManager y FirebaseNotificationService en lugar de FirebaseService legacy
 */
class FcmTokenController extends Controller
{
    private FirebaseNotificationService $firebaseService;
    private TokenManager $tokenManager;

    public function __construct(
        FirebaseNotificationService $firebaseService,
        TokenManager $tokenManager
    ) {
        $this->firebaseService = $firebaseService;
        $this->tokenManager = $tokenManager;
    }

    /**
     * 📱 REGISTRAR TOKEN FCM PARA ANDROID - MOZO
     */
    public function registerToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string|min:20',
            'platform' => 'required|in:android,ios,web',
            'device_info' => 'sometimes|array'
        ]);

        $user = Auth::user();
        
    // Solo mozos pueden registrar tokens para notificaciones de llamadas
    if (!$user->isWaiter()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los mozos pueden registrar tokens para notificaciones'
            ], 403);
        }

        try {
            $token = $request->input('token');
            $platform = $request->input('platform');
            $deviceInfo = $request->input('device_info', []);

            // Usar TokenManager para refrescar el token
            $success = $this->tokenManager->refreshToken($user->id, $token, $platform);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error registrando el token FCM'
                ], 500);
            }

            Log::info('FCM token registered for waiter', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'platform' => $platform,
                'token_preview' => substr($token, 0, 20) . '...',
                'device_info' => $deviceInfo
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token FCM registrado correctamente',
                'data' => [
                    'user_id' => $user->id,
                    'platform' => $platform,
                    'registered_at' => now(),
                    'will_receive_notifications' => true
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error registering FCM token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'token_preview' => substr($request->input('token'), 0, 20) . '...'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error registrando el token FCM'
            ], 500);
        }
    }

    /**
     * 🔄 REFRESCAR TOKEN FCM (cuando expira o cambia)
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $request->validate([
            'old_token' => 'sometimes|string',
            'new_token' => 'required|string|min:20',
            'platform' => 'required|in:android,ios,web'
        ]);

        $user = Auth::user();
        
        try {
            $newToken = $request->input('new_token');
            $platform = $request->input('platform');

            // Refrescar usando TokenManager
            $success = $this->tokenManager->refreshToken($user->id, $newToken, $platform);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error actualizando el token FCM'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token FCM actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error refreshing FCM token', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error actualizando el token FCM'
            ], 500);
        }
    }

    /**
     * 📱 OBTENER ESTADO DEL TOKEN FCM
     */
    public function getTokenStatus(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        try {
            $tokens = DeviceToken::where('user_id', $user->id)
                ->get()
                ->map(function($token) {
                    return [
                        'id' => $token->id,
                        'platform' => $token->platform,
                        'token_preview' => substr($token->token, 0, 20) . '...',
                        'created_at' => $token->created_at,
                        'expires_at' => $token->expires_at,
                        'is_expired' => $token->expires_at && $token->expires_at->isPast(),
                        'days_until_expiry' => $token->expires_at ? 
                            now()->diffInDays($token->expires_at, false) : null
                    ];
                });

            return response()->json([
                'success' => true,
                'tokens' => $tokens,
                'total_active_tokens' => $tokens->where('is_expired', false)->count(),
                'needs_refresh' => $tokens->where('is_expired', true)->count() > 0
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting token status', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo estado de tokens'
            ], 500);
        }
    }

    /**
     * 🧪 TEST DE NOTIFICACIÓN FCM
     */
    public function testNotification(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|string|max:100',
            'body' => 'sometimes|string|max:200',
            'platform' => 'sometimes|in:android,ios,web'
        ]);

        $user = Auth::user();
        
    if (!$user->isWaiter()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los mozos pueden probar notificaciones'
            ], 403);
        }

        try {
            $title = $request->input('title', '🧪 Test - Mozo App');
            $body = $request->input('body', 'Esta es una notificación de prueba para verificar que FCM funciona correctamente.');
            $platform = $request->input('platform');
            
            $data = [
                'type' => 'test_notification',
                'timestamp' => now()->timestamp,
                'user_id' => (string)$user->id,
                'platform' => $platform
            ];

            // Si especifica plataforma, filtrar por ella
            if ($platform) {
                $data['platform'] = $platform;
            }

            $result = $this->firebaseService->sendToUser($user->id, $title, $body, $data, 'high');
            $sent = $result['sent'] ?? 0;

            return response()->json([
                'success' => true,
                'message' => 'Notificación de prueba enviada',
                'data' => [
                    'title' => $title,
                    'body' => $body,
                    'sent_at' => now(),
                    'user_id' => $user->id,
                    'platform_filter' => $platform,
                    'sent' => $sent,
                    'total' => $result['total'] ?? 0,
                    'fcm_result' => $sent > 0 ? 'sent' : 'failed'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending test notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error enviando notificación de prueba: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🗑️ ELIMINAR TOKEN FCM
     */
    public function deleteToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'sometimes|string',
            'platform' => 'sometimes|in:android,ios,web'
        ]);

        $user = Auth::user();
        
        try {
            $query = DeviceToken::where('user_id', $user->id);
            
            if ($request->has('token')) {
                $query->where('token', $request->input('token'));
            }
            
            if ($request->has('platform')) {
                $query->where('platform', $request->input('platform'));
            }
            
            $deletedCount = $query->delete();

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$deletedCount} token(s)",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting FCM token', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error eliminando tokens'
            ], 500);
        }
    }
}