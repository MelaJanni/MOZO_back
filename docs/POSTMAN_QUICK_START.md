# 🚀 POSTMAN QUICK START - MOZO API

## 📥 Importar Colección

1. Abre Postman
2. Click en **Import**
3. Selecciona el archivo: `docs/MOZO_API_Postman_Collection.json`
4. ✅ Listo - Verás 50+ endpoints organizados por carpetas

---

## ⚙️ Configurar Variables

### Paso 1: Crear Environment

1. En Postman, click en **Environments** (⚙️ arriba a la derecha)
2. Click **+** para crear nuevo environment
3. Nombre: `MOZO Local`
4. Agregar variables:

| Variable | Initial Value | Current Value |
|----------|---------------|---------------|
| `base_url` | `http://localhost/api` | `http://localhost/api` |
| `auth_token` | (vacío) | (se llenará automático) |
| `business_id` | `1` | `1` |
| `table_id` | `1` | `1` |
| `waiter_id` | `123` | `123` |
| `call_id` | (vacío) | (vacío) |
| `staff_id` | (vacío) | (vacío) |

5. Click **Save**
6. Selecciona "MOZO Local" en el dropdown de environments

---

## 🔑 Primer Request: Login

### Paso 1: Login con Google (Manual)

Si tienes credenciales de Google:

```
POST {{base_url}}/auth/google/login
```

Body:
```json
{
    "credential": "TU_GOOGLE_JWT_TOKEN",
    "role": "waiter"
}
```

### Paso 2: Login con Email (Alternativa)

Si Sanctum está configurado para email/password:

```
POST {{base_url}}/auth/login
```

Body:
```json
{
    "email": "mozo@example.com",
    "password": "password123"
}
```

### Paso 3: Copiar Token

Respuesta exitosa:
```json
{
    "success": true,
    "token": "1|abcdef123456...",
    "user": { ... }
}
```

**COPIAR el token** y pegarlo en:
- Environment → `auth_token` variable

---

## 🧪 Probar APIs

### 1. Registrar Token FCM (Mozo)

```
POST {{base_url}}/mozo/fcm/register-token
Authorization: Bearer {{auth_token}}
```

Body:
```json
{
    "token": "fT8xK9mNQZG...FAKE_FCM_TOKEN",
    "platform": "android",
    "device_info": {
        "model": "Samsung Galaxy",
        "os_version": "Android 13"
    }
}
```

**Respuesta esperada**: 200 OK

---

### 2. Mesa Llama al Mozo (QR)

**IMPORTANTE**: Primero necesitas:
- Una mesa con `active_waiter_id` asignado
- Mesa con `notifications_enabled = true`

```
POST {{base_url}}/qr/table/{{table_id}}/call
```

Body:
```json
{
    "message": "Necesito la cuenta por favor",
    "urgency": "normal"
}
```

**Respuesta esperada**: 200 OK con datos de la llamada

---

### 3. Ver Llamadas Pendientes (Mozo)

```
GET {{base_url}}/waiter/calls/pending
Authorization: Bearer {{auth_token}}
```

**Respuesta esperada**: Array de llamadas pendientes

---

### 4. Responder Llamada (Acknowledge)

```
PUT {{base_url}}/waiter/calls/{{call_id}}/acknowledge
Authorization: Bearer {{auth_token}}
```

**Respuesta esperada**: 200 OK, status cambia a "acknowledged"

---

### 5. Completar Llamada

```
PUT {{base_url}}/waiter/calls/{{call_id}}/complete
Authorization: Bearer {{auth_token}}
```

**Respuesta esperada**: 200 OK, llamada se elimina de Firebase

---

## 📁 Estructura de la Colección

```
MOZO Backend API
├── 🔐 Autenticación
│   ├── Login Google (Mozo)
│   └── Logout
│
├── 📱 FCM Tokens (Sistema V2)
│   ├── Registrar Token FCM
│   ├── Refrescar Token FCM
│   ├── Estado de Tokens del Usuario
│   ├── Eliminar Token FCM
│   └── Test Notificación FCM
│
├── 🔔 Llamadas de Mesa (WaiterCall V2)
│   ├── Mesa Llama al Mozo (desde QR)
│   ├── Mozo Responde Llamada (Acknowledge)
│   ├── Mozo Completa Llamada
│   ├── Llamadas Pendientes del Mozo
│   └── Historial de Llamadas del Mozo
│
├── 👥 Gestión de Staff
│   ├── Mozo Solicita Unirse a Negocio
│   ├── Admin Aprueba Solicitud de Staff
│   ├── Admin Rechaza Solicitud de Staff
│   ├── Mis Solicitudes de Staff (Mozo)
│   ├── Lista de Staff del Negocio (Admin)
│   └── Admin Elimina Staff
│
├── 🏢 Admin - Gestión de Negocio
│   ├── Obtener Datos del Negocio
│   ├── Crear Nuevo Negocio
│   ├── Actualizar Configuración del Negocio
│   ├── Regenerar Código de Invitación
│   ├── Eliminar Negocio
│   └── Estadísticas del Dashboard
│
├── 🍽️ Mesas (Tables)
│   ├── Lista de Mesas del Negocio
│   ├── Crear Mesa
│   ├── Actualizar Mesa
│   ├── Eliminar Mesa
│   └── Silenciar Mesa Temporalmente
│
├── 📲 QR Codes
│   ├── Lista de QR Codes
│   ├── Generar QR Code para Mesa
│   └── Descargar QR Code
│
└── 👤 Perfil de Usuario
    ├── Obtener Perfil Activo
    └── Actualizar Perfil de Mozo
```

