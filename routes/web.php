<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\QrWebController;
use App\Http\Controllers\ApiDocumentationController;
use ScssPhp\ScssPhp\Compiler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;


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

// Ruta para crear menú de prueba McDonalds
Route::get('/create-test-menu', [QrWebController::class, 'createTestMenu'])
    ->name('qr.create-menu');

// Ruta para arreglar problemas QR
Route::get('/fix-qr-issues', [QrWebController::class, 'fixQrIssues'])
    ->name('qr.fix');

// Ruta para asignar mozo a mesa específica
Route::get('/assign-waiter-to-table/{tableCode}', [QrWebController::class, 'assignWaiterToTable'])
    ->name('qr.assign.waiter');

// Ruta para limpiar asignaciones de mesas huérfanas
Route::get('/clean-orphan-tables', [QrWebController::class, 'cleanOrphanTables'])
    ->name('qr.clean.orphans');

// Ruta para forzar reasignación de mesa específica
Route::get('/force-assign-table/{tableId}/{waiterId}', [QrWebController::class, 'forceAssignTable'])
    ->name('qr.force.assign');

// Ruta para mostrar página de mesa desde QR
Route::get('/QR/{restaurantSlug}/{tableCode}', [QrWebController::class, 'showTablePage'])
    ->name('qr.table.page');


// Ruta para llamar al mozo desde QR (PHP form submission)
Route::post('/QR/call-waiter', [QrWebController::class, 'callWaiter'])
    ->name('waiter.call');

// Servir PDFs de menús públicamente
Route::get('/menu/pdf/{business_id}/{filename}', function($business_id, $filename) {
    try {
        // Validar que business_id sea numérico
        if (!is_numeric($business_id)) {
            abort(404, 'Negocio no válido');
        }

        // Sanitizar el nombre del archivo para prevenir path traversal
        $filename = basename($filename);
        $path = storage_path('app/public/menus/' . $business_id . '/' . $filename);
        
        // Verificar que el archivo existe
        if (!file_exists($path)) {
            Log::warning('PDF menú no encontrado', [
                'business_id' => $business_id,
                'filename' => $filename,
                'path' => $path
            ]);
            abort(404, 'Menú no encontrado');
        }

        // Verificar que es realmente un PDF
        $mimeType = mime_content_type($path);
        if ($mimeType !== 'application/pdf') {
            Log::warning('Archivo de menú no es PDF válido', [
                'business_id' => $business_id,
                'filename' => $filename,
                'mime_type' => $mimeType
            ]);
            abort(415, 'Archivo no es un PDF válido');
        }

        // Verificar tamaño del archivo (máximo 50MB)
        $fileSize = filesize($path);
        if ($fileSize > 50 * 1024 * 1024) {
            Log::warning('PDF menú demasiado grande', [
                'business_id' => $business_id,
                'filename' => $filename,
                'size_mb' => round($fileSize / 1024 / 1024, 2)
            ]);
            abort(413, 'Archivo demasiado grande');
        }

        Log::info('Sirviendo PDF de menú', [
            'business_id' => $business_id,
            'filename' => $filename,
            'size_kb' => round($fileSize / 1024, 2)
        ]);
        
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'public, max-age=3600', // Cache por 1 hora
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN'
        ]);

    } catch (\Exception $e) {
        Log::error('Error sirviendo PDF de menú', [
            'business_id' => $business_id,
            'filename' => $filename,
            'error' => $e->getMessage(),
            'trace_head' => collect(explode("\n", $e->getTraceAsString()))->take(5)->implode(" | "),
            'exists' => file_exists($path ?? '') ? 'yes':'no'
        ]);
        return response()->json([
            'error' => 'PDF_INTERNAL_ERROR',
            'message' => 'No se pudo servir el PDF',
            'hint' => 'Ver logs para más detalles',
        ],500);
    }
})->name('menu.pdf');

// Documentación de APIs
Route::get('/api/docs/qr', [ApiDocumentationController::class, 'qrApis'])
    ->name('api.docs.qr');
Route::get('/api/docs/waiter', [ApiDocumentationController::class, 'waiterApis'])
    ->name('api.docs.waiter');

// Servir SCSS pdf-viewer en vivo (sin cache) - solo para entorno local / staging
Route::get('/live-scss/pdf-viewer.css', function() {
    $path = resource_path('css/pdf-viewer.scss');
    if(!file_exists($path)) abort(404);
    $scss = file_get_contents($path);
    // Si la librería no está disponible devolvemos el CSS ya compilado (si existe) como fallback
    if(!class_exists(Compiler::class)) {
        $fallback = public_path('css/pdf-viewer.css');
        if(file_exists($fallback)) {
            return response(file_get_contents($fallback), 200, [
                'Content-Type' => 'text/css; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0'
            ]);
        }
        return response("/* scssphp no instalado; instale scssphp/scssphp o compile manualmente */",200,["Content-Type"=>"text/css"]);
    }
    $compiler = new Compiler();
    $compiler->setOutputStyle(\ScssPhp\ScssPhp\OutputStyle::COMPRESSED);
    try {
        $css = $compiler->compileString($scss)->getCss();
    } catch(\Throwable $e) {
        return response("/* Error compilando SCSS: ".$e->getMessage()." */",200,["Content-Type"=>"text/css"]);
    }
    return response($css, 200, [
        'Content-Type' => 'text/css; charset=UTF-8',
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache'
    ]);
})->name('live.pdf.viewer.css');

// Ruta para limpiar cachés (sin token; restringe por IP si deseas)
Route::get('/admin/clear-caches', function(){
    $results = [];
    foreach(['config:clear','route:clear','view:clear','cache:clear'] as $cmd){
        try { Artisan::call($cmd); $results[$cmd] = trim(Artisan::output()); } catch(\Throwable $e){ $results[$cmd] = 'ERROR: '.$e->getMessage(); }
    }
    if(function_exists('opcache_reset')) {
        $ok = opcache_reset();
        $results['opcache_reset'] = $ok ? 'OK' : 'FALLÓ';
    } else {
        $results['opcache_reset'] = 'NO DISPONIBLE';
    }
    return response()->json(['status'=>'ok','cleared'=>$results,'timestamp'=>now()->toDateTimeString()]);
});

// Debug hashes para verificar qué archivo se está leyendo
Route::get('/debug/pdf-style-hash', function(){
    $scssPath = resource_path('css/pdf-viewer.scss');
    $pubPath  = public_path('css/pdf-viewer.css');
    return response()->json([
        'resource_exists' => file_exists($scssPath),
        'resource_mtime'  => file_exists($scssPath)?date('c', filemtime($scssPath)):null,
        'resource_md5'    => file_exists($scssPath)?md5_file($scssPath):null,
        'public_exists'   => file_exists($pubPath),
        'public_mtime'    => file_exists($pubPath)?date('c', filemtime($pubPath)):null,
        'public_md5'      => file_exists($pubPath)?md5_file($pubPath):null,
    ]);
});

