<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\WaiterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login/google', [AuthController::class, 'loginWithGoogle']); 
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


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

    // Rutas para tokens de dispositivo FCM
    Route::post('/device-token', [ProfileController::class, 'storeDeviceToken']);
    Route::delete('/device-token', [ProfileController::class, 'deleteDeviceToken']);

    // --- Rutas con prefijo /admin para compatibilidad con el frontend ---
    Route::prefix('admin')->group(function () {
        // Staff
        Route::delete('/staff/{staffId}', [AdminController::class, 'removeStaff']);
        Route::post('/staff/request/{requestId}', [AdminController::class, 'handleStaffRequest']);
        Route::get('/staff/requests', [AdminController::class, 'fetchStaffRequests']);
        Route::get('/staff/requests/archived', [AdminController::class, 'fetchArchivedRequests']);
        Route::post('/staff/onboard', [WaiterController::class, 'onboardBusiness']);

        // Otros recursos (por si el frontend usa /admin/...)
        Route::get('/business', [AdminController::class, 'getBusinessInfo']);
        Route::post('/switch-view', [AdminController::class, 'switchView']);
        Route::get('/settings', [AdminController::class, 'getSettings']);
        Route::post('/settings', [AdminController::class, 'updateSettings']);

        // Rutas de QR
        Route::post('/qr/generate/{tableId}', [QrCodeController::class, 'generateQRCode']);
        Route::get('/qr/preview/{tableId}', [QrCodeController::class, 'preview']);
        Route::post('/qr/export', [QrCodeController::class, 'exportQR']);
        Route::post('/qr/email', [QrCodeController::class, 'emailQR']);

        // Notificaciones generales para administrador
        Route::get('/notifications', [WaiterController::class, 'listNotifications']);
        Route::post('/notifications/test', [AdminController::class, 'sendTestNotification']);

        // Nuevas rutas de staff solicitadas
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

    // Clonar mesa
    Route::post('/tables/clone/{tableId}', [TableController::class, 'cloneTable']);

    // Grupo de rutas específicas para MOZOS (alias con prefijo /waiter para compatibilidad con el frontend)
    Route::prefix('waiter')->group(function () {
        // On-boarding a un negocio
        Route::post('/onboard', [WaiterController::class, 'onboardBusiness']);

        // Mesas del mozo
        Route::get('/tables', [WaiterController::class, 'fetchWaiterTables']);
        Route::post('/tables/toggle-notifications/{tableId}', [WaiterController::class, 'toggleTableNotifications']);
        Route::post('/tables/clone/{tableId}', [TableController::class, 'cloneTable']); // opcional

        // Notificaciones
        Route::get('/notifications', [WaiterController::class, 'fetchWaiterNotifications']);
        Route::post('/notifications/handle/{notificationId}', [WaiterController::class, 'handleNotification']);
        Route::post('/notifications/global', [WaiterController::class, 'globalNotifications']);

        // Perfiles de mesas
        Route::get('/profiles', [WaiterController::class, 'listProfiles']);
        Route::post('/profiles', [WaiterController::class, 'createProfile']);
        Route::delete('/profiles/{id}', [WaiterController::class, 'deleteProfile']);
    });

    // ---- Rutas para gestión de cuenta del usuario ----
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);

    // ---- Ruta de estadísticas del dashboard de administrador ----
    Route::get('/admin/statistics', [AdminController::class, 'getStatistics']);
});

    
Route::post('/tables/{table}/call-waiter', function (App\Models\Table $table) {
    if (!$table->notifications_enabled) {
        return response()->json(['message' => 'Las notificaciones están desactivadas para esta mesa'], 400);
    }

    $waiters = App\Models\User::where('business_id', $table->business_id)
        ->where('role', 'waiter')
        ->get();

    foreach ($waiters as $waiter) {
        $waiter->notify(new App\Notifications\TableCalledNotification($table));
    }

    return response()->json(['message' => 'Llamada al camarero enviada exitosamente']);
});