---

## 🔍 Troubleshooting

### Error 401 Unauthorized

**Causa**: Token inválido o expirado

**Solución**:
1. Hacer login nuevamente
2. Copiar nuevo token
3. Actualizar variable `auth_token`

---

### Error 403 Forbidden

**Causa**: Usuario no tiene permisos (ej: no es mozo)

**Solución**:
- Verificar que el usuario tiene el rol correcto
- Para FCM tokens, **solo mozos** pueden registrar

---

### Error 404 Not Found

**Causa posible**:
1. ID inválido (table_id, call_id, etc.)
2. Ruta incorrecta
3. Recurso no existe en DB

**Solución**:
1. Verificar que el recurso existe: `GET {{base_url}}/admin/tables`
2. Usar ID válido en variables de environment

---

### Error 422 Validation Error

**Causa**: Datos faltantes o inválidos en el body

**Ejemplo**:
```json
{
    "message": "The token field is required.",
    "errors": {
        "token": ["The token field is required."]
    }
}
```

**Solución**:
- Revisar body del request
- Asegurar que todos los campos requeridos están presentes
- Verificar tipos de datos (string, int, boolean)

---

### Error 500 Internal Server Error

**Causa**: Bug en el backend o DB connection issue

**Solución**:
1. Ver logs del backend: `storage/logs/laravel.log`
2. Verificar que la DB está corriendo
3. Verificar `.env` configurado correctamente

---

## 🎯 Casos de Uso Comunes

### Flujo Completo: Mesa llama y Mozo responde

```bash
# 1. Mesa escanea QR y llama
POST /qr/table/1/call
Body: { "message": "Necesito la cuenta" }
→ Devuelve call_id: 456

# 2. Mozo ve notificación y lista llamadas pendientes
GET /waiter/calls/pending
→ Ve llamada con id: 456

# 3. Mozo responde que está en camino
PUT /waiter/calls/456/acknowledge
→ Mesa ve que mozo vio la llamada

# 4. Mozo atiende y completa
PUT /waiter/calls/456/complete
→ Llamada desaparece de Firebase, queda en historial
```

---

### Flujo: Mozo se une a restaurante

```bash
# 1. Admin obtiene código de invitación
GET /admin/business
→ invitation_code: "RESTO123"

# 2. Mozo solicita unirse
POST /waiter/join-business
Body: { "invitation_code": "RESTO123" }
→ Crea solicitud con id: 789, status: "pending"

# 3. Admin ve solicitud
GET /admin/staff?status=pending
→ Ve solicitud id: 789

# 4. Admin aprueba
POST /admin/staff/789/approve
→ Mozo recibe notificación, status: "confirmed"

# 5. Mozo puede trabajar
GET /waiter/my-requests
→ Ve solicitud aprobada
```

---

## 📝 Tips

### 1. Usar Scripts de Pre-request

Para auto-actualizar variables después de login:

```javascript
// En el request de Login, tab "Tests":
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    pm.environment.set("auth_token", jsonData.token);
    pm.environment.set("waiter_id", jsonData.user.id);
}
```

### 2. Carpetas con Tests

Puedes agregar tests automáticos en cada request:

```javascript
// Tab "Tests" en cualquier request:
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has success field", function () {
    pm.expect(pm.response.json()).to.have.property('success');
});
```

### 3. Ejecutar Collection Runner

Para probar todos los endpoints automáticamente:
1. Click derecho en la colección
2. "Run collection"
3. Seleccionar environment
4. Click "Run"

---

## 🚀 Environments Múltiples

Puedes crear diferentes environments:

### Local
```
base_url: http://localhost/api
```

### Staging
```
base_url: https://staging.mozoqr.com/api
```

### Production
```
base_url: https://api.mozoqr.com/api
```

Y cambiar entre ellos con el dropdown.

---

## 📚 Recursos Adicionales

- **Análisis de tests fallando**: `docs/WHY_TESTS_ARE_FAILING.md`
- **Reporte final refactorización**: `docs/REFACTORING_COMPLETE_FINAL.md`
- **Documentación completa**: `CLAUDE.md`

---

**¿Problemas?** Revisar logs en `storage/logs/laravel.log`

**¿Preguntas?** Ver documentación en `docs/`
