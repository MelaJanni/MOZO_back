#!/bin/bash

echo "🧹 Limpiando TODOS los cachés de Laravel..."

# Limpiar cachés de Laravel
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Limpiar caché de Opcache si está habilitado
php artisan optimize:clear

# Limpiar cachés del sistema de archivos
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Cachés limpiados!"
echo ""
echo "🔄 Pasos adicionales recomendados:"
echo "1. Presiona Ctrl+F5 en tu navegador (hard refresh)"
echo "2. O abre en modo incógnito"
echo "3. Verifica que el timestamp del CSS haya cambiado"
echo ""
echo "📁 Archivo CSS: public/css/pdf-viewer.css"
echo "🌐 URL: https://mozoqr.com/QR/mcdonalds/JoA4vw"