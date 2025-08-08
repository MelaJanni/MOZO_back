# 🚨 NGINX CONFIGURATION FIX - mozoqr.com Refused Connection

## 📋 DIAGNÓSTICO DEL PROBLEMA

El error "refused mozoqr.com" indica varios problemas posibles:

1. **Nginx no está corriendo**
2. **Configuración de virtual host incorrecta**
3. **Problemas de DNS/firewall**
4. **Conflicto de puertos**
5. **Permisos incorrectos**

## 🔧 SOLUCIÓN PASO A PASO

### 1. Verificar Estado de Nginx

```bash
# Conectar al servidor
ssh usuario@mozoqr.com

# Verificar si nginx está corriendo
sudo systemctl status nginx

# Si no está corriendo, iniciarlo
sudo systemctl start nginx
sudo systemctl enable nginx

# Verificar que escuche en puerto 80 y 443
sudo netstat -tulpn | grep nginx
# O alternativamente:
sudo ss -tulpn | grep nginx
```

### 2. Verificar Configuración de Nginx

```bash
# Verificar sintaxis de configuración
sudo nginx -t

# Ver configuración actual
sudo nginx -T

# Verificar configuración del sitio
ls -la /etc/nginx/sites-available/
ls -la /etc/nginx/sites-enabled/
```

### 3. Configuración Correcta para mozoqr.com

Crear/actualizar el archivo de configuración:

```bash
sudo nano /etc/nginx/sites-available/mozoqr.com
```

**Configuración recomendada:**

```nginx
# /etc/nginx/sites-available/mozoqr.com
server {
    listen 80;
    listen [::]:80;
    server_name mozoqr.com www.mozoqr.com;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name mozoqr.com www.mozoqr.com;
    
    # Document root
    root /var/www/vhosts/mozoqr.com/httpdocs/public;
    index index.php index.html index.htm;
    
    # SSL Configuration (ajustar paths según tu certificado)
    ssl_certificate /etc/ssl/certs/mozoqr.com.crt;
    ssl_certificate_key /etc/ssl/private/mozoqr.com.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Logs
    access_log /var/log/nginx/mozoqr.com.access.log;
    error_log /var/log/nginx/mozoqr.com.error.log;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    
    # CORS headers for API routes
    location /api/ {
        # Handle preflight requests
        if ($request_method = 'OPTIONS') {
            add_header 'Access-Control-Allow-Origin' '$http_origin' always;
            add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS' always;
            add_header 'Access-Control-Allow-Headers' 'Accept,Authorization,Content-Type,X-Requested-With' always;
            add_header 'Access-Control-Max-Age' 1728000 always;
            add_header 'Content-Type' 'text/plain; charset=utf-8' always;
            add_header 'Content-Length' 0 always;
            return 204;
        }
        
        # Add CORS headers to actual requests
        add_header 'Access-Control-Allow-Origin' '$http_origin' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'Accept,Authorization,Content-Type,X-Requested-With' always;
        
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;  # Ajustar versión PHP
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        
        # Increase timeouts for large requests
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 64k;
        fastcgi_buffers 4 64k;
        fastcgi_busy_buffers_size 128k;
    }
    
    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    # Static files caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }
    
    # Deny access to Laravel storage and config files
    location ~ /(storage|bootstrap|config|database|resources|tests)/ {
        deny all;
    }
    
    # Allow .well-known for SSL verification
    location ^~ /.well-known {
        allow all;
    }
}
```

### 4. Habilitar el Sitio

```bash
# Crear enlace simbólico para habilitar el sitio
sudo ln -sf /etc/nginx/sites-available/mozoqr.com /etc/nginx/sites-enabled/

# Eliminar configuración default si existe
sudo rm -f /etc/nginx/sites-enabled/default

# Verificar configuración
sudo nginx -t

# Si todo está bien, recargar nginx
sudo systemctl reload nginx
```

### 5. Verificar Permisos de Archivos

```bash
# Verificar permisos del directorio web
ls -la /var/www/vhosts/mozoqr.com/

# Corregir permisos si es necesario
sudo chown -R www-data:www-data /var/www/vhosts/mozoqr.com/httpdocs/
sudo chmod -R 755 /var/www/vhosts/mozoqr.com/httpdocs/
sudo chmod -R 775 /var/www/vhosts/mozoqr.com/httpdocs/storage/
sudo chmod -R 775 /var/www/vhosts/mozoqr.com/httpdocs/bootstrap/cache/
```

### 6. Verificar PHP-FPM

```bash
# Verificar que PHP-FPM esté corriendo
sudo systemctl status php8.2-fpm  # Ajustar versión según tu instalación

# Si no está corriendo
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm

# Verificar socket
ls -la /var/run/php/php8.2-fpm.sock
```

