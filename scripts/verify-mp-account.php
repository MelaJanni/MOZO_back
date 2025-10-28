#!/usr/bin/env php
<?php

// Verificar cuenta de MercadoPago asociada al access token

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$token = config('services.mercado_pago.access_token');

if (!$token) {
    echo "❌ No se encontró access token configurado\n";
    exit(1);
}

echo "🔍 Verificando cuenta de MercadoPago...\n\n";

try {
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->get('https://api.mercadopago.com/users/me');

    if ($response->successful()) {
        $data = $response->json();
        
        echo "✅ Información de la cuenta:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "User ID: " . ($data['id'] ?? 'N/A') . "\n";
        echo "Email: " . ($data['email'] ?? 'N/A') . "\n";
        echo "Nickname: " . ($data['nickname'] ?? 'N/A') . "\n";
        echo "First Name: " . ($data['first_name'] ?? 'N/A') . "\n";
        echo "Last Name: " . ($data['last_name'] ?? 'N/A') . "\n";
        echo "Site ID: " . ($data['site_id'] ?? 'N/A') . "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        echo "📝 Este User ID debe coincidir con los usuarios de prueba que creaste.\n";
        echo "   Si no coincide, necesitas actualizar el access token.\n";
        
    } else {
        echo "❌ Error al consultar API de MercadoPago\n";
        echo "Status: " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
