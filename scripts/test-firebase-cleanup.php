#!/usr/bin/env php
<?php
/**
 * Script de Testing - Punto 9: Limpieza de Firebase
 * 
 * Este script verifica que la limpieza de Firebase funcione correctamente
 * al eliminar un negocio.
 * 
 * Uso:
 *   php scripts/test-firebase-cleanup.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Business;
use App\Models\Staff;
use App\Models\Table;
use App\Models\WaiterCall;
use App\Services\UnifiedFirebaseService;

echo "🔥 Testing Firebase Cleanup - Punto 9\n";
echo "=====================================\n\n";

// 1. Verificar que el servicio existe
echo "1. Verificando servicio UnifiedFirebaseService...\n";
try {
    $service = app(UnifiedFirebaseService::class);
    echo "   ✅ Servicio cargado correctamente\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error al cargar servicio: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Buscar un negocio de prueba
echo "2. Buscando negocio de prueba...\n";
$business = Business::with(['staff', 'tables'])->first();

if (!$business) {
    echo "   ⚠️  No hay negocios en la BBDD. Crea uno primero.\n";
    exit(0);
}

echo "   ✅ Negocio encontrado: {$business->name} (ID: {$business->id})\n";
echo "   📊 Staff: " . $business->staff->count() . "\n";
echo "   📊 Mesas: " . $business->tables->count() . "\n\n";

// 3. Simular limpieza de Firebase (sin eliminar BBDD)
echo "3. Simulando limpieza de Firebase...\n";
echo "   ⚠️  NOTA: Esto NO eliminará la BBDD, solo Firebase\n";
echo "   ⚠️  Para testing real, usa un negocio de prueba\n\n";

// Preguntar confirmación
echo "¿Deseas continuar con la simulación? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'y') {
    echo "\n❌ Cancelado por el usuario\n";
    exit(0);
}

echo "\n🚀 Ejecutando limpieza de Firebase...\n\n";

try {
    $result = $service->deleteBusinessData($business->id);
    
    echo "📊 Resultado de la limpieza:\n";
    echo "   Status: " . ($result['success'] ? '✅ SUCCESS' : '❌ FAILED') . "\n";
    echo "   Rutas eliminadas: " . $result['summary']['total_deleted'] . "\n";
    echo "   Errores: " . $result['summary']['total_errors'] . "\n\n";
    
    if (!empty($result['deleted_paths'])) {
        echo "📋 Rutas eliminadas:\n";
        foreach ($result['deleted_paths'] as $path) {
            echo "   - {$path}\n";
        }
        echo "\n";
    }
    
    if (!empty($result['errors'])) {
        echo "⚠️  Errores encontrados:\n";
        foreach ($result['errors'] as $error) {
            echo "   - {$error}\n";
        }
        echo "\n";
    }
    
    echo "✅ Test completado\n";
    echo "\n";
    echo "🔍 Verificación manual:\n";
    echo "   1. Ir a: https://console.firebase.google.com/\n";
    echo "   2. Realtime Database\n";
    echo "   3. Verificar que se eliminaron las rutas mostradas arriba\n";
    
} catch (\Exception $e) {
    echo "❌ Error durante la limpieza: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✅ Script completado exitosamente\n";
