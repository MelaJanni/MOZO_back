# 🧪 TESTING GUIDE: Firebase Real-time Integration

## 📋 RESUMEN

Esta guía proporciona tests paso a paso para validar la integración de Firebase real-time con el sistema público de QR de MozoQR.

## 🔧 SETUP PREVIO

### 1. Verificar Variables de Entorno

Asegúrate de que estas variables estén configuradas en tu `.env`:

```env
# Firebase Frontend Config
FIREBASE_API_KEY=your-api-key
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_STORAGE_BUCKET=your-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=123456789
FIREBASE_APP_ID=1:123456789:web:abc123def456

# Firebase Backend Config
FIREBASE_SERVER_KEY=your-server-key
FIREBASE_SERVICE_ACCOUNT_PATH=storage/app/firebase/firebase.json
```

### 2. Verificar Base de Datos

Ejecutar estas consultas para verificar datos de prueba:

```sql
-- Verificar que existan restaurants
SELECT id, name, slug FROM restaurants LIMIT 5;

-- Verificar que existan tables con códigos
SELECT id, number, code, restaurant_id, business_id, notifications_enabled, active_waiter_id 
FROM tables 
WHERE code IS NOT NULL 
LIMIT 5;

-- Verificar que existan users/waiters
SELECT id, name, role FROM users WHERE role IN ('waiter', 'admin') LIMIT 5;
```

## 🚀 TESTS DE FUNCIONALIDAD

### Test 1: Firebase Config Endpoint

```bash
# Probar endpoint de configuración Firebase
curl -X GET "http://localhost:8000/api/firebase/config" \
  -H "Content-Type: application/json" | jq '.'

# Respuesta esperada:
# {
#   "success": true,
#   "firebase_config": {
#     "apiKey": "your-api-key",
#     "authDomain": "your-project.firebaseapp.com",
#     ...
#   }
# }
```

### Test 2: QR Table Config Endpoint

```bash
# Usar un table_id existente de tu base de datos
TABLE_ID=5

curl -X GET "http://localhost:8000/api/firebase/table/${TABLE_ID}/config" \
  -H "Content-Type: application/json" | jq '.'

# Respuesta esperada:
# {
#   "success": true,
#   "table": {
#     "id": 5,
#     "number": "5",
#     "name": "Mesa VIP",
#     "notifications_enabled": true,
#     "has_active_waiter": true
#   },
#   "firebase_config": {...},
#   "firestore_paths": {
#     "table_calls": "tables/5/waiter_calls",
#     "table_status": "tables/5/status/current"
#   }
# }
```

### Test 3: Public QR Info Endpoint

```bash
# Usar restaurant slug y table code existentes
RESTAURANT_SLUG="test-restaurant"
TABLE_CODE="ABC123"

curl -X GET "http://localhost:8000/api/qr/${RESTAURANT_SLUG}/${TABLE_CODE}" \
  -H "Content-Type: application/json" | jq '.'

# Respuesta esperada debería incluir firebase_config
```

### Test 4: Waiter Call con Firebase

```bash
# Llamar al mozo usando el endpoint público
TABLE_ID=5

curl -X POST "http://localhost:8000/api/tables/${TABLE_ID}/call-waiter" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Test desde integración Firebase",
    "urgency": "normal",
    "client_info": {
      "source": "integration_test",
      "timestamp": "'$(date -Iseconds)'"
    }
  }' | jq '.'

# Verificar en logs que se escribió a Firestore
tail -f storage/logs/laravel.log | grep -i "firestore\|firebase"
```

### Test 5: Table Status Endpoint

```bash
# Probar endpoint de estado de mesa
TABLE_ID=5

curl -X GET "http://localhost:8000/api/table/${TABLE_ID}/status" \
  -H "Content-Type: application/json" | jq '.'

# Respuesta esperada:
# {
#   "success": true,
#   "data": {
#     "table_id": 5,
#     "can_call_waiter": true,
#     "active_call": null,
#     "waiter": {
#       "name": "Juan Pérez"
#     }
#   }
# }
```

## 🔥 TESTS DE FIREBASE REAL-TIME

### Test 6: Verificar Escritura en Firestore

1. **Abrir Firebase Console** → Ir a Firestore Database
2. **Ejecutar llamada de mozo**:
   ```bash
   curl -X POST "http://localhost:8000/api/tables/5/call-waiter" \
     -H "Content-Type: application/json" \
     -d '{"message": "Test Firebase Real-time"}'
   ```
3. **Verificar en Firestore** que aparezcan documentos en:
   - `tables/5/waiter_calls/{call_id}`
   - `waiters/{waiter_id}/calls/{call_id}`
   - `businesses/{business_id}/waiter_calls/{call_id}`

### Test 7: Simular Flujo Completo

**Script de prueba completa:**

