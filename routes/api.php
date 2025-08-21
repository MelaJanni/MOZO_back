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
use App\Http\Controllers\RealtimeController;
use App\Http\Controllers\WaiterCallRealtimeController;
use App\Http\Controllers\WaiterCallRealtimeControllerHTTP;
use App\Http\Controllers\FcmTokenController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login/google', [AuthController::class, 'loginWithGoogle']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// 🚀 SSE ENDPOINTS - Fuera de middleware para acceso directo
Route::get('/test/sse', [RealtimeController::class, 'testStream']);
Route::get('/table/{tableId}/call-status/stream', [RealtimeController::class, 'tableCallStream']);

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
        
        // 🔥 TEST DE ESCRITURA A FIREBASE REALTIME DB
        $firebaseWriteTest = null;
        try {
            $testData = [
                'id' => 'backend_test_' . time(),
                'message' => 'Test de escritura desde backend',
                'timestamp' => now()->toIso8601String(),
                'source' => 'firebase_status_endpoint'
            ];
            
            $url = "https://mozoqr-7d32c-default-rtdb.firebaseio.com/backend_tests/test_" . time() . ".json";
            $response = \Illuminate\Support\Facades\Http::timeout(3)->put($url, $testData);
            
            $firebaseWriteTest = [
                'attempted' => true,
                'success' => $response->successful(),
                'url' => $url,
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ];
        } catch (\Exception $e) {
            $firebaseWriteTest = [
                'attempted' => true,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        return response()->json([
            'status' => $isFullyConfigured ? 'fully_configured' : 'partial_configuration',
            'real_time_available' => $hasServiceAccount,
            'fcm_available' => $config['has_server_key'],
            'frontend_config_available' => $config['has_api_key'] && $config['has_auth_domain'],
            'fallback_polling_enabled' => true,
            'config' => $config,
            'firebase_write_test' => $firebaseWriteTest,
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

    // 🔄 PERFILES SEPARADOS POR ROL
    Route::prefix('user-profile')->group(function () {
        Route::get('/active', [UserProfileController::class, 'getActiveProfile']);
        Route::get('/all', [UserProfileController::class, 'getAllProfiles']);
        Route::get('/completeness', [UserProfileController::class, 'getProfileCompleteness']);
        Route::post('/waiter/update', [UserProfileController::class, 'updateWaiterProfile']);
        Route::post('/admin/update', [UserProfileController::class, 'updateAdminProfile']);
        Route::delete('/avatar', [UserProfileController::class, 'deleteAvatar']);
    });

    // 📋 FUNCIONES ADICIONALES DE PERFIL
    Route::prefix('profile')->group(function () {
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
        // 🔥 GESTIÓN DE LLAMADAS CON FIREBASE REAL-TIME
        Route::get('/calls/pending', [App\Http\Controllers\RealtimeWaiterCallController::class, 'getPendingCalls']);
        Route::get('/calls/recent', [App\Http\Controllers\RealtimeWaiterCallController::class, 'getRecentCalls']);
        Route::post('/calls/{call}/acknowledge', [App\Http\Controllers\RealtimeWaiterCallController::class, 'acknowledgeCall']);
        Route::post('/calls/{call}/complete', [App\Http\Controllers\RealtimeWaiterCallController::class, 'completeCall']);
        
        // Historial (sin tiempo real)
        Route::get('/calls/history', [WaiterCallController::class, 'getCallHistory']);
        
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
        
        // Gestión de IPs bloqueadas (anti-spam)
        Route::post('/ip/block', [WaiterCallController::class, 'blockIp']);
        Route::post('/ip/unblock', [WaiterCallController::class, 'unblockIp']);
        Route::get('/ip/blocked', [WaiterCallController::class, 'getBlockedIps']);
        Route::get('/ip/debug', [WaiterCallController::class, 'debugIpStatus']);
        Route::post('/ip/force-unblock', [WaiterCallController::class, 'forceUnblockIp']);

        // Perfiles de mesa - CRUD completo
        Route::get('/table-profiles', [App\Http\Controllers\TableProfileController::class, 'index']);
        Route::post('/table-profiles', [App\Http\Controllers\TableProfileController::class, 'store']);
        Route::get('/table-profiles/{profile}', [App\Http\Controllers\TableProfileController::class, 'show']);
        Route::put('/table-profiles/{profile}', [App\Http\Controllers\TableProfileController::class, 'update']);
        Route::delete('/table-profiles/{profile}', [App\Http\Controllers\TableProfileController::class, 'destroy']);
        
        // Activación de perfiles
        Route::post('/table-profiles/{profile}/activate', [App\Http\Controllers\TableProfileController::class, 'activate']);
        Route::post('/table-profiles/{profile}/deactivate', [App\Http\Controllers\TableProfileController::class, 'deactivate']);
        
        // Notificaciones de auto-completar
        Route::get('/table-profiles/notifications', [App\Http\Controllers\TableProfileController::class, 'getAutoCompleteNotifications']);
        Route::post('/table-profiles/notifications/{notificationId}/read', [App\Http\Controllers\TableProfileController::class, 'markNotificationAsRead']);
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
        
        // Funcionalidades adicionales para el admin
        Route::post('/staff/bulk-process', [AdminController::class, 'bulkProcessRequests']);
        Route::get('/staff/{id}/whatsapp', [AdminController::class, 'getWhatsAppLink']);
        Route::get('/profile', [AdminController::class, 'getAdminProfile']);
        Route::post('/profile/update', [AdminController::class, 'updateAdminProfile']);
    });

    // 🔥 STAFF MANAGEMENT - Sistema de solicitudes de mozos con Firebase
    Route::prefix('staff')->group(function () {
        Route::get('/', [StaffController::class, 'index']); // Listar solicitudes
        Route::post('/', [StaffController::class, 'store']); // Crear solicitud
        Route::get('/{id}', [StaffController::class, 'show']); // Ver detalles
        Route::post('/{id}/approve', [StaffController::class, 'approve']); // Aprobar
        Route::post('/{id}/reject', [StaffController::class, 'reject']); // Rechazar
        Route::post('/{id}/invite', [StaffController::class, 'sendInvitation']); // Enviar invitación
        Route::get('/{id}/whatsapp', [StaffController::class, 'getWhatsAppInvitation']); // Obtener WhatsApp
        Route::delete('/{id}', [StaffController::class, 'destroy']); // Eliminar
        Route::post('/test-notifications', [StaffController::class, 'testNotifications']); // Test
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
    Route::get('/menus/upload-limits', [MenuController::class, 'uploadLimits']);
    
    Route::get('/notifications', [WaiterController::class, 'fetchWaiterNotifications']);
    Route::post('/notifications/handle/{notificationId}', [WaiterController::class, 'handleNotification']);
    Route::post('/notifications/{notificationId}/read', [WaiterController::class, 'markNotificationAsRead']);
    Route::post('/notifications/mark-multiple-read', [WaiterController::class, 'markMultipleNotificationsAsRead']);
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
        Route::post('/notifications/{notificationId}/read', [WaiterController::class, 'markNotificationAsRead']);
        Route::post('/notifications/mark-multiple-read', [WaiterController::class, 'markMultipleNotificationsAsRead']);
        Route::post('/notifications/global', [WaiterController::class, 'globalNotifications']);

        Route::get('/profiles', [WaiterController::class, 'listProfiles']);
        Route::post('/profiles', [WaiterController::class, 'createProfile']);
        Route::put('/profiles/{id}', [WaiterController::class, 'updateProfile']);
        Route::delete('/profiles/{id}', [WaiterController::class, 'deleteProfile']);
        Route::post('/profiles/{id}/activate', [WaiterController::class, 'activateProfile']);

        // 📱 FCM Token Management for Android Waiters
        Route::post('/fcm/register', [FcmTokenController::class, 'registerToken']);
        Route::post('/fcm/refresh', [FcmTokenController::class, 'refreshToken']);
        Route::get('/fcm/status', [FcmTokenController::class, 'getTokenStatus']);
        Route::post('/fcm/test', [FcmTokenController::class, 'testNotification']);
        Route::delete('/fcm/token', [FcmTokenController::class, 'deleteToken']);
    });

    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);

    Route::get('/admin/statistics', [AdminController::class, 'getStatistics']);
});

// Rutas públicas para QR codes y llamadas de mozo (sin autenticación)
Route::middleware('public_api')->group(function () {
    // 🔥 FIREBASE REAL-TIME DESDE CERO (LIMPIO Y ORGANIZADO)
    Route::post('/tables/{table}/call-waiter', [App\Http\Controllers\RealtimeWaiterCallController::class, 'createCall']);
    
    // 🔥 TEST DIRECTO FIREBASE (ULTRA SIMPLE)
    Route::get('/firebase/write-test', function() {
        try {
            $testData = [
                'id' => 'test_' . time(),
                'message' => 'Test desde backend Laravel',
                'timestamp' => now()->toIso8601String(),
                'table_number' => '99'
            ];
            
            $url = "https://mozoqr-7d32c-default-rtdb.firebaseio.com/waiters/2/calls/test_" . time() . ".json";
            
            $response = \Illuminate\Support\Facades\Http::timeout(5)->put($url, $testData);
            
            return response()->json([
                'test_write' => true,
                'url' => $url,
                'data_sent' => $testData,
                'firebase_response_status' => $response->status(),
                'firebase_response_body' => $response->body(),
                'success' => $response->successful()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
        }
    });

    // Test ULTRA RÁPIDO - Realtime Database
    Route::get('/firebase/test', function() {
        try {
            $service = new \App\Services\FirebaseRealtimeDatabaseService();
            $result = $service->testConnection();
            return response()->json([
                'realtime_database_test' => $result,
                'type' => 'ULTRA_FAST_REALTIME_DB',
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    });
    
    // 🔥 TEST FIREBASE WRITE FROM CREATENOTIFICATION METHOD
    Route::get('/firebase/test-createnotification-method', function() {
        try {
            // Create a fake call to test Firebase write
            $call = new \App\Models\WaiterCall();
            $call->id = 999;
            $call->waiter_id = 1;
            $call->table_id = 2;
            $call->message = "Test from createNotification method test";
            $call->metadata = ['urgency' => 'high'];
            $call->called_at = now();
            
            // Create fake table and waiter objects
            $table = new \App\Models\Table();
            $table->id = 2;
            $table->number = 99;
            
            $waiter = new \App\Models\User();
            $waiter->id = 1;
            $waiter->name = "Test Waiter";
            
            $call->setRelation('table', $table);
            $call->setRelation('waiter', $waiter);
            
            // Get instance of WaiterCallController and call the Firebase method
            $controller = new \App\Http\Controllers\WaiterCallController(
                app(\App\Services\FirebaseService::class),
                app(\App\Services\FirebaseRealtimeService::class)
            );
            
            // Use reflection to call private method
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('writeSimpleFirebaseRealtimeDB');
            $method->setAccessible(true);
            $result = $method->invoke($controller, $call);
            
            return response()->json([
                'test_firebase_write' => $result ? 'SUCCESS' : 'FAILED',
                'test_call_id' => 999,
                'firebase_url' => "https://mozoqr-7d32c-default-rtdb.firebaseio.com/waiters/1/calls/999.json",
                'timestamp' => now()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    });
    
    // Ruta original (fallback)
    Route::post('/tables/{table}/call-waiter-legacy', [WaiterCallController::class, 'callWaiter']);
    
    // API pública para información de QR codes
    Route::get('/qr/{restaurantSlug}/{tableCode}', [PublicQrController::class, 'getTableInfo'])
        ->name('api.qr.table.info');

    // API pública para obtener estado de mesa (polling fallback)
    Route::get('/table/{tableId}/status', [PublicQrController::class, 'getTableStatus'])
        ->name('api.table.status');

    // 🔗 STAFF - Unirse con token de invitación (público)
    Route::post('/staff/join/{token}', [StaffController::class, 'joinWithToken']);

    // 🔍 DEBUG: Endpoint para ver llamadas recientes (para testing)
    Route::get('/debug/recent-calls', [App\Http\Controllers\RealtimeWaiterCallController::class, 'getRecentCalls']);
    
    // 🔥 FIREBASE DIRECT ROUTE - BYPASS CONTROLLER CACHE - TEMPORARILY DISABLED TO AVOID DUPLICATES
    /*
    Route::post('/waiter-notifications', function(\Illuminate\Http\Request $request) {
        // Validar request
        $request->validate([
            'restaurant_id' => 'required|integer',
            'table_id' => 'required|integer',
            'message' => 'sometimes|string|max:500',
            'urgency' => 'sometimes|in:low,normal,high'
        ]);
        
        // Buscar mesa
        $table = \App\Models\Table::with(['activeWaiter', 'business'])->find($request->table_id);
        
        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Mesa no encontrada'
            ], 404);
        }
        
        if (!$table->active_waiter_id) {
            return response()->json([
                'success' => false,
                'message' => 'Esta mesa no tiene un mozo asignado actualmente'
            ], 422);
        }
        
        // Fix para waiter inexistente
        $actualWaiterId = $table->active_waiter_id;
        $waiterExists = \App\Models\User::where('id', $actualWaiterId)->exists();
        if (!$waiterExists && $actualWaiterId == 1) {
            $actualWaiterId = 2; // Usar waiter 2 si el 1 no existe
        }
        
        // Crear llamada
        $call = \App\Models\WaiterCall::create([
            'table_id' => $table->id,
            'waiter_id' => $actualWaiterId,
            'status' => 'pending',
            'message' => $request->input('message', 'Llamada desde mesa ' . $table->number),
            'called_at' => now(),
            'metadata' => [
                'urgency' => $request->input('urgency', 'normal'),
                'restaurant_id' => $request->input('restaurant_id'),
                'source' => 'qr_page_direct'
            ]
        ]);
        
        // 🔥 ESCRIBIR A FIREBASE INMEDIATAMENTE
        $firebaseData = [
            'id' => (string)$call->id,
            'table_number' => (int)$table->number,
            'table_id' => (int)$call->table_id,
            'message' => (string)$call->message,
            'urgency' => (string)($call->metadata['urgency'] ?? 'normal'),
            'status' => 'pending',
            'timestamp' => time() * 1000,
            'called_at' => time() * 1000,
            'waiter_id' => (string)$call->waiter_id
        ];
        
        \Illuminate\Support\Facades\Http::timeout(3)->put(
            "https://mozoqr-7d32c-default-rtdb.firebaseio.com/waiters/{$call->waiter_id}/calls/{$call->id}.json",
            $firebaseData
        );
        
        // 🔥 TAMBIÉN ESCRIBIR EN EL PATH QUE ESCUCHA EL CLIENTE
        $clientFirebaseData = [
            'status' => 'pending',
            'table_id' => (string)$call->table_id,
            'waiter_id' => (string)$call->waiter_id,
            'waiter_name' => $table->activeWaiter->name ?? 'Mozo',
            'called_at' => time() * 1000,
            'message' => $call->message
        ];
        
        \Illuminate\Support\Facades\Http::timeout(3)->put(
            "https://mozoqr-7d32c-default-rtdb.firebaseio.com/tables/call_status/{$call->id}.json",
            $clientFirebaseData
        );
        
        Log::debug('Firebase writes completed', [
            'call_id' => $call->id,
            'waiter_id' => $call->waiter_id,
            'table_id' => $call->table_id,
            'waiter_path' => "waiters/{$call->waiter_id}/calls/{$call->id}",
            'client_path' => "tables/call_status/{$call->id}"
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Notificación enviada al mozo exitosamente',
            'data' => [
                'id' => $call->id,
                'table_number' => $table->number,
                'waiter_name' => $table->activeWaiter->name ?? 'Mozo',
                'status' => 'pending',
                'called_at' => $call->called_at,
                'message' => $call->message,
                'firebase_written' => true,
                'route_used' => 'direct_bypass'
            ]
        ]);
    });
    */
    
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
    
    // SSE endpoints moved outside middleware groups for direct access
    
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

// 🔧 DIAGNÓSTICO TEMPORAL: Endpoint para debuggear subida de archivos
Route::middleware('auth:sanctum')->post('/debug/upload-test', function(Illuminate\Http\Request $request) {
    try {
        $user = $request->user();
        
        // Verificar autenticación
        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }
        
        // Información del usuario
        $userInfo = [
            'id' => $user->id,
            'name' => $user->name,
            'business_id' => $user->business_id,
            'email' => $user->email
        ];
        
        // Verificar si el archivo fue enviado
        $fileInfo = [];
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileInfo = [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'is_valid' => $file->isValid(),
                'error' => $file->getError(),
                'error_message' => $file->getErrorMessage(),
                'temp_path' => $file->getRealPath()
            ];
        } else {
            $fileInfo['error'] = 'No file uploaded';
        }
        
        // Verificar directorio storage
        $storagePath = storage_path('app/public/menus/' . $user->business_id);
        $storageInfo = [
            'path' => $storagePath,
            'exists' => file_exists($storagePath),
            'writable' => is_writable(dirname($storagePath)),
            'parent_writable' => is_writable(storage_path('app/public')),
        ];
        
        // Intentar crear directorio si no existe
        if (!file_exists($storagePath)) {
            try {
                mkdir($storagePath, 0755, true);
                $storageInfo['created'] = true;
            } catch (\Exception $e) {
                $storageInfo['create_error'] = $e->getMessage();
            }
        }
        
        // Información de espacio en disco
        $diskInfo = [
            'free_space' => disk_free_space(storage_path()),
            'total_space' => disk_total_space(storage_path()),
        ];
        
        // Límites PHP
        $phpInfo = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'file_uploads' => ini_get('file_uploads'),
            'upload_tmp_dir' => ini_get('upload_tmp_dir'),
        ];
        
        return response()->json([
            'success' => true,
            'debug_info' => [
                'user' => $userInfo,
                'file' => $fileInfo,
                'storage' => $storageInfo,
                'disk' => $diskInfo,
                'php' => $phpInfo,
                'request_data' => $request->all()
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
});

// 🔧 DIAGNÓSTICO: Endpoint para arreglar límites PHP
Route::middleware('auth:sanctum')->post('/debug/fix-php-limits', function(Illuminate\Http\Request $request) {
    try {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }
        
        $results = [];
        
        // Intentar modificar límites via ini_set (solo funciona para algunos valores)
        $limits = [
            'memory_limit' => '512M',
            'max_execution_time' => '300',
            'max_input_time' => '300'
        ];
        
        foreach ($limits as $setting => $value) {
            $oldValue = ini_get($setting);
            $success = ini_set($setting, $value);
            $newValue = ini_get($setting);
            
            $results[$setting] = [
                'old_value' => $oldValue,
                'attempted_value' => $value,
                'new_value' => $newValue,
                'success' => $success !== false && $newValue === $value
            ];
        }
        
        // Crear archivo .htaccess con límites de upload
        $htaccessPath = public_path('.htaccess');
        $htaccessExists = file_exists($htaccessPath);
        $htaccessContent = '';
        
        if ($htaccessExists) {
            $htaccessContent = file_get_contents($htaccessPath);
        }
        
        // Verificar si ya tiene configuración de upload
        $hasUploadConfig = strpos($htaccessContent, 'php_value upload_max_filesize') !== false;
        
        if (!$hasUploadConfig) {
            $uploadConfig = "\n# File Upload Limits\n";
            $uploadConfig .= "php_value upload_max_filesize 50M\n";
            $uploadConfig .= "php_value post_max_size 60M\n";
            $uploadConfig .= "php_value memory_limit 512M\n";
            $uploadConfig .= "php_value max_execution_time 300\n";
            $uploadConfig .= "php_value max_input_time 300\n\n";
            
            try {
                file_put_contents($htaccessPath, $htaccessContent . $uploadConfig);
                $results['htaccess'] = [
                    'created' => true,
                    'path' => $htaccessPath,
                    'content_added' => $uploadConfig
                ];
            } catch (\Exception $e) {
                $results['htaccess'] = [
                    'created' => false,
                    'error' => $e->getMessage()
                ];
            }
        } else {
            $results['htaccess'] = [
                'already_configured' => true,
                'path' => $htaccessPath
            ];
        }
        
        // Verificar límites actuales después de los cambios
        $currentLimits = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_time' => ini_get('max_input_time'),
        ];
        
        return response()->json([
            'success' => true,
            'results' => $results,
            'current_limits' => $currentLimits,
            'recommendations' => [
                'If .htaccess method fails, contact your hosting provider',
                'Alternative: Create php.ini file in public directory',
                'Plesk users: Check PHP Settings in control panel'
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
});