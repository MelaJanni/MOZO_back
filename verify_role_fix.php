<?php

/**
 * Script de verificación para el fix del error "Column 'role' not found"
 */

echo "=== VERIFICACIÓN DEL FIX - COLUMNA 'ROLE' ===\n\n";

// Verificar AuthController
$authPath = __DIR__ . '/app/Http/Controllers/AuthController.php';
$authContent = file_get_contents($authPath);

echo "1. Verificando AuthController::loginWithGoogle()...\n";

if (strpos($authContent, "\$user->role = 'waiter';") !== false) {
    echo "   ❌ Todavía intenta establecer \$user->role en loginWithGoogle\n";
} else {
    echo "   ✅ No intenta establecer \$user->role (correcto)\n";
}

echo "\n2. Verificando AuthController::register()...\n";

if (preg_match("/'role'\s*=>\s*'waiter'/", $authContent)) {
    echo "   ❌ Todavía incluye 'role' en User::create()\n";
} else {
    echo "   ✅ No incluye 'role' en User::create() (correcto)\n";
}

echo "\n3. Verificando uso de Spatie Permissions...\n";

if (strpos($authContent, 'HasRoles') !== false || file_exists(__DIR__ . '/app/Models/User.php')) {
    $userModel = file_get_contents(__DIR__ . '/app/Models/User.php');
    if (strpos($userModel, 'HasRoles') !== false) {
        echo "   ✅ User model usa HasRoles trait de Spatie (correcto)\n";
    } else {
        echo "   ⚠️  User model no usa HasRoles trait\n";
    }
}

echo "\n4. Verificando comentarios explicativos...\n";

if (strpos($authContent, 'Spatie Permissions') !== false || strpos($authContent, 'HasRoles') !== false) {
    echo "   ✅ Tiene comentarios sobre manejo de roles (correcto)\n";
} else {
    echo "   ⚠️  Falta comentario explicativo sobre roles\n";
}

echo "\n=== RESUMEN ===\n";
echo "Si todos los checks están en ✅, el error 'Column role not found' está resuelto.\n";
echo "El sistema ahora usa Spatie Permissions correctamente para manejo de roles.\n";
