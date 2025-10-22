# 🔍 Instrucciones de Diagnóstico - Paso a Paso

## ❓ ¿Por qué no ves la siguiente pantalla?

El error que ves indica que el formulario se está enviando, pero algo está fallando en el servidor. Sin embargo, **no vemos el error en los logs**, lo que significa que:

1. El formulario no se está enviando correctamente (JavaScript lo está bloqueando)
2. O el error no se está registrando en los logs

---

## 📝 Instrucciones para Diagnóstico

### Paso 1: Abrir Consola del Navegador

1. Estando en la página: `https://mozoqr.com/checkout/plan/1`
2. Presiona **F12** (o clic derecho → "Inspeccionar")
3. Ve a la pestaña **"Console"** (Consola)
4. **Deja la consola abierta**

### Paso 2: Intentar el Checkout

1. Completa el formulario:
   - ✅ Selecciona "Mensual"
   - ✅ Selecciona "MercadoPago"
   - ✅ Marca "Acepto los términos"
2. **Haz clic en "Contratar Plan"**

### Paso 3: Observar la Consola

Deberías ver mensajes como estos:
```
Checkout form loaded: <form>
Submit button: <button>
Form submit event triggered!
Form action: https://mozoqr.com/checkout/subscribe
Form method: post
Terms checkbox: true
Billing period: monthly
Form validation passed, submitting...
```

### Paso 4: Verificar la Pestaña "Network" (Red)

1. Ve a la pestaña **"Network"** (Red)
2. Filtra por **"Fetch/XHR"** o **"All"**
3. Haz clic en "Contratar Plan" de nuevo
4. Busca una petición a `/checkout/subscribe`
5. Haz clic en ella y ve a:
   - **Headers** → Status Code (debería ser 302 o 200)
   - **Response** → Ver la respuesta del servidor
   - **Preview** → Ver errores si los hay

---

## 🎯 ¿Qué Esperar?

### ✅ Si Todo Funciona Correctamente:

1. **En la consola** verás:
   ```
   Form validation passed, submitting...
   ```

2. **En la pestaña Network** verás:
   - Petición POST a `/checkout/subscribe`
   - Status: `302` (redirect)
   - Location: URL de MercadoPago

3. **En el navegador**:
   - Te redirige automáticamente a `https://www.mercadopago.com.ar/checkout/...`

### ❌ Si Hay un Error:

**Escenario A: Error de JavaScript**
```
Terms checkbox not checked!
// O
No billing period selected!
```
→ **Solución**: El formulario tiene un problema de validación

**Escenario B: Error 500 en Network**
```
Status: 500 Internal Server Error
Response: {error: "..."}
```
→ **Solución**: Hay un error en el servidor (comparte el mensaje de error)

**Escenario C: Error 422 Validation**
```
Status: 422 Unprocessable Entity
Response: {errors: {...}}
```
→ **Solución**: Faltan campos o hay datos inválidos

**Escenario D: No pasa nada**
→ **Solución**: JavaScript está bloqueando el envío

---

## 🔧 Soluciones Rápidas

### Solución 1: Deshabilitar Validación JavaScript Temporalmente

Abre la consola y ejecuta:
```javascript
document.getElementById('checkout-form').submit();
```

Esto enviará el formulario sin pasar por la validación de JavaScript.

### Solución 2: Verificar CSRF Token

En la consola, ejecuta:
```javascript
document.querySelector('meta[name="csrf-token"]')?.content
```

Deberías ver un token largo. Si aparece `undefined`, ese es el problema.

### Solución 3: Ver los Datos del Formulario

En la consola, ejecuta:
```javascript
const form = document.getElementById('checkout-form');
const formData = new FormData(form);
for (let [key, value] of formData.entries()) {
    console.log(key, value);
}
```

Esto te mostrará todos los datos que se están enviando.

---

## 📸 ¿Qué Necesito que Compartas?

Por favor, comparte **capturas de pantalla** de:

1. **La consola del navegador** después de hacer clic en "Contratar Plan"
2. **La pestaña Network** mostrando la petición a `/checkout/subscribe` (si existe)
3. **La respuesta del servidor** (Response tab en Network)

O mejor aún, **copia y pega** en un mensaje:

```
# Consola (Console):
[pega aquí todo el texto de la consola]

# Network - Request Headers:
[pega los headers de la petición]

# Network - Response:
[pega la respuesta del servidor]
```

---

## 🚀 Próximos Pasos

Una vez que tenga esta información, podré:
1. Identificar el error exacto
2. Corregir el problema específico
3. Hacer que veas la pantalla de MercadoPago

---

## ⚡ Atajo Rápido (Modo Desarrollador)

Si tienes acceso al servidor de producción, ejecuta:

```bash
# Limpiar logs antiguos para ver solo errores nuevos
echo "" > storage/logs/laravel.log

# Monitorear en tiempo real
tail -f storage/logs/laravel.log

# Luego intenta el checkout de nuevo
```

Cualquier error aparecerá inmediatamente en la terminal.
