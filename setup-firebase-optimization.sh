#!/bin/bash

# =====================================================
# CONFIGURACIÓN DE QUEUES Y OPTIMIZACIÓN FIREBASE
# =====================================================

echo "🚀 Configurando sistema de notificaciones optimizado..."

# 1. CREAR MIGRACIÓN PARA TABLA DE JOBS (si no existe)
echo "📦 Creando tabla de jobs..."
php artisan queue:table
php artisan migrate

# 2. CREAR MIGRACIÓN PARA TABLA FAILED_JOBS (si no existe)
echo "📦 Creando tabla de failed jobs..."
php artisan queue:failed-table
php artisan migrate

# 3. ACTUALIZAR .env
echo "🔧 Actualizando configuración .env..."
cat >> .env << 'EOF'

# OPTIMIZACIÓN DE QUEUES
QUEUE_CONNECTION=database
BROADCAST_DRIVER=null
CACHE_DRIVER=redis

# FIREBASE OPTIMIZADO
FIREBASE_PROJECT_ID=mozoqr-7d32c
FIREBASE_CREDENTIALS_PATH=storage/app/firebase/mozoqr-7d32c-firebase-adminsdk-fbsvc-0a90bdb0a0.json

# CONFIGURACIÓN DE WORKERS
QUEUE_WORKER_MEMORY=256
QUEUE_WORKER_TIMEOUT=60
QUEUE_WORKER_TRIES=3
EOF

# 4. LIMPIAR CACHE
echo "🧹 Limpiando cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 5. OPTIMIZAR PARA PRODUCCIÓN
echo "⚡ Optimizando para producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev

# 6. CREAR SUPERVISOR CONFIG
echo "📝 Creando configuración de Supervisor..."
sudo tee /etc/supervisor/conf.d/mozo-queue-workers.conf > /dev/null << 'EOF'
[program:mozo-high-priority]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/vhosts/mozoqr.com/httpdocs/artisan queue:work database --queue=high-priority --sleep=1 --tries=3 --timeout=30 --max-jobs=1000 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/var/log/supervisor/mozo-high-priority.log
stopwaitsecs=10
stopsignal=TERM
priority=999

[program:mozo-notifications]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/vhosts/mozoqr.com/httpdocs/artisan queue:work database --queue=notifications --sleep=2 --tries=3 --timeout=60 --max-jobs=1000 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/mozo-notifications.log
stopwaitsecs=10
stopsignal=TERM
priority=998

[program:mozo-default]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/vhosts/mozoqr.com/httpdocs/artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=90 --max-jobs=500 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/mozo-default.log
stopwaitsecs=10
stopsignal=TERM
priority=997
EOF

# 7. REINICIAR SUPERVISOR
echo "🔄 Reiniciando Supervisor..."
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all

# 8. CREAR COMANDO DE TEST
echo "🧪 Creando comando de test..."
cat > test-notification-speed.php << 'EOF'
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\FirebaseService;
use App\Services\FirebaseRealtimeService;
use App\Models\User;
use App\Models\Table;

echo "🚀 TEST DE VELOCIDAD DE NOTIFICACIONES\n";
echo "=====================================\n\n";

$firebaseService = app(FirebaseService::class);
$testUserId = 1;

echo "1. Testing FCM Push Notification...\n";
$start = microtime(true);
$result = $firebaseService->testNotificationSpeed($testUserId);
echo "   ✅ Tiempo de envío: {$result['execution_time_ms']}ms\n";
echo "   📊 Resultado: " . ($result['success'] ? 'EXITOSO' : 'FALLÓ') . "\n\n";

$firestoreService = app(FirebaseRealtimeService::class);

echo "2. Testing Firestore Write Speed...\n";
$latencyTest = $firestoreService->testParallelPerformance(5);
echo "   ✅ Escritura Secuencial: {$latencyTest['sequential']}ms\n";
echo "   ✅ Escritura Paralela: {$latencyTest['parallel']}ms\n";
echo "   📊 Mejora: {$latencyTest['improvement']}%\n\n";

echo "3. Testing End-to-End (Llamada de Mozo)...\n";
$start = microtime(true);
$table = Table::first();
if ($table) {
    $response = app('App\Http\Controllers\WaiterCallController')->create(
        new \Illuminate\Http\Request([
            'table_id' => $table->id,
            'message' => 'Test de velocidad',
            'urgency' => 'high'
        ])
    );
    $endToEnd = (microtime(true) - $start) * 1000;
    echo "   ✅ Tiempo total End-to-End: {$endToEnd}ms\n";
    echo "   📊 Response: " . json_encode($response->getData()) . "\n";
}

echo "\n=====================================\n";
echo "✨ TEST COMPLETADO\n";
echo "\nTiempos objetivo:\n";
echo "- FCM Push: < 500ms\n";
echo "- Firestore Paralelo: < 200ms\n";
echo "- End-to-End: < 1000ms\n";
EOF

echo "✅ Verificando configuración..."
php artisan queue:work --status
php artisan config:show queue

echo "📊 Configurando monitoreo..."
cat > monitor-notifications.sh << 'EOF'
echo "📊 MONITOR DE NOTIFICACIONES MOZO"
echo "=================================="
echo ""

while true; do
    clear
    echo "📊 MONITOR DE NOTIFICACIONES - $(date)"
    echo "=================================="
    echo ""
    
    echo "📦 Jobs en cola:"
    php artisan queue:monitor high-priority:10,notifications:25,default:50
    echo ""
    
    FAILED=$(php artisan queue:failed | wc -l)
    echo "❌ Jobs fallidos: $((FAILED-4))"
    echo ""
    
    echo "👷 Workers activos:"
    sudo supervisorctl status | grep mozo
    echo ""
    
    echo "📝 Últimos eventos:"
    tail -n 5 /var/log/supervisor/mozo-high-priority.log
    
    sleep 5
done
EOF

chmod +x monitor-notifications.sh

echo ""
echo "✅ CONFIGURACIÓN COMPLETADA"
echo "============================"
echo ""
echo "📋 Siguientes pasos:"
echo "1. Verificar que Redis esté instalado: redis-cli ping"
echo "2. Iniciar workers: sudo supervisorctl start mozo-high-priority:*"
echo "3. Probar velocidad: php test-notification-speed.php"
echo "4. Monitorear: ./monitor-notifications.sh"
echo ""
echo "🎯 Con esta configuración, las notificaciones deberían llegar en < 2 segundos"