<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiDocumentationController;
use App\Http\Controllers\NotificationStreamController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\WaiterController;
use App\Http\Controllers\WaiterCallController;
use App\Http\Controllers\PublicQrController;
use App\Http\Controllers\FirebaseConfigController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login/google', [AuthController::class, 'loginWithGoogle']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/api-docs', [ApiDocumentationController::class, 'listAllApis']);

// Broadcasting Auth route for real-time features
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Endpoint temporal para registrar tokens FCM de prueba
Route::post('/test/register-fcm-token', [NotificationController::class, 'registerTestToken']);

// Firebase configuration endpoints (public for mozoqr.com)
Route::middleware('public_api')->group(function () {
    Route::get('/firebase/config', [FirebaseConfigController::class, 'getConfig']);
    Route::get('/firebase/table/{table}/config', [FirebaseConfigController::class, 'getQrTableConfig']);
    
    // 🔧 DIAGNOSTICO: Verificar estado de Firebase
    Route::get('/firebase/status', function() {
        $serviceAccountPath = config('services.firebase.service_account_path');
        $hasServiceAccount = !empty($serviceAccountPath) && file_exists($serviceAccountPath);
        
        $config = [
            'project_id' => config('services.firebase.project_id'),
            'has_server_key' => !empty(config('services.firebase.server_key')),
            'has_service_account' => $hasServiceAccount,
            'service_account_path' => $serviceAccountPath,
            'has_api_key' => !empty(config('services.firebase.api_key')),
            'has_auth_domain' => !empty(config('services.firebase.auth_domain')),
        ];
        
        $isFullyConfigured = $config['has_server_key'] && $config['has_service_account'] && $config['has_api_key'];
        
        return response()->json([
            'status' => $isFullyConfigured ? 'fully_configured' : 'partial_configuration',
            'real_time_available' => $hasServiceAccount,
            'fcm_available' => $config['has_server_key'],
            'frontend_config_available' => $config['has_api_key'] && $config['has_auth_domain'],
            'fallback_polling_enabled' => true,
            'config' => $config,
            'recommendations' => $isFullyConfigured ? [] : [
                !$config['has_service_account'] ? 'Configure FIREBASE_SERVICE_ACCOUNT_PATH for real-time features' : null,
                !$config['has_server_key'] ? 'Configure FIREBASE_SERVER_KEY for push notifications' : null,
                !$config['has_api_key'] ? 'Configure FIREBASE_API_KEY for frontend auth' : null,
            ]
        ]);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/role/select', [RoleController::class, 'selectRole']);

    Route::prefix('profile')->group(function () {
        Route::post('/update', [ProfileController::class, 'updateProfile']);
        Route::post('/whatsapp/send', [ProfileController::class, 'sendWhatsAppMessage']);
        
        Route::get('/work-history', [ProfileController::class, 'getWorkHistory']);
        Route::post('/work-history', [ProfileController::class, 'addWorkHistory']);
        Route::put('/work-history/{workExperience}', [ProfileController::class, 'updateWorkHistory']);
        Route::delete('/work-history/{workExperience}', [ProfileController::class, 'deleteWorkHistory']);
    });

    Route::post('/device-token', [NotificationController::class, 'storeDeviceToken']);
    Route::delete('/device-token', [ProfileController::class, 'deleteDeviceToken']);
    Route::get('/device-tokens/{userId}', [NotificationController::class, 'getUserDeviceTokens']);
    
    // Notificaciones del usuario autenticado
    Route::get('/user/notifications', [NotificationController::class, 'getUserNotifications']);
    Route::post('/user/notifications/{id}/read', [NotificationController::class, 'markNotificationAsRead']);

    // Llamadas de mozo - APIs para mozos autenticados
    Route::prefix('waiter')->group(function () {
        // Gestión de llamadas
        Route::get('/calls/pending', [WaiterCallController::class, 'getPendingCalls']);
        Route::get('/calls/history', [WaiterCallController::class, 'getCallHistory']);
        Route::post('/calls/{call}/acknowledge', [WaiterCallController::class, 'acknowledgeCall']);
        Route::post('/calls/{call}/complete', [WaiterCallController::class, 'completeCall']);
        
        // Dashboard y estado
        Route::get('/dashboard', [WaiterCallController::class, 'getDashboard']);
        Route::get('/tables/status', [WaiterCallController::class, 'getTablesStatus']);

        // Gestión de negocios múltiples
        Route::get('/businesses', [WaiterCallController::class, 'getWaiterBusinesses']);
        Route::get('/businesses/{id}/tables', [WaiterCallController::class, 'getBusinessTables']);
        Route::post('/join-business', [WaiterCallController::class, 'joinBusiness']);
        Route::post('/set-active-business', [WaiterCallController::class, 'setActiveBusiness']);
        
        // Gestión de mesas - Individual
        Route::get('/tables/assigned', [WaiterCallController::class, 'getAssignedTables']);
        Route::get('/tables/available', [WaiterCallController::class, 'getAvailableTables']);
        Route::post('/tables/{table}/activate', [WaiterCallController::class, 'activateTable']);
        Route::delete('/tables/{table}/activate', [WaiterCallController::class, 'deactivateTable']);
        Route::post('/tables/{table}/silence', [WaiterCallController::class, 'silenceTable']);
        Route::delete('/tables/{table}/silence', [WaiterCallController::class, 'unsilenceTable']);
        
        // Gestión de mesas - Múltiples
        Route::post('/tables/activate/multiple', [WaiterCallController::class, 'activateMultipleTables']);
        Route::post('/tables/deactivate/multiple', [WaiterCallController::class, 'deactivateMultipleTables']);
        Route::post('/tables/silence/multiple', [WaiterCallController::class, 'silenceMultipleTables']);
        Route::post('/tables/unsilence/multiple', [WaiterCallController::class, 'unsilenceMultipleTables']);
        
        // Estado de mesas
        Route::get('/tables/silenced', [WaiterCallController::class, 'getSilencedTables']);

        // Perfiles de mesa
        Route::get('/table-profiles', [WaiterController::class, 'listProfiles']);
        Route::post('/table-profiles', [WaiterController::class, 'createProfile']);
        Route::put('/table-profiles/{id}', [WaiterController::class, 'updateProfile']);
        Route::delete('/table-profiles/{id}', [WaiterController::class, 'deleteProfile']);
        Route::post('/table-profiles/{id}/activate', [WaiterController::class, 'activateProfile']);
    });

    Route::prefix('admin')->group(function () {
        Route::delete('/staff/{staffId}', [AdminController::class, 'removeStaff']);
        Route::post('/staff/request/{requestId}', [AdminController::class, 'handleStaffRequest']);
        Route::get('/staff/requests', [AdminController::class, 'fetchStaffRequests']);
        Route::get('/staff/requests/archived', [AdminController::class, 'fetchArchivedRequests']);
        Route::post('/staff/onboard', [WaiterController::class, 'onboardBusiness']);

        Route::get('/business', [AdminController::class, 'getBusinessInfo']);
        Route::post('/business/regenerate-invitation-code', [AdminController::class, 'regenerateInvitationCode']);
        Route::post('/switch-view', [AdminController::class, 'switchView']);
        Route::get('/settings', [AdminController::class, 'getSettings']);
        Route::post('/settings', [AdminController::class, 'updateSettings']);

        Route::post('/qr/generate/{tableId}', [QrCodeController::class, 'generateQRCode']);
        Route::get('/qr/preview/{tableId}', [QrCodeController::class, 'preview']);
        Route::post('/qr/export', [QrCodeController::class, 'exportQR']);
        Route::post('/qr/email', [QrCodeController::class, 'emailQR']);
        
        Route::post('/send-test-notification', [AdminController::class, 'sendTestNotification']);
        Route::post('/send-notification-to-user', [AdminController::class, 'sendNotificationToUser']);
        
        // FCM Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/send-to-all', [NotificationController::class, 'sendToAllUsers']);
        Route::post('/notifications/send-to-user', [NotificationController::class, 'sendToUser']);
        Route::post('/notifications/send-to-device', [NotificationController::class, 'sendToDevice']);
        Route::post('/notifications/send-to-topic', [NotificationController::class, 'sendToTopic']);
        Route::post('/notifications/subscribe-to-topic', [NotificationController::class, 'subscribeToTopic']);

        // Admin - Historial de llamadas y gestión
        Route::get('/calls/history', [WaiterCallController::class, 'getCallHistory']);
        Route::get('/tables/silenced', [WaiterCallController::class, 'getSilencedTables']);
        Route::delete('/tables/{table}/silence', [WaiterCallController::class, 'unsilenceTable']);

        Route::get('/staff', [AdminController::class, 'getStaff']);
        Route::get('/staff/{id}', [AdminController::class, 'getStaffMember']);
        Route::put('/staff/{id}', [AdminController::class, 'updateStaffMember']);
        Route::post('/staff/invite', [AdminController::class, 'inviteStaff']);
        Route::post('/staff/{id}/reviews', [AdminController::class, 'addReview']);
        Route::delete('/staff/{staffId}/reviews/{id}', [AdminController::class, 'deleteReview']);
    });

    Route::get('/tables', [TableController::class, 'fetchTables']);
    Route::post('/tables', [TableController::class, 'createTable']);
    Route::put('/tables/{tableId}', [TableController::class, 'updateTable']);
    Route::delete('/tables/{tableId}', [TableController::class, 'deleteTable']);
    
    Route::get('/menus', [MenuController::class, 'fetchMenus']);
    Route::post('/menus', [MenuController::class, 'uploadMenu']);
    Route::post('/menus/default', [MenuController::class, 'setDefaultMenu']);
    Route::put('/menus/{menu}', [MenuController::class, 'renameMenu']);
    Route::delete('/menus/{menu}', [MenuController::class, 'destroy']);
    Route::post('/menus/reorder', [MenuController::class, 'reorderMenus']);
    Route::get('/menus/{menu}/preview', [MenuController::class, 'preview']);
    Route::get('/menus/{menu}/download', [MenuController::class, 'download']);
    
    Route::get('/notifications', [WaiterController::class, 'fetchWaiterNotifications']);
    Route::post('/notifications/handle/{notificationId}', [WaiterController::class, 'handleNotification']);
    Route::post('/notifications/global', [WaiterController::class, 'globalNotifications']);
    Route::post('/tables/toggle-notifications/{tableId}', [WaiterController::class, 'toggleTableNotifications']);
    
    Route::get('/table-profiles', [WaiterController::class, 'listProfiles']);
    Route::post('/table-profiles', [WaiterController::class, 'createProfile']);
    Route::delete('/table-profiles/{id}', [WaiterController::class, 'deleteProfile']);

    Route::post('/tables/clone/{tableId}', [TableController::class, 'cloneTable']);

    Route::prefix('waiter')->group(function () {
        Route::post('/onboard', [WaiterController::class, 'onboardBusiness']);

        Route::get('/tables', [WaiterController::class, 'fetchWaiterTables']);
        Route::post('/tables/toggle-notifications/{tableId}', [WaiterController::class, 'toggleTableNotifications']);
        Route::post('/tables/clone/{tableId}', [TableController::class, 'cloneTable']);

        Route::get('/notifications', [WaiterController::class, 'fetchWaiterNotifications']);
        Route::post('/notifications/handle/{notificationId}', [WaiterController::class, 'handleNotification']);
        Route::post('/notifications/global', [WaiterController::class, 'globalNotifications']);

        Route::get('/profiles', [WaiterController::class, 'listProfiles']);
        Route::post('/profiles', [WaiterController::class, 'createProfile']);
        Route::put('/profiles/{id}', [WaiterController::class, 'updateProfile']);
        Route::delete('/profiles/{id}', [WaiterController::class, 'deleteProfile']);
        Route::post('/profiles/{id}/activate', [WaiterController::class, 'activateProfile']);
    });

    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);

    Route::get('/admin/statistics', [AdminController::class, 'getStatistics']);
});

// Rutas públicas para QR codes y llamadas de mozo (sin autenticación)
Route::middleware('public_api')->group(function () {
    Route::post('/tables/{table}/call-waiter', [WaiterCallController::class, 'callWaiter']);
    
    // API pública para información de QR codes
    Route::get('/qr/{restaurantSlug}/{tableCode}', [PublicQrController::class, 'getTableInfo'])
        ->name('api.qr.table.info');

    // API pública para obtener estado de mesa (polling fallback)
    Route::get('/table/{tableId}/status', [PublicQrController::class, 'getTableStatus'])
        ->name('api.table.status');

    // Compatibility route for existing frontend (waiter-notifications)
    Route::post('/waiter-notifications', [WaiterCallController::class, 'createNotification']);
    Route::get('/waiter-notifications/{id}', [WaiterCallController::class, 'getNotificationStatus']);
    
    // 🔧 TEST: Endpoint simple para probar la API
    Route::post('/test-waiter-notification', function(Illuminate\Http\Request $request) {
        try {
            Log::info('Test waiter notification', [
                'method' => $request->method(),
                'body' => $request->all(),
                'ip' => $request->ip()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Test endpoint funcionando correctamente',
                'received_data' => $request->all(),
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    });
    
    // 🔧 DEBUG: Agregar ruta GET para diagnosticar el problema
    Route::get('/waiter-notifications', function() {
        return response()->json([
            'error' => 'Método no permitido',
            'message' => 'Esta ruta solo acepta POST para crear notificaciones.',
            'correct_usage' => [
                'method' => 'POST',
                'url' => '/api/waiter-notifications',
                'body' => [
                    'restaurant_id' => 'integer',
                    'table_id' => 'integer', 
                    'message' => 'string (optional)',
                    'urgency' => 'string (optional: low,normal,high)'
                ]
            ],
            'status_check' => [
                'method' => 'GET',
                'url' => '/api/waiter-notifications/{notification_id}'
            ],
            'debug_info' => [
                'timestamp' => now(),
                'requested_method' => 'GET',
                'client_ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]
        ], 405); // Method Not Allowed
    });
    
    // 🚀 TIEMPO REAL OPTIMIZADO: Alternativas a Firebase
    Route::get('/notifications/stream', [NotificationStreamController::class, 'stream']); // Server-Sent Events
    Route::get('/notifications/poll', [NotificationStreamController::class, 'poll']);     // Polling optimizado
    
    // 🔧 FALLBACK: Polling simple para notificaciones cuando Firebase falle
    Route::get('/waiter/{waiterId}/notifications', function($waiterId) {
        try {
            // Obtener llamadas pendientes del mozo
            $pendingCalls = \App\Models\WaiterCall::with(['table', 'waiter'])
                ->where('waiter_id', $waiterId)
                ->where('status', 'pending')
                ->orderBy('called_at', 'desc')
                ->take(10)
                ->get();
                
            // Obtener llamadas recientes (últimos 5 minutos)
            $recentCalls = \App\Models\WaiterCall::with(['table', 'waiter'])
                ->where('waiter_id', $waiterId)
                ->where('called_at', '>=', now()->subMinutes(5))
                ->whereIn('status', ['acknowledged', 'completed'])
                ->orderBy('called_at', 'desc')
                ->take(5)
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => [
                    'pending_calls' => $pendingCalls->map(function($call) {
                        return [
                            'id' => $call->id,
                            'table_number' => $call->table->number,
                            'table_name' => $call->table->name,
                            'message' => $call->message,
                            'called_at' => $call->called_at,
                            'urgency' => $call->metadata['urgency'] ?? 'normal'
                        ];
                    }),
                    'recent_calls' => $recentCalls->map(function($call) {
                        return [
                            'id' => $call->id,
                            'table_number' => $call->table->number,
                            'status' => $call->status,
                            'called_at' => $call->called_at,
                            'acknowledged_at' => $call->acknowledged_at,
                            'completed_at' => $call->completed_at
                        ];
                    }),
                    'total_pending' => $pendingCalls->count(),
                    'last_check' => now()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // 🔧 FALLBACK: Polling de estado de mesa para frontend QR
    Route::get('/table/{tableId}/call-status', function($tableId) {
        try {
            // Obtener la última llamada de esta mesa
            $latestCall = \App\Models\WaiterCall::with(['waiter'])
                ->where('table_id', $tableId)
                ->orderBy('called_at', 'desc')
                ->first();
                
            if (!$latestCall) {
                return response()->json([
                    'success' => true,
                    'has_active_call' => false,
                    'message' => 'No hay llamadas activas'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'has_active_call' => $latestCall->status === 'pending',
                'call' => [
                    'id' => $latestCall->id,
                    'status' => $latestCall->status,
                    'waiter_name' => $latestCall->waiter->name ?? 'Mozo',
                    'called_at' => $latestCall->called_at,
                    'acknowledged_at' => $latestCall->acknowledged_at,
                    'completed_at' => $latestCall->completed_at,
                    'message' => $latestCall->message,
                    'is_recent' => $latestCall->called_at->isAfter(now()->subMinutes(10))
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });
});