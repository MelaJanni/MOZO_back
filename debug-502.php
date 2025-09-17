<?php

// Debug script temporal para identificar el problema 502
$logFile = __DIR__ . '/storage/logs/manual-debug.log';

try {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] INICIO DEBUG MANUAL\n", FILE_APPEND);

    // 1. Verificar bootstrap de Laravel
    require_once __DIR__ . '/bootstrap/autoload.php';
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Autoload completado\n", FILE_APPEND);

    $app = require_once __DIR__ . '/bootstrap/app.php';
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] App bootstrap completado\n", FILE_APPEND);

    // 2. Verificar base de datos
    $pdo = new PDO('mysql:host=localhost;dbname=mozoqr_database', 'root', '');
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Conexión DB exitosa\n", FILE_APPEND);

    // 3. Intentar cargar Filament
    if (class_exists('\\Filament\\Resources\\Resource')) {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Filament Resource class existe\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: Filament Resource class NO existe\n", FILE_APPEND);
    }

    // 4. Intentar cargar UserResource
    if (class_exists('\\App\\Filament\\Resources\\UserResource')) {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] UserResource class existe\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: UserResource class NO existe\n", FILE_APPEND);
    }

    // 5. Intentar cargar EditUser
    if (class_exists('\\App\\Filament\\Resources\\UserResource\\Pages\\EditUser')) {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] EditUser class existe\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: EditUser class NO existe\n", FILE_APPEND);
    }

    echo "Debug completado - revisar logs/manual-debug.log";

} catch (Exception $e) {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR CAPTURADO: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] TRACE: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    echo "Error: " . $e->getMessage();
}