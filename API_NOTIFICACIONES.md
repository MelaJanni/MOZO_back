# 📱 API NOTIFICACIONES - MOZO APP

**Base URL**: `https://api.mozoqr.com/api`

---

## 🔐 AUTENTICACIÓN

Todos los endpoints requieren token Bearer:
```
Authorization: Bearer {token}
```

---

## 📲 FCM TOKENS

### 1. Registrar Token
```http
POST /mozo/fcm/register-token
```

**Body**:
```json
{
  "token": "fcm_token_string",
  "platform": "android|ios|web",
  "device_info": {
    "model": "Pixel 6",
    "os_version": "Android 13"
  }
}
```

**Response**:
```json
{
  "success": true,
  "message": "Token FCM registrado correctamente",
  "data": {
    "user_id": 123,
    "platform": "android",
    "registered_at": "2025-01-04T10:30:00Z",
    "will_receive_notifications": true
  }
}
```

---

### 2. Refrescar Token
```http
POST /mozo/fcm/refresh-token
```

**Body**:
```json
{
  "new_token": "new_fcm_token",
  "platform": "android|ios|web",
  "old_token": "old_fcm_token" // opcional
}
```

**Response**:
```json
{
  "success": true,
  "message": "Token FCM actualizado correctamente"
}
```

---

### 3. Estado de Tokens
```http
GET /mozo/fcm/token-status
```

**Response**:
```json
{
  "success": true,
  "tokens": [
    {
      "id": 1,
      "platform": "android",
      "token_preview": "fK7gH9sL2mN4pQ6r...",
      "created_at": "2025-01-01T10:00:00Z",
      "expires_at": "2025-03-01T10:00:00Z",
      "is_expired": false,
      "days_until_expiry": 56
    }
  ],
  "total_active_tokens": 2,
  "needs_refresh": false
}
```

---

### 4. Test de Notificación
```http
POST /mozo/fcm/test
```

**Body**:
```json
{
  "title": "Test Title",
  "body": "Test message",
  "platform": "android" // opcional
}
```

**Response**:
```json
{
  "success": true,
  "message": "Notificación de prueba enviada",
  "data": {
    "title": "Test Title",
    "body": "Test message",
    "sent_at": "2025-01-04T10:30:00Z",
    "user_id": 123,
    "platform_filter": "android",
    "sent": 1,
    "total": 1,
    "fcm_result": "sent"
  }
}
```

---

### 5. Eliminar Token
```http
DELETE /mozo/fcm/delete-token
```

**Body**:
```json
{
  "token": "fcm_token_to_delete", // opcional
  "platform": "android" // opcional
}
```

**Response**:
```json
{
  "success": true,
  "message": "Se eliminaron 1 token(s)",
  "deleted_count": 1
}
```

---

## 🔔 NOTIFICACIONES

### 6. Obtener Notificaciones del Usuario
```http
GET /user/notifications?page=1&per_page=20
```

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": "abc123",
      "type": "waiter_call",
      "title": "🔔 Mesa 5",
      "body": "Nueva llamada",
      "data": {
        "call_id": "789",
        "table_number": "5",
        "urgency": "normal"
      },
      "read_at": null,
      "created_at": "2025-01-04T10:30:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 45
  }
}
```

---

### 7. Marcar Notificación como Leída
```http
POST /user/notifications/{id}/read
```

**Response**:
```json
{
  "success": true,
  "message": "Notificación marcada como leída"
}
```

---

## 📊 FORMATO DE NOTIFICACIONES FCM

### Llamada de Mesa (waiter_call)
```json
{
  "notification": {
    "title": "🔔 Mesa 5",
    "body": "Nueva llamada"
  },
  "data": {
    "type": "waiter_call",
    "call_id": "789",
    "table_id": "456",
    "table_number": "5",
    "waiter_id": "123",
    "status": "pending",
    "urgency": "normal|high",
    "timestamp": "1704361800",
    "source": "waiter_call_system"
  }
}
```

### Notificación de Staff (staff_request)
```json
{
  "notification": {
    "title": "Nueva solicitud de staff",
    "body": "Juan Pérez quiere unirse a tu negocio"
  },
  "data": {
    "type": "staff_request_created",
    "staff_id": "456",
    "business_id": "789",
    "user_id": "123",
    "timestamp": "1704361800"
  }
}
```

---

## ⚙️ CONFIGURACIÓN FIREBASE WEB

### Obtener Configuración Pública
```http
GET /firebase/config
```

**Response**:
```json
{
  "apiKey": "AIza...",
  "authDomain": "mozoqr.firebaseapp.com",
  "databaseURL": "https://mozoqr.firebaseio.com",
  "projectId": "mozoqr-7d32c",
  "storageBucket": "mozoqr-7d32c.appspot.com",
  "messagingSenderId": "123456789",
  "appId": "1:123456789:web:abc123"
}
```

---

## 🚨 CÓDIGOS DE ERROR

| Código | Descripción |
|--------|-------------|
| 200 | OK |
| 400 | Bad Request (validación falló) |
| 401 | Unauthorized (token inválido) |
| 403 | Forbidden (solo mozos pueden registrar tokens) |
| 404 | Not Found |
| 429 | Too Many Requests (rate limit) |
| 500 | Internal Server Error |

---

## 📝 NOTAS

### Registro de Token
- Solo usuarios con rol "waiter" pueden registrar tokens
- Tokens expiran en 60 días
- Se puede tener múltiples tokens por plataforma

### Plataformas Soportadas
- `android` - App nativa Android
- `ios` - App nativa iOS
- `web` - Progressive Web App (PWA)

### Notificaciones en Tiempo Real
Las notificaciones se envían vía FCM inmediatamente cuando:
- Una mesa llama al mozo
- Un staff es confirmado/rechazado
- Un admin invita a un staff

### Limpieza Automática
Los tokens expirados se eliminan automáticamente cada día a las 00:00
