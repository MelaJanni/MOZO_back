<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\DeviceToken;

class NotificationController extends Controller
{
    private $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Get notifications overview
     */
    public function index(): JsonResponse
    {
        try {
            $totalTokens = DeviceToken::count();
            $totalUsers = DeviceToken::distinct('user_id')->count();
            $tokensByPlatform = DeviceToken::select('platform')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('platform')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_tokens' => $totalTokens,
                    'total_users_with_tokens' => $totalUsers,
                    'tokens_by_platform' => $tokensByPlatform,
                    'firebase_project_id' => config('services.firebase.project_id'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notifications data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notification to all users
     */
    public function sendToAllUsers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->firebaseService->sendToAllUsers(
                $request->title,
                $request->body,
                $request->data ?? []
            );

            if ($result === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'No device tokens found for broadcast'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification sent to all users successfully',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notification to specific user
     */
    public function sendToUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->firebaseService->sendToUser(
                $request->user_id,
                $request->title,
                $request->body,
                $request->data ?? []
            );

            if ($result === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'No device tokens found for this user'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification sent to user successfully',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notification to specific device token
     */
    public function sendToDevice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->firebaseService->sendToDevice(
                $request->token,
                $request->title,
                $request->body,
                $request->data ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Notification sent to device successfully',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subscribe device token to topic
     */
    public function subscribeToTopic(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tokens' => 'required|array',
            'tokens.*' => 'string',
            'topic' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->firebaseService->subscribeToTopic(
                $request->tokens,
                $request->topic
            );

            return response()->json([
                'success' => true,
                'message' => 'Tokens subscribed to topic successfully',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to subscribe to topic: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notification to topic
     */
    public function sendToTopic(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'topic' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->firebaseService->sendToTopic(
                $request->topic,
                $request->title,
                $request->body,
                $request->data ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Notification sent to topic successfully',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store device token for user
     */
    public function storeDeviceToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'platform' => 'required|string|in:android,ios,web',
            'channel' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Mantener política: update-or-create por user_id + platform (un token por plataforma)
            // Eliminar tokens duplicados para el mismo token
            try {
                DeviceToken::where('token', $request->token)->where('user_id', '<>', $request->user()->id)->delete();
            } catch (\Exception $e) {
                \Log::warning('Could not delete foreign device tokens with same token: ' . $e->getMessage());
            }

            try {
                // Eliminar otros tokens del mismo usuario en la misma plataforma
                DeviceToken::where('user_id', $request->user()->id)
                    ->where('platform', $request->platform)
                    ->where('token', '<>', $request->token)
                    ->delete();
            } catch (\Exception $e) {
                \Log::warning('Could not clean other device tokens for user ' . $request->user()->id . ': ' . $e->getMessage());
            }

            $deviceToken = DeviceToken::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'platform' => $request->platform,
                ],
                [
                    'token' => $request->token,
                    'channel' => $request->input('channel'),
                    'expires_at' => now()->addMonths(6),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Device token stored successfully',
                'data' => $deviceToken
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store device token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all device tokens for a user
     */
    public function getUserDeviceTokens($userId): JsonResponse
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $deviceTokens = DeviceToken::where('user_id', $userId)->get();

            return response()->json([
                'success' => true,
                'data' => $deviceTokens
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get device tokens: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notifications for authenticated user
     */
    public function getUserNotifications(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $unreadNotifications = $user->unreadNotifications()
                ->orderBy('created_at', 'desc')
                ->get();
                
            $readNotifications = $user->readNotifications()
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'unread' => $unreadNotifications,
                    'read' => $readNotifications,
                    'unread_count' => $unreadNotifications->count(),
                    'total_count' => $unreadNotifications->count() + $readNotifications->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            $notification = $user->notifications()->where('id', $id)->first();
            
            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }
            
            $notification->markAsRead();
            
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'data' => $notification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint temporal para registrar tokens FCM de prueba
     */
    public function registerTestToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'token' => 'required|string',
            'platform' => 'required|string|in:android,ios,web',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Crear usuario de prueba si no existe
            $user = User::firstOrCreate(
                ['id' => $request->user_id],
                [
                    'name' => 'Test User ' . $request->user_id,
                    'email' => 'testuser' . $request->user_id . '@example.com',
                    'password' => Hash::make('password'),
                    'role' => 'waiter'
                ]
            );

            // Registrar token FCM
            $deviceToken = DeviceToken::updateOrCreate(
                [
                    'user_id' => $request->user_id,
                    'token' => $request->token,
                ],
                [
                    'platform' => $request->platform,
                    'expires_at' => now()->addMonths(6),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Test FCM token registered successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ],
                    'device_token' => $deviceToken
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register test token: ' . $e->getMessage()
            ], 500);
        }
    }
}