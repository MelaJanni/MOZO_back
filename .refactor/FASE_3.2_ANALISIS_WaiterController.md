# FASE 3.2: Análisis WaiterController

## Fecha de Inicio
2025-01-04

## Métricas Iniciales
- **Líneas totales**: 2,304
- **Métodos totales**: 35 (34 públicos + 1 privado)
- **Objetivo**: ~400 líneas (-83% reducción)
- **Test baseline**: 19F/55P (72% pass rate)

---

## Inventario de Métodos

### 1️⃣ BUSINESS OPERATIONS (7 métodos) - 320 líneas
**Responsabilidad**: Gestión multi-tenant de negocios del mozo

| Método | Líneas | Descripción |
|--------|--------|-------------|
| `onboardBusiness()` | 108-144 | Primera configuración de negocio |
| `getWaiterBusinesses()` | 640-725 | Lista de negocios del mozo |
| `getActiveTodayBusinesses()` | 726-783 | Negocios activos hoy |
| `leaveBusiness()` | 784-868 | Salir de un negocio |
| `setActiveBusiness()` | 869-936 | Cambiar negocio activo |
| `joinBusiness()` | 937-1107 | Unirse a nuevo negocio |
| `ensureBusinessId()` | 39-107 | Helper privado validación negocio |

**Target Controller**: `BusinessWaiterController.php` (YA EXISTE - MERGE)
- Líneas actuales: ~300
- Líneas después merge: ~620
- Conflicto potencial: Métodos duplicados o similares


### 2️⃣ TABLE OPERATIONS (8 métodos) - 580 líneas
**Responsabilidad**: Gestión y activación de mesas

| Método | Líneas | Descripción |
|--------|--------|-------------|
| `listTables()` | 145-164 | Lista mesas (legacy?) |
| `getBusinessTables()` | 1108-1221 | Mesas por negocio |
| `getAssignedTables()` | 1222-1289 | Mesas asignadas al mozo |
| `getAvailableTables()` | 1290-1326 | Mesas disponibles |
| `activateTable()` | 1327-1402 | Activar mesa individual |
| `deactivateTable()` | 1403-1462 | Desactivar mesa individual |
| `activateMultipleTables()` | 1990-2098 | Activar múltiples mesas |
| `deactivateMultipleTables()` | 2099-2183 | Desactivar múltiples mesas |

**Target Controller**: `WaiterTablesController.php` (NUEVO)
- Líneas estimadas: ~600
- Overlap con: `TableActivationController.php` (ya existe - verificar duplicación)


### 3️⃣ CALL OPERATIONS (6 métodos) - 420 líneas
**Responsabilidad**: Gestión de llamados de clientes

| Método | Líneas | Descripción |
|--------|--------|-------------|
| `getPendingCalls()` | 1463-1522 | Llamados pendientes |
| `getRecentCalls()` | 1523-1584 | Historial reciente |
| `acknowledgeCall()` | 1585-1679 | Aceptar llamado |
| `completeCall()` | 1680-1770 | Completar llamado |
| `resyncCall()` | 1771-1821 | Resincronizar llamado |
| `createCall()` | 1822-1886 | Crear llamado manual |

**Target Controller**: `WaiterCallController.php` (YA EXISTE - REFACTORIZADO)
- Estado actual: 742 líneas (limpio)
- Acción: **MOVER** estos 6 métodos desde WaiterController
- Conflicto potencial: Verificar si ya existen métodos similares


### 4️⃣ NOTIFICATION OPERATIONS (6 métodos) - 420 líneas
**Responsabilidad**: Manejo de notificaciones push y Firebase

| Método | Líneas | Descripción |
|--------|--------|-------------|
| `toggleTableNotifications()` | 165-190 | Toggle notifs por mesa |
| `globalNotifications()` | 191-221 | Notificaciones globales |
| `listNotifications()` | 222-231 | Listar notificaciones |
| `respondNotification()` | 232-258 | Responder notificación |
| `fetchWaiterTables()` | 259-288 | Fetch mesas (legacy?) |
| `fetchWaiterNotifications()` | 289-306 | Fetch notificaciones |

**Target Controller**: `WaiterNotificationsController.php` (NUEVO)
- Líneas estimadas: ~450
- Overlap con: Lógica Firebase en servicios


### 5️⃣ NOTIFICATION MANAGEMENT (3 métodos) - 270 líneas
**Responsabilidad**: CRUD de notificaciones (marcar leídas, procesar)

| Método | Líneas | Descripción |
|--------|--------|-------------|
| `handleNotification()` | 307-514 | Procesar notificación compleja |
| `markNotificationAsRead()` | 515-594 | Marcar individual leída |
| `markMultipleNotificationsAsRead()` | 595-639 | Marcar múltiples leídas |

