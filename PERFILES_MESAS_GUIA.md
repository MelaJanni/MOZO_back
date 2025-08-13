# 🎯 **Perfiles de Mesas - Guía Completa**

## 📋 **Funcionalidades Implementadas**

### ✅ **CRUD Completo**
- **Crear** perfil con nombre, descripción y mesas
- **Listar** perfiles del mozo actual  
- **Ver** perfil específico con estado de mesas
- **Actualizar** perfil (nombre, descripción, mesas)
- **Eliminar** perfil

### ⚡ **Activación Inteligente**
- **Activar perfil completo** con un solo click
- **Detección de conflictos** (mesas ocupadas por otros mozos)
- **Activación parcial** (activa disponibles, reporta ocupadas)
- **Auto-completar** cuando mesa se libera

---

## 🚀 **API Endpoints**

### **CRUD Básico**
```bash
# Listar perfiles del mozo
GET /api/waiter/table-profiles

# Crear nuevo perfil
POST /api/waiter/table-profiles
{
    "name": "Patio Trasero",
    "description": "Mesas del patio posterior",
    "table_ids": [5, 7, 10, 12]
}

# Ver perfil específico  
GET /api/waiter/table-profiles/{profile_id}

# Actualizar perfil
PUT /api/waiter/table-profiles/{profile_id}
{
    "name": "Patio Trasero Actualizado",
    "description": "Nueva descripción",
    "table_ids": [5, 7, 10, 12, 15]
}

# Eliminar perfil
DELETE /api/waiter/table-profiles/{profile_id}
```

### **Activación**
```bash
# Activar perfil completo
POST /api/waiter/table-profiles/{profile_id}/activate

# Desactivar perfil
POST /api/waiter/table-profiles/{profile_id}/deactivate

# Ver notificaciones de auto-completar
GET /api/waiter/table-profiles/notifications

# Marcar notificación como leída
POST /api/waiter/table-profiles/notifications/{notification_id}/read
```

---

## 💡 **Casos de Uso**

### **Caso 1: Creación de Perfil "Patio Trasero"**
```json
{
    "name": "Patio Trasero",
    "description": "Mesas 5, 7 y 10 del patio posterior",
    "table_ids": [5, 7, 10]
}
```

### **Caso 2: Activación con Conflictos**
```json
// Request
POST /api/waiter/table-profiles/1/activate

// Response
{
    "success": true,
    "message": "Perfil activado. 2 mesas activadas. 1 ya era tuya. Conflictos: Mesa 7 (Juan Pérez)",
    "result": {
        "profile_name": "Patio Trasero",
        "total_tables": 3,
        "activated_tables": 2,
        "own_tables": 1,
        "conflicting_tables": [
            {
                "id": 7,
                "number": 7,
                "name": "Mesa 7",
                "assigned_waiter": {
                    "id": 2,
                    "name": "Juan Pérez"
                }
            }
        ]
    }
}
```

### **Caso 3: Auto-completar**
```
Flujo:
1. Mozo Juan tiene perfil "Patio" activo (mesas 5, 7, 10)
2. Mesa 7 está ocupada por Mozo Pedro  
3. Mozo Pedro hace logout → Mesa 7 se libera
4. Sistema detecta que Mesa 7 está en perfil activo de Juan
5. Sistema auto-asigna Mesa 7 a Juan automáticamente
6. Juan recibe notificación: "Mesa 7 auto-activada (perfil: Patio)"
```

---

## 🔧 **Validaciones Implementadas**

### **Al Crear/Actualizar Perfil**
- ✅ Nombre único por mozo
- ✅ Máximo 20 mesas por perfil
- ✅ Mínimo 1 mesa por perfil
- ✅ Solo mesas del business del mozo
- ✅ Verificar que las mesas existen

### **Al Activar Perfil**
- ✅ Solo el dueño puede activar su perfil
- ✅ Desactivar otros perfiles activos del mozo
- ✅ Mesas silenciadas se asignan igual (quedan silenciadas)
- ✅ Reportar conflictos con otros mozos
- ✅ Activar solo mesas disponibles

### **Auto-completar**
- ✅ Solo activar si perfil está activo
- ✅ No re-asignar al mismo mozo que se deslogueó
- ✅ Verificar que dueño del perfil existe y es mozo
- ✅ Solo un perfil por mesa liberada

---

## 📊 **Estructura de Base de Datos**

### **Tabla `profiles`**
```sql
- id (PK)
- user_id (FK → users)
- business_id (FK → businesses)  
- name (varchar)
- description (text, nullable)
- is_active (boolean, default false)
- activated_at (timestamp, nullable)
- created_at, updated_at
```

### **Tabla `profile_table` (pivot)**
```sql
- id (PK)
- profile_id (FK → profiles)
- table_id (FK → tables)
- created_at, updated_at
- UNIQUE(profile_id, table_id)
```

---

## 🎯 **Ejemplo Completo de Flujo**

### **1. Crear Perfil**
```bash
curl -X POST http://mozoqr.com/api/waiter/table-profiles \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Salón Principal", 
    "description": "Mesas principales del salón",
    "table_ids": [1, 2, 3, 4, 8, 9]
  }'
```

### **2. Activar Perfil**
```bash
curl -X POST http://mozoqr.com/api/waiter/table-profiles/1/activate \
  -H "Authorization: Bearer {token}"
```

### **3. Ver Estado**
```bash
curl -X GET http://mozoqr.com/api/waiter/table-profiles/1 \
  -H "Authorization: Bearer {token}"
```

### **4. Ver Notificaciones Auto-completar**
```bash
curl -X GET http://mozoqr.com/api/waiter/table-profiles/notifications \
  -H "Authorization: Bearer {token}"
```

---

## 🚨 **Casos Edge Implementados**

### **Mesa Inexistente**
- ❌ Error 422: "Algunas mesas no existen o no pertenecen a tu negocio"

### **Nombre Duplicado** 
- ❌ Error 422: "Ya tienes un perfil con ese nombre"

### **Perfil de Otro Mozo**
- ❌ Error 403: "No tienes acceso a este perfil"

### **Auto-completar Circular**
- ✅ Evitado: No re-asignar al mozo que se acaba de desloguear

### **Múltiples Perfiles Activos**
- ✅ Solo un perfil activo por mozo (desactiva otros automáticamente)

---

## 📈 **Logs y Monitoreo**

### **Eventos Logueados**
- ✅ Creación de perfil
- ✅ Activación de perfil  
- ✅ Auto-completar exitoso
- ✅ Conflictos en activación
- ✅ Notificaciones de auto-completar

### **Ejemplo de Log**
```json
{
    "level": "info",
    "message": "Mesa auto-completada por perfil activo",
    "context": {
        "table_id": 7,
        "table_number": 7,
        "profile_id": 1,
        "profile_name": "Patio Trasero",
        "new_waiter_id": 3,
        "new_waiter_name": "Carlos Rodriguez",
        "previous_waiter_id": 2
    }
}
```

---

## ✅ **Testing Checklist**

Para probar la funcionalidad:

1. **Crear perfil** con mesas válidas
2. **Intentar crear** perfil con nombre duplicado
3. **Activar perfil** sin conflictos
4. **Activar perfil** con algunas mesas ocupadas
5. **Logout de mozo** con mesa en perfil activo de otro
6. **Verificar auto-completar** funcionó
7. **Ver notificaciones** de auto-completar
8. **Marcar notificaciones** como leídas

**¡La funcionalidad está completa y lista para usar!** 🎉