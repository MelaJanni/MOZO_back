# 🔧 KERNEL & CORS FIXES - Problemas de Configuración Resueltos

## 📋 PROBLEMAS IDENTIFICADOS Y SOLUCIONADOS

### 1. **Problema Principal**: Middleware Conflictivo
- **Síntoma**: Error al cargar `cors.php` o problemas CORS en producción
- **Causa**: El middleware `EnsureFrontendRequestsAreStateful` de Sanctum interfería con APIs públicas
- **Solución**: Creado grupo `public_api` separado sin Sanctum

### 2. **Problemas Secundarios**: CORS mal configurado
- **Síntoma**: Preflight requests fallando
- **Causa**: Configuración CORS genérica no optimizada para APIs públicas
- **Solución**: Middleware CORS personalizado

## 🚀 CAMBIOS REALIZADOS

### 1. Kernel HTTP Actualizado (`app/Http/Kernel.php`)

```php
// NUEVO: Grupo middleware para APIs públicas
'public_api' => [
    \App\Http\Middleware\PublicApiCors::class,         // CORS personalizado
    \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

**Beneficios**:
- ✅ Sin interferencia de Sanctum
- ✅ Sin DevAuthMiddleware en producción
- ✅ CORS optimizado para APIs públicas
- ✅ Rate limiting mantenido

### 2. CORS Mejorado (`config/cors.php`)

```php
// Configuración específica por entorno
'allowed_origins' => env('APP_ENV') === 'production' ? [
    'https://mozoqr.com',
    'https://www.mozoqr.com',
    'http://mozoqr.com',
    'http://www.mozoqr.com',
] : [
    'http://localhost:5173',
    // ... desarrollo
    '*'
],

// Optimizado para APIs públicas
'supports_credentials' => false,
```

### 3. Middleware CORS Personalizado (`app/Http/Middleware/PublicApiCors.php`)

- **Manejo inteligente de orígenes** por entorno
- **Preflight requests** optimizados
- **Headers específicos** para APIs públicas
- **Fallback seguro** si origin no permitido

### 4. Rutas Reorganizadas (`routes/api.php`)

```php
// ANTES: Rutas públicas sin middleware específico
Route::get('/firebase/config', [...]);
Route::post('/tables/{table}/call-waiter', [...]);

// DESPUÉS: Rutas agrupadas con middleware optimizado
Route::middleware('public_api')->group(function () {
    Route::get('/firebase/config', [FirebaseConfigController::class, 'getConfig']);
    Route::get('/firebase/table/{table}/config', [...]);
    Route::post('/tables/{table}/call-waiter', [...]);
    Route::get('/qr/{restaurantSlug}/{tableCode}', [...]);
    Route::get('/table/{tableId}/status', [...]);
});
```

## 🧪 TESTING DE LOS FIXES

### Test 1: Verificar Middleware Groups

```bash
# Localmente (debería funcionar)
php artisan route:list --path=api/firebase

# En producción
curl -X OPTIONS "https://mozoqr.com/api/firebase/config" \
  -H "Origin: https://mozoqr.com" \
  -H "Access-Control-Request-Method: GET" \
  -v
```

### Test 2: Probar CORS Headers

```bash
# Test desde navegador
fetch('https://mozoqr.com/api/firebase/config', {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

### Test 3: Verificar Routes Públicas

```bash
# Test llamada de mozo (usando table_id real)
curl -X POST "https://mozoqr.com/api/tables/5/call-waiter" \
  -H "Content-Type: application/json" \
  -H "Origin: https://mozoqr.com" \
  -d '{"message": "Test desde fix de CORS"}' | jq '.'
```

## 🔧 CONFIGURACIÓN REQUERIDA EN PRODUCCIÓN

### Variables .env necesarias:

```env
# Esencial para CORS
APP_ENV=production
APP_URL=https://mozoqr.com

# Firebase (completar con valores reales)
FIREBASE_API_KEY=your-production-api-key
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_STORAGE_BUCKET=your-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=123456789
FIREBASE_APP_ID=1:123456789:web:abcdef123456
FIREBASE_SERVER_KEY=your-server-key
FIREBASE_SERVICE_ACCOUNT_PATH=/var/www/vhosts/mozoqr.com/httpdocs/storage/app/firebase/service-account.json
```

## 🚨 DEPLOYMENT COMMANDS

```bash
# En el servidor de producción
cd /var/www/vhosts/mozoqr.com/httpdocs

# Pull cambios
git pull origin main

# Limpiar cache (IMPORTANTE después de cambios en Kernel)
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Regenerar cache optimizado
php artisan config:cache
php artisan route:cache

# Verificar permisos
chmod 644 app/Http/Kernel.php
chmod 644 config/cors.php
chmod 644 app/Http/Middleware/PublicApiCors.php
```

## ⚠️ TROUBLESHOOTING

### Error: "Class PublicApiCors not found"

```bash
# Regenerar autoloader
composer dump-autoload --optimize
```

### Error: "Route not defined"

```bash
# Limpiar cache de rutas
php artisan route:clear
php artisan route:cache
```

### Error: CORS sigue fallando

```bash
# Verificar que HandleCors esté primero en middleware global
grep -n "HandleCors" app/Http/Kernel.php

# Verificar configuración CORS
php artisan config:show cors
```

### Error: "Method not allowed"

```bash
# Verificar que las rutas estén registradas
php artisan route:list --path=api/firebase
php artisan route:list --path=api/qr
```

## 📊 IMPACTO DE LOS CAMBIOS

### ✅ Beneficios:
- **APIs públicas** funcionan sin conflictos de Sanctum
- **CORS** optimizado por entorno
- **Performance** mejorada (menos middleware innecesario)
- **Seguridad** mantenida con rate limiting
- **Compatibilidad** total con sistema existente

### ⚠️ Consideraciones:
- **Nuevo middleware** requiere deployment coordinado
- **Cache clearing** necesario en producción
- **Testing** requerido post-deployment

## 🎯 VALIDACIÓN FINAL

Estas APIs deberían funcionar perfectamente después de los fixes:

1. ✅ `GET /api/firebase/config` - Configuración Firebase
2. ✅ `GET /api/firebase/table/{id}/config` - Config específica de mesa
3. ✅ `GET /api/qr/{restaurant}/{table}` - Info completa QR
4. ✅ `POST /api/tables/{id}/call-waiter` - Llamar mozo
5. ✅ `GET /api/table/{id}/status` - Estado mesa (polling)

Con estos cambios, el error de CORS debería estar completamente resuelto y las APIs públicas funcionarán correctamente desde mozoqr.com.

## 🚀 NEXT STEPS

1. **Deploy estos cambios** en producción
2. **Ejecutar tests** de verificación
3. **Implementar frontend** usando la documentación ya creada
4. **Monitorear logs** para verificar que no hay errores
5. **Ajustar configuration** si es necesario basado en logs