**Target Controller**: `WaiterNotificationsController.php` (mismo que #4)


### 6️⃣ DASHBOARD & STATS (2 métodos) - 100 líneas
**Responsabilidad**: Métricas y estadísticas

| Método | Líneas | Descripción |
|--------|--------|-------------|
| `getDashboard()` | 2184-2265 | Dashboard completo |
| `diagnoseUser()` | 2266-2304 | Diagnóstico usuario (debug?) |

**Target Controller**: `DashboardController.php` (YA EXISTE)
- Estado actual: ~400 líneas
- Acción: **MOVER** estos 2 métodos (probablemente duplicados)


### 7️⃣ ADMIN/SECURITY (2 métodos) - 150 líneas
**Responsabilidad**: Monitoreo de seguridad

| Método | Líneas | Descripción |
|--------|--------|-------------|
| `getSilencedTables()` | 1887-1914 | Mesas silenciadas |
| `getBlockedIps()` | 1915-1989 | IPs bloqueadas |

**Target Controller**: `IpBlockController.php` (YA EXISTE)
- Estado actual: ~300 líneas
- Acción: **MOVER** estos 2 métodos si aplica


---

## Análisis de Duplicación

### ⚠️ POSIBLES CONFLICTOS DETECTADOS

1. **BusinessWaiterController.php** (ya existe):
   - ¿Ya tiene métodos como `getWaiterBusinesses()`, `setActiveBusiness()`?
   - Verificar antes de merge

2. **TableActivationController.php** (ya existe):
   - ¿Ya tiene `activateTable()`, `deactivateTable()`?
   - ¿Duplicación de lógica?

3. **DashboardController.php** (ya existe):
   - ¿Ya tiene `getDashboard()`?
   - Verificar método existente

4. **WaiterCallController.php** (ya existe, limpio):
   - ¿Ya tiene métodos de calls?
   - Migrar solo si no existen

---

## Estrategia de Refactorización

### FASE 1: Investigación y Consolidación
**Duración**: 2-3 horas

**Tareas**:
1. ✅ Inventario completo de métodos (COMPLETADO)
2. ⏳ Verificar controllers existentes:
   - Leer `BusinessWaiterController.php` (¿qué métodos tiene?)
   - Leer `TableActivationController.php` (¿overlap con table operations?)
   - Leer `DashboardController.php` (¿ya tiene getDashboard?)
   - Leer `WaiterCallController.php` (¿qué call methods tiene?)
   - Leer `IpBlockController.php` (¿tiene getSilencedTables?)
3. ⏳ Decidir estrategia:
   - **MERGE**: Si controller existe y es compatible
   - **MOVE**: Si controller existe pero método no
   - **CREATE**: Si controller no existe

**Output**: Documento de decisión (este archivo actualizado)


### FASE 2: Crear Controllers Nuevos (solo si necesario)
**Duración**: 1 hora

**Nuevo controller requerido**:
- `WaiterNotificationsController.php` (9 métodos, ~690 líneas)
  * Métodos: toggleTableNotifications, globalNotifications, listNotifications, etc.
  * Sin duplicación conocida

- `WaiterTablesController.php` (8 métodos, ~580 líneas)
  * ⚠️ Verificar overlap con `TableActivationController.php`
  * Si hay duplicación → MERGE en lugar de crear nuevo


### FASE 3-7: Migración Gradual (6 fases atómicas)
**Duración**: 2-3 días

#### FASE 3: Migrar Business Operations
- Target: `BusinessWaiterController.php` (merge 7 métodos)
- Actualizar rutas: `/waiter/businesses/*`
- Commit: "refactor(waiter): merge business methods into BusinessWaiterController"

#### FASE 4: Migrar Notification Operations
- Target: `WaiterNotificationsController.php` (crear + 9 métodos)
- Actualizar rutas: `/waiter/notifications/*`
- Commit: "refactor(waiter): extract notifications into WaiterNotificationsController"

#### FASE 5: Migrar Table Operations
- Target: `WaiterTablesController.php` o merge en `TableActivationController.php`
- Actualizar rutas: `/waiter/tables/*`
- Commit: "refactor(waiter): consolidate table operations"

#### FASE 6: Migrar Call Operations
- Target: `WaiterCallController.php` (mover 6 métodos)
- Actualizar rutas: `/waiter/calls/*` (probablemente ya correctas)
- Commit: "refactor(waiter): move call methods to WaiterCallController"

#### FASE 7: Migrar Dashboard/Security
- Target: `DashboardController.php` + `IpBlockController.php`
- Actualizar rutas: `/waiter/dashboard`, `/waiter/blocked-ips`
- Commit: "refactor(waiter): distribute remaining methods"

#### FASE 8: Limpiar WaiterController
- Eliminar todos los métodos migrados
- Dejar solo `__construct()` si es necesario
- O eliminar controller completamente
- Commit: "refactor(waiter): clean WaiterController after migration"


### FASE 9: Documentación y Validación
**Duración**: 1 hora

- Ejecutar tests: `vendor/bin/phpunit`
- Validar 19F/55P mantenido
- Actualizar este documento con métricas finales
- Crear `FASE_3.2_SUMMARY.md`
- Commit: "docs(refactor): complete FASE 3.2 WaiterController"

---

## Métricas Objetivo

| Métrica | Antes | Después | Cambio |
|---------|-------|---------|--------|
| WaiterController líneas | 2,304 | 0-50 | -99% |
| Controllers creados | 0 | 1-2 | +1-2 |
| Controllers modificados | 0 | 4-5 | merge |
| Métodos migrados | 0 | 35 | 100% |
| Rutas actualizadas | 0 | ~30 | - |
| Tests regresión | 0 | 0 | ✅ |
| Commits atómicos | 0 | 6-8 | - |

---

## Siguiente Acción

**DECISION POINT**: Antes de continuar, necesito verificar controllers existentes para evitar duplicación.

**Comando sugerido**:
```bash
# Verificar métodos en controllers existentes
grep -n "public function" app/Http/Controllers/BusinessWaiterController.php
grep -n "public function" app/Http/Controllers/TableActivationController.php
grep -n "public function" app/Http/Controllers/DashboardController.php
grep -n "public function" app/Http/Controllers/WaiterCallController.php
grep -n "public function" app/Http/Controllers/IpBlockController.php
```

**Espero confirmación para continuar con investigación de controllers existentes.**

---

## Estado
🔄 **EN PROGRESO** - Fase 0: Análisis completado, esperando decisión sobre verificación de duplicación
