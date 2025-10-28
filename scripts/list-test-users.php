<?php

/**
 * Script para listar los usuarios de prueba de MercadoPago
 * 
 * Uso: php scripts/list-test-users.php
 */

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$accessToken = $_ENV['MERCADO_PAGO_ACCESS_TOKEN'];

echo "🔍 Listando usuarios de prueba de tu cuenta...\n\n";

// Endpoint para listar usuarios de prueba
$url = 'https://api.mercadopago.com/users/test_user';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n\n";

if ($httpCode === 200) {
    $users = json_decode($response, true);
    
    if (empty($users)) {
        echo "⚠️  No hay usuarios de prueba creados en esta cuenta.\n";
        echo "✅ Puedes usar el usuario creado por API:\n";
        echo "   - Username: TESTUSER8074686931900071084\n";
        echo "   - Password: UshpTq58q5\n";
        echo "   - Site: MLB\n";
    } else {
        echo "✅ Usuarios de prueba encontrados:\n\n";
        foreach ($users as $user) {
            echo "----------------------------------------\n";
            echo "ID: " . ($user['id'] ?? 'N/A') . "\n";
            echo "Username: " . ($user['nickname'] ?? 'N/A') . "\n";
            echo "Email: " . ($user['email'] ?? 'N/A') . "\n";
            echo "Site: " . ($user['site_id'] ?? 'N/A') . "\n";
            echo "----------------------------------------\n\n";
        }
    }
} else {
    echo "❌ Error al obtener usuarios de prueba\n";
    echo "Respuesta:\n";
    print_r(json_decode($response, true));
}

echo "\n💡 RECUERDA: Solo puedes usar usuarios de prueba creados desde TU cuenta (230980817)\n";
