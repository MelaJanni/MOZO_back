# 🎯 Flujo Visual del Checkout con MercadoPago

## Estado Actual del Error

Según la imagen que compartiste, estás viendo:
```
❌ Por favor, corrige los siguientes errores:
   • Error procesando el pago. Por favor intenta nuevamente o contacta a soporte.
```

Esto significa que el backend **NO pudo crear la preferencia de pago** en MercadoPago.

---

## 📊 Flujo Completo Esperado

```
┌─────────────────────────────────────────────────────────────┐
│ PASO 1: Formulario de Checkout (DONDE ESTÁS AHORA)         │
│ URL: https://mozoqr.com/checkout/plan/1                    │
├─────────────────────────────────────────────────────────────┤
│ ✓ Plan seleccionado: Mensual                               │
│ ✓ Método de pago: MercadoPago                              │
│ ✓ Términos aceptados                                       │
│                                                              │
│ [Botón: Contratar Plan] ← HACES CLIC AQUÍ                  │
└─────────────────────────────────────────────────────────────┘
                          │
                          ↓ POST /checkout/subscribe
                          │
┌─────────────────────────────────────────────────────────────┐
│ BACKEND: Procesa el Pago                                    │
├─────────────────────────────────────────────────────────────┤
│ 1. Valida datos del formulario                             │
│ 2. Crea suscripción en BD                                  │
│ 3. Llama a MercadoPago API:                                │
│    POST https://api.mercadopago.com/checkout/preferences   │
│ 4. Recibe URL de checkout de MercadoPago                   │
│ 5. Redirige al usuario                                     │
└─────────────────────────────────────────────────────────────┘
                          │
                          ↓ Redirect 302
                          │
┌─────────────────────────────────────────────────────────────┐
│ PASO 2: Checkout de MercadoPago (DEBERÍAS VER ESTO)       │
│ URL: https://www.mercadopago.com.ar/checkout/v1/...        │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ╔══════════════════════════════════════╗                  │
│  ║  MercadoPago                          ║                  │
│  ╠══════════════════════════════════════╣                  │
│  ║  Suscripción Plan Mensual            ║                  │
│  ║  Monto: $15.000 ARS                  ║                  │
│  ║                                       ║                  │
│  ║  Número de tarjeta:                  ║                  │
│  ║  [____  ____  ____  ____]            ║                  │
│  ║                                       ║                  │
│  ║  Vencimiento: [__/__]  CVV: [___]    ║                  │
│  ║                                       ║                  │
│  ║  Nombre: [___________________]       ║                  │
│  ║                                       ║                  │
│  ║  [Pagar Ahora]                       ║                  │
│  ╚══════════════════════════════════════╝                  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
                          │
                          ↓ Procesa pago
                          │
                    ┌─────┴─────┐
                    │           │
          ┌─────────▼──┐   ┌───▼──────┐
          │  APROBADO  │   │ RECHAZADO │
          └─────────┬──┘   └───┬──────┘
                    │          │
                    ↓          ↓
┌─────────────────────────────────────────────────────────────┐
│ PASO 3: Resultado                                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ ✅ ÉXITO: /checkout/success                                 │
│    "¡Pago Exitoso! Tu cuenta está activa"                  │
│                                                              │
│ ❌ CANCELADO: /checkout/cancel                              │
│    "Pago Cancelado - No se realizó ningún cargo"           │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔴 Problema Actual

El flujo se está **deteniendo** aquí:

```
[Contratar Plan] → ❌ ERROR
                   No puede crear preferencia en MercadoPago
                   Muestra mensaje de error en la misma página
```

**NO estás llegando** a la página de MercadoPago porque el sistema no puede conectarse con su API.

---

## 🔧 Posibles Causas del Error

### 1. **Access Token Inválido o Expirado** ⚠️
```env
MERCADO_PAGO_ACCESS_TOKEN=TEST-4596292006853439-091822-...
```

**Solución**: Verificar que el token sea válido en el [Dashboard de MercadoPago](https://www.mercadopago.com.ar/developers/panel/app)

### 2. **API de MercadoPago No Responde** 🌐
El servidor no puede alcanzar `api.mercadopago.com`

**Solución**: Verificar firewall, conexión a internet del servidor

### 3. **Caché de Configuración** 💾
La configuración antigua está en caché

**Solución**:
```bash
php artisan config:clear
php artisan cache:clear
```

### 4. **Error en el Código** 💻
Algún problema con el código que acabamos de modificar

---

## 🧪 Cómo Diagnosticar el Problema

### Opción 1: Ver Logs en Tiempo Real

Abre una terminal y ejecuta:
```bash
# En PowerShell (Windows)
Get-Content "storage\logs\laravel.log" -Wait -Tail 20
```

Luego intenta hacer el checkout de nuevo y observa qué error aparece.

### Opción 2: Verificar la Configuración

```bash
php artisan tinker
```

Luego ejecuta:
```php
$service = app('App\Services\MercadoPagoService');
dd([
    'access_token' => config('services.mercado_pago.access_token') ? 'Configured' : 'Missing',
    'public_key' => config('services.mercado_pago.public_key') ? 'Configured' : 'Missing',
    'environment' => config('services.mercado_pago.environment'),
]);
```

### Opción 3: Probar la Conexión con MercadoPago

```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Http;

$token = config('services.mercado_pago.access_token');

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
])->get('https://api.mercadopago.com/v1/payment_methods');

if ($response->successful()) {
    echo "✅ Conexión exitosa con MercadoPago\n";
} else {
    echo "❌ Error: " . $response->status() . " - " . $response->body() . "\n";
}
```

---

## ✅ Una Vez Que Funcione

Cuando todo esté correcto, verás esto:

1. **Haces clic en "Contratar Plan"** 
   - La página se recarga con un spinner/loading
   
2. **Redirección automática**
   - Sales de `mozoqr.com`
   - Entras a `mercadopago.com.ar`
   
3. **Página de pago de MercadoPago**
   - Formulario azul de MercadoPago
   - Campos para tarjeta de crédito
   - Logo de MercadoPago visible
   
4. **Completas el pago**
   - Usas tarjeta de prueba: `5031 7557 3453 0604`
   - CVV: `123`, Vencimiento: `11/25`, Nombre: `APRO`
   
5. **Vuelves a MOZO QR**
   - Página de éxito con check verde ✅
   - Mensaje de bienvenida

---

## 📞 Próximos Pasos

1. **Revisa los logs** para ver el error exacto
2. **Verifica las credenciales** de MercadoPago
3. **Prueba la conexión** con la API de MercadoPago
4. Si nada funciona, comparte el error específico de los logs
