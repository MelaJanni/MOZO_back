#!/bin/bash
# Script para aplicar cambios de .env en producción

echo "🔄 Actualizando configuración de MercadoPago..."

# Limpiar caché de configuración
php artisan config:clear
echo "✅ Config cache cleared"

# Limpiar caché de aplicación
php artisan cache:clear
echo "✅ Application cache cleared"

# Limpiar caché de rutas (opcional pero recomendado)
php artisan route:clear
echo "✅ Routes cache cleared"

# Verificar las credenciales
echo ""
echo "📋 Verificando credenciales actuales:"
php artisan tinker --execute="
echo 'Access Token: ' . (config('services.mercado_pago.access_token') ? substr(config('services.mercado_pago.access_token'), 0, 20) . '...' : 'NOT SET') . PHP_EOL;
echo 'Public Key: ' . (config('services.mercado_pago.public_key') ? substr(config('services.mercado_pago.public_key'), 0, 20) . '...' : 'NOT SET') . PHP_EOL;
echo 'Environment: ' . config('services.mercado_pago.environment', 'NOT SET') . PHP_EOL;
"

echo ""
echo "✅ ¡Listo! Ahora prueba el checkout de nuevo."
