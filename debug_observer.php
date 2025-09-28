<?php

// Script para verificar qué código está en el servidor
require_once __DIR__ . '/vendor/autoload.php';

echo "=== DEBUG DEL USEROBSERVER ===\n";

$observerPath = __DIR__ . '/app/Observers/UserObserver.php';
if (file_exists($observerPath)) {
    $content = file_get_contents($observerPath);

    echo "Archivo UserObserver existe\n";

    if (strpos($content, 'firstOrCreate') !== false) {
        echo "✅ Contiene firstOrCreate\n";
    } else {
        echo "❌ NO contiene firstOrCreate\n";
    }

    if (strpos($content, 'WaiterProfile::create') !== false) {
        echo "❌ Todavía usa WaiterProfile::create\n";
    } else {
        echo "✅ NO usa WaiterProfile::create\n";
    }

    echo "\n=== CÓDIGO ACTUAL ===\n";
    // Mostrar el método created
    preg_match('/public function created.*?}.*?}/s', $content, $matches);
    if (!empty($matches)) {
        echo $matches[0] . "\n";
    }
} else {
    echo "❌ Archivo UserObserver NO existe\n";
}