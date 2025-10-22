# Guía de Prueba de la Pasarela de Pagos MercadoPago

## Configuración Actual
- **Entorno**: Sandbox (Testing)
- **Access Token**: Configurado ✅
- **Public Key**: Configurado ✅
- **Webhook URL**: https://mozoqr.com/webhooks/mercadopago

## Cómo Probar el Flujo de Pago

### 1. Acceder a la Página de Planes
```
URL: https://mozoqr.com/checkout
```

### 2. Seleccionar un Plan
```
URL: https://mozoqr.com/checkout/plan/{id}
Ejemplo: https://mozoqr.com/checkout/plan/1
```

### 3. Completar el Formulario
- Seleccionar periodo de facturación (mensual, trimestral, anual)
- Ingresar código de cupón (opcional)
- Seleccionar método de pago: **MercadoPago**
- Aceptar términos y condiciones

### 4. Procesar el Pago
Al hacer clic en "Contratar Plan", se debe:
1. Crear la suscripción en la base de datos
2. Generar una preferencia de pago en MercadoPago
3. Redirigir al checkout de MercadoPago

### 5. Completar el Pago en MercadoPago
En el entorno de sandbox, puedes usar tarjetas de prueba:

**Tarjetas de Prueba APROBADAS:**
```
Número: 5031 7557 3453 0604
CVV: 123
Fecha: 11/25
Nombre: APRO
```

**Tarjetas de Prueba RECHAZADAS:**
```
Número: 5031 4332 1540 6351
CVV: 123
Fecha: 11/25
Nombre: OXXO
```

### 6. Verificar el Resultado
Después del pago:
- **Éxito**: Redirige a `/checkout/success`
- **Cancelado**: Redirige a `/checkout/cancel`

## Monitoreo de Logs

### Ver Logs en Tiempo Real
```bash
# En PowerShell
Get-Content "storage\logs\laravel.log" -Wait -Tail 50
```

### Buscar Errores Específicos de MercadoPago
```bash
Get-Content "storage\logs\laravel.log" | Select-String -Pattern "MercadoPago|checkout" -Context 2,2
```

## Webhooks

### Probar Webhooks Localmente
Para probar webhooks en desarrollo local, necesitas exponer tu servidor local:

**Opción 1: ngrok**
```bash
ngrok http 80
```

**Opción 2: Configurar en producción**
- URL del webhook ya está configurada: `https://mozoqr.com/webhooks/mercadopago`
- MercadoPago enviará notificaciones POST a esta URL

### Estructura del Webhook
```json
{
  "action": "payment.created",
  "api_version": "v1",
  "data": {
    "id": "1234567890"
  },
  "date_created": "2025-10-22T10:00:00Z",
  "id": 123456789,
  "type": "payment",
  "user_id": "230980817"
}
```

## Verificación de Estado

### Verificar Configuración
```bash
php artisan tinker --execute="
echo 'Access Token: ' . (config('services.mercado_pago.access_token') ? 'Configured' : 'NOT Configured') . PHP_EOL;
echo 'Public Key: ' . (config('services.mercado_pago.public_key') ? 'Configured' : 'NOT Configured') . PHP_EOL;
echo 'Environment: ' . config('services.mercado_pago.environment') . PHP_EOL;
"
```

### Verificar Servicios
```bash
# Verificar MercadoPagoService
php artisan tinker --execute="
try {
    \$service = app('App\Services\MercadoPagoService');
    echo 'MercadoPagoService: OK' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . PHP_EOL;
}
"

# Verificar MercadoPagoProvider
php artisan tinker --execute="
try {
    \$provider = app('App\Services\PaymentProviders\MercadoPagoProvider');
    echo 'MercadoPagoProvider: OK' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . PHP_EOL;
}
"
```

## Solución de Problemas

### Error: "Error procesando el pago"
**Causa**: Access token no configurado o inválido
**Solución**:
1. Verificar que `.env` tenga `MERCADO_PAGO_ACCESS_TOKEN`
2. Ejecutar `php artisan config:clear`
3. Verificar logs en `storage/logs/laravel.log`

### Error: "Payment provider not configured"
**Causa**: El servicio no pudo inicializarse
**Solución**:
1. Revisar `config/services.php` - sección `mercado_pago`
2. Verificar que las credenciales sean válidas
3. Consultar [MercadoPago Developers](https://www.mercadopago.com.ar/developers)

### Webhooks no llegan
**Causa**: URL no accesible o firewall bloqueando
**Solución**:
1. Verificar que `https://mozoqr.com/webhooks/mercadopago` sea accesible públicamente
2. Revisar configuración de webhooks en el dashboard de MercadoPago
3. Verificar logs del servidor web (nginx/apache)

## Comandos Útiles

```bash
# Limpiar cachés
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Ver rutas de checkout
php artisan route:list --name=checkout

# Ver rutas de webhooks
php artisan route:list --name=webhook

# Ver información de la aplicación
php artisan about
```

## Recursos Adicionales

- **Documentación MercadoPago**: https://www.mercadopago.com.ar/developers/es/docs
- **API Reference**: https://www.mercadopago.com.ar/developers/es/reference
- **Tarjetas de Prueba**: https://www.mercadopago.com.ar/developers/es/docs/integration/testing/test-cards
- **Webhook Testing**: https://www.mercadopago.com.ar/developers/es/docs/notifications/webhooks

## Notas Importantes

⚠️ **Entorno de Sandbox**
- Las transacciones no son reales
- Usa solo tarjetas de prueba
- Los webhooks pueden tener delay

⚠️ **Producción**
- Cambiar `MERCADO_PAGO_ENVIRONMENT=production`
- Usar credenciales de producción
- Configurar `MERCADO_PAGO_WEBHOOK_SECRET` para seguridad
- Probar exhaustivamente antes de publicar

✅ **Buenas Prácticas**
- Monitorear logs regularmente
- Implementar retry logic para webhooks
- Guardar todos los eventos de pago
- Enviar notificaciones al usuario por email
