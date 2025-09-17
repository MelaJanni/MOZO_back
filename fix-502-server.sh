#!/bin/bash

echo "=== Configurando servidor para resolver errores 502 ==="

# Backup de configuraciones actuales
echo "1. Creando backup de configuraciones..."
sudo cp /etc/php/8.3/fpm/pool.d/www.conf /etc/php/8.3/fpm/pool.d/www.conf.backup
sudo cp /etc/php/8.3/fpm/php.ini /etc/php/8.3/fpm/php.ini.backup

# Configurar PHP-FPM
echo "2. Configurando PHP-FPM..."
sudo tee /etc/php/8.3/fpm/pool.d/www.conf > /dev/null << 'EOF'
[www]
user = www-data
group = www-data
listen = /run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 15
pm.max_requests = 500
pm.process_idle_timeout = 60s
php_admin_value[memory_limit] = 512M
php_admin_value[max_execution_time] = 300
php_admin_value[max_input_time] = 300
php_admin_value[post_max_size] = 64M
php_admin_value[upload_max_filesize] = 64M
EOF

# Configurar límites en php.ini
echo "3. Configurando PHP.ini..."
sudo sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/max_input_time = .*/max_input_time = 300/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 64M/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' /etc/php/8.3/fpm/php.ini

# Verificar configuración
echo "4. Verificando configuración..."
sudo php-fpm8.3 -t

# Reiniciar servicios
echo "5. Reiniciando servicios..."
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx

# Verificar estado
echo "6. Verificando estado de servicios..."
sudo systemctl status php8.3-fpm --no-pager
sudo systemctl status nginx --no-pager

echo "=== Configuración completada ==="
echo "Monitorea los logs con: journalctl -u php8.3-fpm -f"