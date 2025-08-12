#!/bin/bash

# Script para corregir permisos después de git pull
# Ejecutar como: sudo ./fix-permissions.sh

echo "Corrigiendo permisos..."

# Corregir propietario (cambiar www-data por el usuario correcto si es necesario)
chown -R www-data:www-data /var/www/vhosts/mozoqr.com/httpdocs

# Permisos para directorios
find /var/www/vhosts/mozoqr.com/httpdocs -type d -exec chmod 755 {} \;

# Permisos para archivos
find /var/www/vhosts/mozoqr.com/httpdocs -type f -exec chmod 644 {} \;

# Permisos especiales para Laravel
chmod -R 775 /var/www/vhosts/mozoqr.com/httpdocs/storage
chmod -R 775 /var/www/vhosts/mozoqr.com/httpdocs/bootstrap/cache

# Hacer el script ejecutable
chmod +x /var/www/vhosts/mozoqr.com/httpdocs/fix-permissions.sh

echo "Permisos corregidos"

# Reiniciar servicios
systemctl restart php8.3-fpm
systemctl reload nginx

echo "Servicios reiniciados"
echo "Limpiando cache de Laravel..."

cd /var/www/vhosts/mozoqr.com/httpdocs
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo "¡Listo!"