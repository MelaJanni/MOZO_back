# 🚀 Pasos para Desplegar los Cambios

## Problema Identificado

El formulario SE ESTÁ ENVIANDO correctamente, pero el servidor está fallando al crear la preferencia de MercadoPago y devolviendo el error genérico.

## Cambios Realizados

He agregado **logging detallado** con emojis para rastrear exactamente dónde falla:

- 🔵 = Información de progreso
- ❌ = Error controlado  
- ✅ = Éxito
- 💥 = Excepción no controlada

## Desplegar en Producción

### Opción 1: Desde tu máquina local

```bash
# 1. Commit de los cambios
git add .
git commit -m "feat: Add detailed logging to MercadoPago payment flow"

# 2. Push al repositorio
git push origin main

# 3. Conectarte al servidor y actualizar
ssh root@mozoqr.com
cd /var/www/html  # o donde esté tu proyecto
git pull origin main
php artisan config:clear
php artisan cache:clear
```

### Opción 2: Si estás en el servidor

```bash
# En el servidor de producción
cd /var/www/html  # o tu directorio
git pull origin main
php artisan config:clear
php artisan cache:clear
```

### Opción 3: Copiar archivo manualmente

Si no puedes hacer git push, copia el archivo modificado:

```bash
# En tu máquina local
scp app/Http/Controllers/PublicCheckoutController.php root@mozoqr.com:/var/www/html/app/Http/Controllers/

# Luego en el servidor
ssh root@mozoqr.com
cd /var/www/html
php artisan config:clear
php artisan cache:clear
```

## Monitorear los Logs en Producción

Una vez desplegado, monitorea los logs en tiempo real:

```bash
# Conectarte al servidor
ssh root@mozoqr.com

# Limpiar logs antiguos (opcional)
> /var/www/html/storage/logs/laravel.log

# Monitorear en tiempo real
tail -f /var/www/html/storage/logs/laravel.log | grep -E "🔵|❌|✅|💥"
```

## Probar de Nuevo

1. Ve a https://mozoqr.com/checkout/plan/1
2. Haz clic en "Contratar Plan"
3. Observa los logs en la terminal SSH

Deberías ver algo como:

```
🔵 Subscribe method called
🔵 Price calculation debug
🔵 Base price calculated
🔵 Final price calculated
🔵 START: Processing MercadoPago payment
🔵 RESULT: MercadoPago payment processed
```

Y luego:
- ✅ SUCCESS: Redirecting to MercadoPago → **ÉXITO!**
- ❌ FAIL: MercadoPago payment failed → Ver el mensaje de error
- 💥 EXCEPTION → Ver la excepción completa

## Errores Comunes y Soluciones

### Error: "MercadoPagoService not available"
```bash
# Verificar que el servicio esté registrado
php artisan tinker
app('App\Services\MercadoPagoService');
```

### Error: "Error creating MercadoPago preference: ..."
- Verificar credenciales en `.env`
- Ejecutar: `php artisan config:clear`
- Verificar conectividad con API de MercadoPago

### Error: "Call to a member function ... on null"
- El servicio MercadoPagoService no está inyectándose
- Verificar el constructor del controlador

## Ubicación de Logs en Servidor

```bash
# Laravel logs
/var/www/html/storage/logs/laravel.log

# Nginx logs (si aplica)
/var/log/nginx/error.log
/var/log/nginx/access.log

# PHP-FPM logs (si aplica)
/var/log/php-fpm/error.log
```

## Siguiente Paso

Una vez desplegado, **comparte la salida de los logs** para que pueda ver exactamente dónde está fallando y solucionarlo definitivamente.
