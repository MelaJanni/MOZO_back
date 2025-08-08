# 🚨 DEPLOYMENT FIX: Missing CORS Configuration

## 📋 PROBLEMA IDENTIFICADO

El error indica que el archivo `config/cors.php` no se encuentra en el servidor de producción:

```
Failed opening required '/var/www/vhosts/mozoqr.com/httpdocs/config/cors.php'
```

## 🔧 SOLUCIÓN INMEDIATA

### 1. Verificar Archivos Faltantes en Producción

Conectarse al servidor y verificar qué archivos config faltan:

```bash
# Conectar al servidor
ssh usuario@mozoqr.com

# Ir al directorio del proyecto
cd /var/www/vhosts/mozoqr.com/httpdocs

# Verificar archivos config
ls -la config/

# Verificar si cors.php existe
ls -la config/cors.php
```

### 2. Subir Archivo CORS Faltante

Si el archivo no existe, crearlo manualmente en el servidor:

```bash
# Crear el archivo cors.php en producción
cat > config/cors.php << 'EOF'
<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://mozoqr.com',
        'http://mozoqr.com',
        'https://www.mozoqr.com',
        'http://www.mozoqr.com',
        // Permitir temporalmente para testing
        '*'
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
EOF
```

### 3. Verificar Permisos

```bash
# Asegurar permisos correctos
chmod 644 config/cors.php
chown www-data:www-data config/cors.php
```

### 4. Limpiar Cache

```bash
# Limpiar cache de configuración
php artisan config:clear
php artisan config:cache
```

## 🚀 SOLUCIÓN COMPLETA DE DEPLOYMENT

### Paso 1: Preparar Cambios Locales

```bash
# En tu máquina local
git add .
git commit -m "Add Firebase real-time integration for public QR pages

- Enhanced PublicQrController with Firebase config
- Added table status endpoint for polling fallback  
- Implemented comprehensive real-time notifications
- Added complete frontend integration guide
- Created testing documentation"

git push origin main
```

### Paso 2: Deploy en Producción

```bash
# En el servidor de producción
cd /var/www/vhosts/mozoqr.com/httpdocs

# Pull cambios
git pull origin main

# Instalar/actualizar dependencias
composer install --no-dev --optimize-autoloader

# Ejecutar migraciones si las hay
php artisan migrate --force

# Limpiar y cachear configuración
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimizar composer
composer dump-autoload --optimize
```

### Paso 3: Verificar Variables de Entorno

Asegurar que estas variables estén en el `.env` de producción:

```env
# Firebase Frontend Config (REQUERIDO)
FIREBASE_API_KEY=your-production-api-key
FIREBASE_AUTH_DOMAIN=mozoqr-project.firebaseapp.com
FIREBASE_PROJECT_ID=mozoqr-project-id
FIREBASE_STORAGE_BUCKET=mozoqr-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=123456789
FIREBASE_APP_ID=1:123456789:web:abc123def456

# Firebase Backend Config (REQUERIDO)
FIREBASE_SERVER_KEY=your-server-key
FIREBASE_SERVICE_ACCOUNT_PATH=/var/www/vhosts/mozoqr.com/httpdocs/storage/app/firebase/service-account.json

# CORS Origins (ACTUALIZAR)
APP_URL=https://mozoqr.com
```

### Paso 4: Verificar Service Account Firebase

```bash
# Verificar que el service account existe
ls -la storage/app/firebase/

# Si no existe, crear directorio y subir archivo
mkdir -p storage/app/firebase/
# Copiar service-account.json desde Firebase Console
```

## 🧪 VERIFICACIÓN POST-DEPLOYMENT

### Test 1: Verificar CORS

```bash
curl -H "Origin: https://mozoqr.com" \
     -H "Access-Control-Request-Method: GET" \
     -H "Access-Control-Request-Headers: X-Requested-With" \
     -X OPTIONS \
     https://mozoqr.com/api/firebase/config
```

### Test 2: Probar Endpoints

```bash
# Test Firebase config
curl https://mozoqr.com/api/firebase/config | jq '.'

# Test table config (usar ID real)
curl https://mozoqr.com/api/firebase/table/5/config | jq '.'

# Test QR endpoint (usar datos reales)
curl https://mozoqr.com/api/qr/restaurant-slug/table-code | jq '.'
```

### Test 3: Verificar Logs

```bash
# Monitorear logs en tiempo real
tail -f storage/logs/laravel.log

# Buscar errores específicos
grep -i "error\|exception\|failed" storage/logs/laravel.log | tail -20
```

## ⚠️ TROUBLESHOOTING COMÚN

### Error: "Class not found"

```bash
# Regenerar autoloader
composer dump-autoload --optimize
php artisan clear-compiled
```

### Error: "Permission denied"

```bash
# Corregir permisos
sudo chown -R www-data:www-data /var/www/vhosts/mozoqr.com/httpdocs
sudo chmod -R 755 /var/www/vhosts/mozoqr.com/httpdocs
sudo chmod -R 775 /var/www/vhosts/mozoqr.com/httpdocs/storage
sudo chmod -R 775 /var/www/vhosts/mozoqr.com/httpdocs/bootstrap/cache
```

### Error: Firebase "Permission denied"

```bash
# Verificar service account
php artisan tinker
>>> config('services.firebase.service_account_path');
>>> file_exists(config('services.firebase.service_account_path'));
```

## 🔄 ROLLBACK PLAN

Si hay problemas, rollback rápido:

```bash
# Volver al commit anterior
git log --oneline -5
git checkout [previous-commit-hash]

# Limpiar cache
php artisan config:clear
php artisan route:clear
```

## ✅ CHECKLIST FINAL

- [ ] Archivo `config/cors.php` existe en producción
- [ ] Variables Firebase configuradas en `.env`
- [ ] Service account JSON subido
- [ ] Permisos correctos aplicados
- [ ] Cache limpiado y regenerado
- [ ] Endpoints responden correctamente
- [ ] CORS funciona desde frontend
- [ ] Logs sin errores críticos
- [ ] Git actualizado con últimos cambios

## 📞 NEXT STEPS DESPUÉS DEL FIX

1. **Probar integración completa** usando `INTEGRATION-TEST.md`
2. **Implementar frontend** usando `MOZOQR-REALTIME-IMPLEMENTATION.md`  
3. **Monitorear logs** las primeras horas
4. **Ajustar CORS origins** si es necesario
5. **Documentar** cualquier configuración específica del servidor

Este fix debería resolver el problema inmediato del CORS y preparar el sistema para la integración Firebase completa.