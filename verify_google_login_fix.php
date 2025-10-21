<?php

/**
 * Script de prueba para verificar el fix del login con Google
 * 
 * Este script verifica:
 * 1. Que el UserObserver esté usando DB::afterCommit y firstOrCreate
 * 2. Que AuthController no cree manualmente el WaiterProfile
 * 3. Que no haya transacciones anidadas problemáticas
 */

echo "=== VERIFICACIÓN DEL FIX DE GOOGLE LOGIN ===\n\n";

// Verificar UserObserver
$observerPath = __DIR__ . '/app/Observers/UserObserver.php';
$observerContent = file_get_contents($observerPath);

echo "1. Verificando UserObserver...\n";

if (strpos($observerContent, 'DB::afterCommit') !== false) {
    echo "   ✅ Usa DB::afterCommit (correcto)\n";
} else {
    echo "   ❌ NO usa DB::afterCommit\n";
}

if (strpos($observerContent, 'firstOrCreate') !== false) {
    echo "   ✅ Usa firstOrCreate (correcto)\n";
} else {
    echo "   ❌ NO usa firstOrCreate\n";
}

if (strpos($observerContent, 'DB::transaction(function () use ($user)') !== false) {
    echo "   ❌ Todavía usa transacciones anidadas (MALO)\n";
} else {
    echo "   ✅ No usa transacciones anidadas (correcto)\n";
}

// Verificar AuthController
echo "\n2. Verificando AuthController...\n";

$authPath = __DIR__ . '/app/Http/Controllers/AuthController.php';
$authContent = file_get_contents($authPath);

if (strpos($authContent, '$user->waiterProfile()->create') !== false) {
    echo "   ❌ Todavía crea WaiterProfile manualmente\n";
} else {
    echo "   ✅ No crea WaiterProfile manualmente (correcto)\n";
}

if (strpos($authContent, 'El UserObserver creará automáticamente el WaiterProfile') !== false) {
    echo "   ✅ Tiene comentario explicativo (correcto)\n";
} else {
    echo "   ⚠️  Falta comentario explicativo\n";
}

if (strpos($authContent, '$user->refresh()') !== false) {
    echo "   ✅ Refresca el usuario antes de retornar (correcto)\n";
} else {
    echo "   ❌ No refresca el usuario\n";
}

// Verificar comando de fix
echo "\n3. Verificando comando FixMissingWaiterProfiles...\n";

$commandPath = __DIR__ . '/app/Console/Commands/FixMissingWaiterProfiles.php';
if (file_exists($commandPath)) {
    echo "   ✅ Comando existe\n";
    $commandContent = file_get_contents($commandPath);
    if (strpos($commandContent, 'firstOrCreate') !== false) {
        echo "   ✅ Usa firstOrCreate (correcto)\n";
    }
} else {
    echo "   ❌ Comando NO existe\n";
}

echo "\n=== RESUMEN ===\n";
echo "Si todos los checks están en ✅, el fix está completo.\n";
echo "Si hay ❌, revisa los archivos indicados.\n\n";
echo "Para aplicar el fix a usuarios existentes, ejecuta:\n";
echo "php artisan fix:missing-waiter-profiles\n";
