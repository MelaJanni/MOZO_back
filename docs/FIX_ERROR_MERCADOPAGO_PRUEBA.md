# 🔧 Solución: Error "Una de las partes es de prueba"

## 🎯 Problema

MercadoPago muestra: **"Algo salió mal... Una de las partes con la que intentás hacer el pago es de prueba."**

Este error ocurre cuando hay una **inconsistencia entre las credenciales TEST y la configuración de la cuenta**.

---

## ✅ Solución Paso a Paso

### Paso 1: Verificar que las Credenciales sean de TU Cuenta

Las credenciales TEST deben pertenecer a **TU cuenta de MercadoPago**.

1. **Inicia sesión en MercadoPago Developers**:
   - Ve a: https://www.mercadopago.com.ar/developers/panel/app
   - Inicia sesión con tu cuenta de MercadoPago

2. **Ve a "Tus aplicaciones"**:
   - Haz clic en tu aplicación o crea una nueva
   - Ve a la sección **"Credenciales de prueba"**

3. **Copia las credenciales correctas**:
   ```
   Access Token TEST: TEST-XXXXXXXX-XXXXXX-XXXXXXXX
   Public Key TEST: TEST-XXXXXXXX-XXXX-XXXX
   ```

4. **Actualiza el archivo `.env`** en el servidor:
   ```bash
   ssh root@mozoqr.com
   nano /var/www/vhosts/mozoqr.com/httpdocs/.env
   ```

   Reemplaza:
   ```env
   MERCADO_PAGO_ACCESS_TOKEN=TEST-tu-nuevo-token-aqui
   MERCADO_PAGO_PUBLIC_KEY=TEST-tu-public-key-aqui
   MERCADO_PAGO_ENVIRONMENT=sandbox
   ```

5. **Limpia el caché**:
   ```bash
   cd /var/www/vhosts/mozoqr.com/httpdocs
   php artisan config:clear
   php artisan cache:clear
   ```

### Paso 2: Crear un Usuario de Prueba (Comprador)

MercadoPago requiere que uses un **usuario de prueba** para hacer compras en sandbox:

1. **Ve a Usuarios de Prueba**:
   - https://www.mercadopago.com.ar/developers/panel/test-users

2. **Crea un usuario de prueba tipo "comprador"**:
   - Tipo: **Buyer (Comprador)**
   - País: Argentina
   - Monto en cuenta: Puedes dejarlo en $1000 o lo que quieras

3. **Guarda las credenciales del usuario de prueba**:
   ```
   Email: test_user_XXXXXXXX@testuser.com
   Password: XXXXXXXXXX
   ```

### Paso 3: Realizar el Pago con el Usuario de Prueba

1. **Abre una ventana de incógnito** (importante)

2. **Ve al checkout**:
   - https://mozoqr.com/checkout/plan/1

3. **Completa el formulario y haz clic en "Contratar Plan"**

4. **En la página de MercadoPago**:
   - **NO uses tu email personal**
   - Usa los **datos del usuario de prueba** que creaste
   - O usa directamente una tarjeta de prueba

### Paso 4: Usar Tarjetas de Prueba

En la página de MercadoPago, usa estas tarjetas de prueba:

#### ✅ Tarjeta APROBADA:
```
Número: 5031 7557 3453 0604
CVV: 123
Vencimiento: 11/25
Titular: APRO
DNI: 12345678
```

#### ❌ Tarjeta RECHAZADA (para probar errores):
```
Número: 5031 4332 1540 6351
CVV: 123
Vencimiento: 11/25
Titular: OXXO
DNI: 12345678
```

---

## 🔍 Verificación Rápida

Ejecuta este comando en el servidor para verificar que las credenciales sean válidas:

```bash
ssh root@mozoqr.com
cd /var/www/vhosts/mozoqr.com/httpdocs

php artisan tinker --execute="
\$token = config('services.mercado_pago.access_token');
\$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . \$token
])->get('https://api.mercadopago.com/v1/payment_methods');

if (\$response->successful()) {
    echo '✅ Credenciales válidas' . PHP_EOL;
    echo 'Métodos de pago disponibles: ' . count(\$response->json()) . PHP_EOL;
} else {
    echo '❌ Credenciales inválidas' . PHP_EOL;
    echo 'Status: ' . \$response->status() . PHP_EOL;
    echo 'Error: ' . \$response->body() . PHP_EOL;
}
"
```

---

## 🎯 Checklist de Verificación

- [ ] Las credenciales TEST son de MI cuenta de MercadoPago
- [ ] He creado un usuario de prueba tipo "Comprador"
- [ ] Estoy usando ventana de incógnito
- [ ] Estoy usando datos del usuario de prueba O tarjetas de prueba
- [ ] He limpiado el caché después de cambiar credenciales
- [ ] El precio del plan es > $0

---

## 🔧 Alternativa: Modo de Producción

Si necesitas probar con tu cuenta real (NO recomendado para testing):

1. **Cambia a credenciales de producción**:
   ```env
   MERCADO_PAGO_ACCESS_TOKEN=APP-XXXXXXXX-XXXXXX-XXXXXXXX (sin TEST)
   MERCADO_PAGO_PUBLIC_KEY=APP-XXXXXXXX-XXXX-XXXX (sin TEST)
   MERCADO_PAGO_ENVIRONMENT=production
   ```

2. **Limpia el caché**

3. **Realiza una compra real** (se cargará a tu tarjeta)

⚠️ **NO recomendado**: Usa siempre modo sandbox para pruebas.

---

## 📚 Referencias

- **Credenciales**: https://www.mercadopago.com.ar/developers/panel/app
- **Usuarios de Prueba**: https://www.mercadopago.com.ar/developers/panel/test-users
- **Tarjetas de Prueba**: https://www.mercadopago.com.ar/developers/es/docs/checkout-pro/additional-content/test-cards
- **Documentación**: https://www.mercadopago.com.ar/developers/es/docs

---

## 🆘 Si el Error Persiste

1. **Verifica el log de MercadoPago**:
   ```bash
   tail -f /var/www/vhosts/mozoqr.com/httpdocs/storage/logs/laravel.log | grep MercadoPago
   ```

2. **Contacta a soporte de MercadoPago**:
   - https://www.mercadopago.com.ar/developers/es/support

3. **Comparte el error específico** que aparezca en los logs
