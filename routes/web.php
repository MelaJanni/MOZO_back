<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QrWebController;
use App\Http\Controllers\ApiDocumentationController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/password/reset/{token}', function ($token) {
    $email = request()->query('email');
    
    $appScheme = env('MOBILE_APP_SCHEME', 'mozo');
    $deepLink = $appScheme . '://reset-password?token=' . $token . '&email=' . urlencode($email);
    
    // HTML de la página que muestra instrucciones y/o intenta abrir la app
    return '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Restablecer contraseña - MOZO</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 500px;
                margin: 0 auto;
                padding: 20px;
                text-align: center;
            }
            .logo {
                max-width: 150px;
                margin: 20px auto;
            }
            .card {
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                padding: 30px;
                margin-top: 30px;
            }
            h1 {
                font-size: 24px;
                margin-bottom: 20px;
            }
            p {
                margin-bottom: 25px;
                color: #666;
            }
            .btn {
                display: inline-block;
                background: #4A6FFF;
                color: white;
                text-decoration: none;
                padding: 12px 30px;
                border-radius: 50px;
                font-weight: bold;
                margin-bottom: 15px;
            }
            .help {
                font-size: 14px;
                color: #888;
                margin-top: 30px;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Restablecer tu contraseña</h1>
            <p>Haz clic en el botón para abrir la aplicación MOZO y completar el proceso de restablecimiento de contraseña.</p>
            <a href="' . $deepLink . '" class="btn">Abrir aplicación</a>
            <p class="help">Si el botón no funciona, asegúrate de tener la aplicación MOZO instalada en tu dispositivo.</p>
        </div>
        <script>
            window.location.href = "' . $deepLink . '";
        </script>
    </body>
    </html>';
})->name('password.reset');

// Ruta de prueba simple
Route::get('/test-simple', function() {
    return response()->json(['status' => 'working', 'message' => 'Routes are working!']);
});

// Ruta de prueba QR
Route::get('/test-qr', [QrWebController::class, 'testQr'])
    ->name('qr.test');

// Ruta de debug para verificar datos
Route::get('/debug-qr-data', [QrWebController::class, 'debugData'])
    ->name('qr.debug');

// Ruta para setup datos de prueba
Route::get('/setup-test-data', [QrWebController::class, 'setupTestData'])
    ->name('qr.setup');

// Ruta para mostrar página de mesa desde QR
Route::get('/QR/{restaurantSlug}/{tableCode}', [QrWebController::class, 'showTablePage'])
    ->name('qr.table.page');

// Documentación de APIs
Route::get('/api/docs/qr', [ApiDocumentationController::class, 'qrApis'])
    ->name('api.docs.qr');
Route::get('/api/docs/waiter', [ApiDocumentationController::class, 'waiterApis'])
    ->name('api.docs.waiter');