### 7. Verificar SSL (Si usas HTTPS)

```bash
# Verificar certificados SSL
sudo ls -la /etc/ssl/certs/mozoqr.com.*
sudo ls -la /etc/ssl/private/mozoqr.com.*

# Si usas Let's Encrypt
sudo certbot certificates
```

## 🚨 CONFIGURACIÓN DE EMERGENCIA (Sin SSL)

Si necesitas que funcione rápidamente sin SSL:

```nginx
# /etc/nginx/sites-available/mozoqr.com
server {
    listen 80;
    listen [::]:80;
    server_name mozoqr.com www.mozoqr.com;
    
    root /var/www/vhosts/mozoqr.com/httpdocs/public;
    index index.php index.html;
    
    access_log /var/log/nginx/mozoqr.com.access.log;
    error_log /var/log/nginx/mozoqr.com.error.log;
    
    # CORS for API
    location /api/ {
        if ($request_method = 'OPTIONS') {
            add_header 'Access-Control-Allow-Origin' '*';
            add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS';
            add_header 'Access-Control-Allow-Headers' 'Accept,Authorization,Content-Type,X-Requested-With';
            add_header 'Access-Control-Max-Age' 1728000;
            return 204;
        }
        
        add_header 'Access-Control-Allow-Origin' '*';
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

## 🧪 TESTING DESPUÉS DE LA CONFIGURACIÓN

### 1. Test Básico de Conexión

```bash
# Desde tu máquina local
curl -I http://mozoqr.com
curl -I https://mozoqr.com

# Debería retornar headers HTTP, no "connection refused"
```

### 2. Test de API

```bash
# Test Firebase config
curl http://mozoqr.com/api/firebase/config
curl https://mozoqr.com/api/firebase/config

# Test con CORS
curl -H "Origin: https://mozoqr.com" http://mozoqr.com/api/firebase/config -v
```

### 3. Test desde Navegador

```javascript
// En consola del navegador
fetch('http://mozoqr.com/api/firebase/config')
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));
```

## 🔍 DEBUGGING ADICIONAL

### Ver Logs en Tiempo Real

```bash
# Logs de Nginx
sudo tail -f /var/log/nginx/mozoqr.com.error.log
sudo tail -f /var/log/nginx/mozoqr.com.access.log

# Logs generales
sudo tail -f /var/log/nginx/error.log
sudo journalctl -f -u nginx

# Logs de PHP-FPM
sudo tail -f /var/log/php8.2-fpm.log
```

### Verificar Procesos

```bash
# Ver procesos nginx
ps aux | grep nginx

# Ver procesos PHP-FPM
ps aux | grep php-fpm

# Ver puertos en uso
sudo netstat -tulpn | grep :80
sudo netstat -tulpn | grep :443
```

### Test de DNS

```bash
# Verificar resolución DNS
nslookup mozoqr.com
dig mozoqr.com

# Ping básico
ping mozoqr.com
```

## ⚠️ PROBLEMAS COMUNES Y SOLUCIONES

### 1. "Connection refused"
```bash
# Verificar que nginx esté corriendo
sudo systemctl start nginx
```

### 2. "502 Bad Gateway"
```bash
# Problema con PHP-FPM
sudo systemctl restart php8.2-fpm
```

### 3. "403 Forbidden"
```bash
# Problema de permisos
sudo chown -R www-data:www-data /var/www/vhosts/mozoqr.com/httpdocs/
```

### 4. "404 Not Found"
```bash
# Verificar document root y index.php
ls -la /var/www/vhosts/mozoqr.com/httpdocs/public/index.php
```

### 5. "SSL Certificate Error"
```bash
# Generar certificado Let's Encrypt
sudo certbot --nginx -d mozoqr.com -d www.mozoqr.com
```

## 🚀 CHECKLIST FINAL

- [ ] Nginx está corriendo (`sudo systemctl status nginx`)
- [ ] Configuración sintácticamente correcta (`sudo nginx -t`)
- [ ] Sitio habilitado (`ls /etc/nginx/sites-enabled/mozoqr.com`)
- [ ] PHP-FPM corriendo (`sudo systemctl status php8.2-fpm`)
- [ ] Permisos correctos (`ls -la /var/www/vhosts/mozoqr.com/httpdocs/`)
- [ ] SSL configurado (si se usa HTTPS)
- [ ] DNS resolviendo (`nslookup mozoqr.com`)
- [ ] Firewall permite tráfico (`sudo ufw status`)
- [ ] APIs responden (`curl http://mozoqr.com/api/firebase/config`)

Una vez completados estos pasos, mozoqr.com debería estar accesible y las APIs funcionando correctamente.