```bash
#!/bin/bash

echo "🧪 Testing Firebase Integration..."

TABLE_ID=5
WAITER_ID=10  # Usar un waiter_id existente

# 1. Llamar al mozo
echo "📞 Step 1: Calling waiter..."
CALL_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/tables/${TABLE_ID}/call-waiter" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Test complete flow",
    "urgency": "normal"
  }')

CALL_ID=$(echo $CALL_RESPONSE | jq -r '.call.id')
echo "✅ Call created with ID: $CALL_ID"

# 2. Simular acknowledge del mozo
echo "👨‍🍳 Step 2: Waiter acknowledges call..."
AUTH_TOKEN="YOUR_WAITER_AUTH_TOKEN"  # Obtener token real

curl -s -X POST "http://localhost:8000/api/waiter/calls/${CALL_ID}/acknowledge" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" | jq '.'

# 3. Simular completar llamada
echo "✅ Step 3: Waiter completes call..."
curl -s -X POST "http://localhost:8000/api/waiter/calls/${CALL_ID}/complete" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" | jq '.'

echo "🎉 Test completed!"
```

## 🌐 TESTS DE FRONTEND

### Test 8: Probar Frontend Integration

**HTML de prueba simple:**

```html
<!DOCTYPE html>
<html>
<head>
    <title>Firebase Integration Test</title>
    <script type="module">
        // Configurar Firebase
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/9.0.0/firebase-app.js';
        import { getFirestore, collection, onSnapshot } from 'https://www.gstatic.com/firebasejs/9.0.0/firebase-firestore.js';

        // Obtener config del backend
        const configResponse = await fetch('/api/firebase/config');
        const { firebase_config } = await configResponse.json();

        // Inicializar Firebase
        const app = initializeApp(firebase_config);
        const db = getFirestore(app);

        // Escuchar llamadas de mesa
        const TABLE_ID = 5;
        const callsRef = collection(db, `tables/${TABLE_ID}/waiter_calls`);

        onSnapshot(callsRef, (snapshot) => {
            snapshot.docChanges().forEach((change) => {
                const callData = change.doc.data();
                console.log('🔔 Real-time event:', callData);
                
                // Mostrar en página
                const statusDiv = document.getElementById('status');
                statusDiv.innerHTML += `<p>${callData.event_type}: ${callData.message}</p>`;
            });
        });

        // Función para llamar mozo
        window.callWaiter = async function() {
            const response = await fetch(`/api/tables/${TABLE_ID}/call-waiter`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: 'Test desde frontend' })
            });
            const result = await response.json();
            console.log('Call result:', result);
        };
    </script>
</head>
<body>
    <h1>Firebase Real-time Test</h1>
    <button onclick="callWaiter()">🔔 Llamar Mozo</button>
    <div id="status"></div>
</body>
</html>
```

### Test 9: Probar Modal Components

**Prueba del modal en consola del navegador:**

```javascript
// Crear instancia del modal
const modal = new WaiterCallModal();

// Probar diferentes estados
setTimeout(() => modal.showCalling(), 1000);
setTimeout(() => modal.showAcknowledged(), 3000);
setTimeout(() => modal.showCompleted(), 5000);
```

## 🚨 TROUBLESHOOTING

### Errores Comunes

**1. "Firebase not initialized"**
- Verificar que `/api/firebase/config` responda correctamente
- Revisar que las variables de entorno estén configuradas

**2. "Permission denied" en Firestore**
- Verificar las reglas de Firestore
- Comprobar que el service account tenga permisos

**3. "Table not found"**
- Verificar que existan registros en la tabla `tables`
- Comprobar que los códigos QR sean válidos

**4. Modal no aparece**
- Verificar que Font Awesome esté cargado
- Comprobar errores en consola del navegador
- Verificar que los estilos CSS estén aplicados

### Comandos de Debug

```bash
# Ver logs de Laravel en tiempo real
tail -f storage/logs/laravel.log

# Limpiar cache de Laravel
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Verificar conexión a base de datos
php artisan tinker
>>> DB::connection()->getPdo();
```

### Verificar Firestore Rules

```javascript
// En Firebase Console > Firestore > Rules
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    // Permitir lectura/escritura temporal para testing
    match /{document=**} {
      allow read, write: if true;
    }
  }
}
```

## ✅ CHECKLIST DE TESTING

- [ ] Variables de entorno configuradas
- [ ] Base de datos con datos de prueba
- [ ] Endpoint `/api/firebase/config` responde
- [ ] Endpoint `/api/firebase/table/{id}/config` responde
- [ ] Endpoint `/api/qr/{restaurant}/{table}` responde
- [ ] Endpoint `/api/table/{id}/status` responde  
- [ ] Llamada de mozo escribe en Firestore
- [ ] Frontend recibe eventos en tiempo real
- [ ] Modal funciona correctamente
- [ ] Fallback polling funciona
- [ ] Responsive design correcto

## 🎯 MÉTRICAS DE ÉXITO

1. **Latencia de notificación** < 2 segundos
2. **Tasa de éxito Firebase** > 95%
3. **Fallback automático** funciona
4. **UI responsive** en móviles
5. **Sin errores JavaScript** en consola
6. **Logs backend** sin errores críticos

Con estos tests puedes verificar que toda la integración funciona correctamente antes del despliegue en producción.