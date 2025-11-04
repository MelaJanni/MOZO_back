# 🔧 PLAN DE REFACTORIZACIÓN - MOZO BACKEND

**Fecha**: 2025-01-04
**Objetivo**: Reducir tamaño del proyecto manteniendo funcionalidades actuales
**Principio rector**: NO ROMPER NADA - Verificación en cada paso

---

## 📋 ÍNDICE

1. [Estrategia General](#estrategia-general)
2. [Métricas Actuales](#métricas-actuales)
3. [Fases de Refactorización](#fases-de-refactorización)
4. [Plan de Testing y Verificación](#plan-de-testing-y-verificación)
5. [Rollback Strategy](#rollback-strategy)
6. [Checklist de Aprobación](#checklist-de-aprobación)

---

## 🎯 ESTRATEGIA GENERAL

### Principios

1. **Incremental**: Cambios pequeños y verificables
2. **Backward Compatible**: Mantener endpoints existentes funcionando
3. **Test-First**: Verificar antes y después de cada cambio
4. **Rollback-Ready**: Git commits atómicos, fácil revertir
5. **Zero Downtime**: No afectar producción

### Enfoque

```
┌─────────────────────────────────────────┐
│  FASE 1: PREPARACIÓN (No tocar código)  │
│  - Crear tests de regresión             │
│  - Documentar endpoints críticos        │
│  - Crear backup completo                │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  FASE 2: QUICK WINS (Bajo riesgo)      │
│  - Eliminar código duplicado            │
│  - Crear middlewares                    │
│  - Extraer helpers                      │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  FASE 3: REFACTORIZACIÓN (Alto impacto) │
│  - Dividir controladores grandes        │
│  - Consolidar servicios                 │
│  - Implementar Actions                  │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  FASE 4: OPTIMIZACIÓN (Opcional)       │
│  - Repository pattern                   │
│  - Consolidar migraciones               │
│  - Cleanup final                        │
└─────────────────────────────────────────┘
```

---

## 📊 MÉTRICAS ACTUALES

| Componente | Estado Actual | Objetivo | Reducción |
|------------|---------------|----------|-----------|
| **Controladores** | 17,664 líneas (8 archivos) | 8,000 líneas | -55% |
| **Servicios** | 5,643 líneas (5 archivos) | 2,500 líneas | -56% |
| **Código duplicado** | 123+ patrones | 0 | -100% |
| **Migraciones** | 50 archivos | 15 archivos | -70% |
| **Modelos (fat)** | 358 líneas (User) | 150 líneas | -58% |

### Archivos Más Problemáticos

1. **WaiterCallController.php** - 2,693 líneas → **300 líneas** (-89%)
2. **WaiterController.php** - 2,303 líneas → **400 líneas** (-83%)
3. **AdminController.php** - 2,027 líneas → **600 líneas** (-70%)
4. **FirebaseService.php** - 905 líneas → **400 líneas** (-56%)
5. **User.php (modelo)** - 358 líneas → **150 líneas** (-58%)

---

## 🚀 FASES DE REFACTORIZACIÓN

---

## FASE 1: PREPARACIÓN (CRÍTICA)
**Duración**: 2-3 días
**Riesgo**: ⚪ NINGUNO (no toca código)
**Prioridad**: 🔴 OBLIGATORIA

### Objetivos
- Crear red de seguridad para refactorización
- Documentar comportamiento actual
- Establecer baseline de tests

### Tareas

#### 1.1 Crear Tests de Endpoints Críticos
```bash
# tests/Feature/Smoke/
- WaiterCallEndpointsTest.php
- StaffEndpointsTest.php
- AdminEndpointsTest.php
- NotificationEndpointsTest.php
```

**Tests a crear (mínimo 20):**
```php
// WaiterCallEndpointsTest.php
✓ test_call_waiter_creates_notification
✓ test_acknowledge_call_updates_status
✓ test_complete_call_removes_from_firebase
✓ test_pending_calls_returns_correct_format
✓ test_call_history_pagination

// StaffEndpointsTest.php
✓ test_create_staff_request_sends_notification
✓ test_approve_staff_updates_status
✓ test_reject_staff_sends_notification
✓ test_my_requests_filters_by_status

// AdminEndpointsTest.php
✓ test_remove_staff_cleans_firebase
✓ test_get_business_staff_returns_all
✓ test_update_business_settings

// NotificationEndpointsTest.php
✓ test_register_fcm_token
✓ test_refresh_token_updates_existing
✓ test_notification_sent_to_correct_user
```

**Comando para ejecutar:**
```bash
php artisan test --testsuite=Smoke
```

**Criterio de éxito**: Todos los tests pasan (baseline)

---

#### 1.2 Documentar Endpoints Críticos
```bash
# docs/api/
- waiter_calls.http          # Requests HTTP de ejemplo
- staff_management.http
- notifications.http
```

**Ejemplo**: `waiter_calls.http`
```http
### Call Waiter
POST {{base_url}}/api/qr/table/1/call
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "message": "Necesito la cuenta",
  "urgency": "normal"
}

### Expected Response
# Status: 200
# Body: { "success": true, "call": { "id": 123, ... } }
```

**Criterio de éxito**: 30+ requests documentados

---

#### 1.3 Crear Backup Completo
```bash
# Ejecutar:
mkdir -p backups/pre_refactor_2025_01_04
cp -r app/ backups/pre_refactor_2025_01_04/
cp -r database/ backups/pre_refactor_2025_01_04/
cp -r routes/ backups/pre_refactor_2025_01_04/

# Git tag
git tag -a v1.0-pre-refactor -m "Estado antes de refactorización"
git push --tags
```

**Criterio de éxito**: Tag creado, backup verificado

---

#### 1.4 Configurar Git Strategy
```bash
# Crear rama de refactorización
git checkout -b refactor/phase-1-quick-wins

# Configurar commits atómicos
# Cada commit = 1 cambio verificable
```

**Política de commits:**
```
feat(refactor): extraer BusinessIdValidator middleware

- Crea app/Http/Middleware/EnsureActiveBusiness.php
- Tests: ✓ 5 tests pasando
- No rompe: ✓ Smoke tests pasando
- Rollback: git revert <commit-hash>
```

---

### ✅ Checklist Fase 1

- [ ] Tests de endpoints críticos creados (20+)
- [ ] Todos los tests pasan (baseline establecido)
- [ ] Endpoints documentados en .http files (30+)
- [ ] Backup completo creado y verificado
- [ ] Git tag creado (v1.0-pre-refactor)
- [ ] Rama de refactorización creada
- [ ] Política de commits documentada

**Tiempo estimado**: 2-3 días
**Criterio para avanzar**: Todos los items del checklist completados

---

## FASE 2: QUICK WINS (BAJO RIESGO)
**Duración**: 3-4 días
**Riesgo**: 🟢 BAJO
**Prioridad**: 🟡 ALTA

### Objetivos
- Reducir código duplicado sin cambiar lógica
- Mejorar estructura sin tocar funcionalidad
- Ganar confianza para refactors más grandes

---

### 2.1 Crear Middleware `EnsureActiveBusiness`
**Impacto**: Elimina 123 duplicaciones
**Riesgo**: Bajo (fácil de revertir)
**Tiempo**: 4-6 horas

#### Problema Actual
```php
// Este código se repite 123 veces:
$businessId = $this->activeBusinessId($user, 'admin');
if (!$businessId) {
    return response()->json([
        'success' => false,
        'message' => 'No tienes un negocio activo...'
    ], 400);
}
```

#### Solución
```php
// app/Http/Middleware/EnsureActiveBusiness.php
class EnsureActiveBusiness
{
    public function handle(Request $request, Closure $next, ?string $role = null)
    {
        $user = $request->user();

        // Resolver business usando servicio
        $businessId = app(BusinessResolver::class)
            ->resolveForUser($user, $role);

        if (!$businessId) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un negocio activo asignado'
            ], 400);
        }

        // Inyectar en request para uso posterior
        $request->merge(['business_id' => $businessId]);
        $request->attributes->set('business_id', $businessId);

        return $next($request);
    }
}
```

#### BusinessResolver Service
```php
// app/Services/Business/BusinessResolver.php
class BusinessResolver
{
    public function resolveForUser(User $user, ?string $role = null): ?int
    {
        // Lógica extraída de los métodos duplicados
        if ($role === 'admin') {
            return $user->adminProfiles()->first()?->business_id;
        }

        if ($role === 'waiter') {
            return $user->waiterProfiles()->first()?->active_business_id;
        }

        return $user->active_business_id;
    }
}
```

#### Aplicar en Routes
```php
// routes/api.php - ANTES
Route::post('/admin/staff', [AdminController::class, 'createStaff'])
    ->middleware('auth:sanctum');

// routes/api.php - DESPUÉS
Route::post('/admin/staff', [AdminController::class, 'createStaff'])
    ->middleware(['auth:sanctum', 'business:admin']);
```

#### Refactorizar Controladores
```php
// AdminController.php - ANTES (2,027 líneas)
public function createStaff(Request $request)
{
    $user = Auth::user();
    $businessId = $this->activeBusinessId($user, 'admin');
    if (!$businessId) {
        return response()->json([
            'success' => false,
            'message' => 'No tienes un negocio activo...'
        ], 400);
    }
    // ... resto del método
}

// AdminController.php - DESPUÉS
public function createStaff(Request $request)
{
    $businessId = $request->get('business_id'); // Inyectado por middleware
    // ... resto del método (8 líneas menos)
}
```

#### Plan de Migración
```
1. Crear middleware + tests (2h)
2. Crear BusinessResolver service (1h)
3. Aplicar en 5 controladores más pequeños primero (2h)
4. Verificar tests
5. Aplicar en WaiterCallController (1h)
6. Aplicar en AdminController (1h)
7. Verificar tests finales
```

#### Tests
```php
// tests/Unit/Middleware/EnsureActiveBusinessTest.php
✓ test_middleware_allows_request_with_active_business
✓ test_middleware_rejects_request_without_business
✓ test_middleware_injects_business_id_into_request
✓ test_middleware_works_with_admin_role
✓ test_middleware_works_with_waiter_role
```

**Reducción esperada**: -984 líneas (8 líneas × 123 ocurrencias)

---

### 2.2 Crear Trait `JsonResponses`
**Impacto**: Estandariza 500+ respuestas JSON
**Riesgo**: Muy bajo
**Tiempo**: 2-3 horas

#### Problema Actual
```php
// 500+ variaciones de esto:
return response()->json(['success' => true, 'data' => ...]);
return response()->json(['message' => '...', 'result' => ...]);
return response()->json(['status' => 'ok', ...]);
```

#### Solución
```php
// app/Http/Responses/JsonResponses.php
trait JsonResponses
{
    protected function success($data = null, string $message = null, int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected function error(string $message, int $code = 400, $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }

    protected function created($data, string $message = 'Creado exitosamente')
    {
        return $this->success($data, $message, 201);
    }

    protected function noContent(string $message = 'Eliminado exitosamente')
    {
        return response()->json([
            'success' => true,
            'message' => $message
        ], 200);
    }
}
```

#### Aplicar en Controladores
```php
// AdminController.php
class AdminController extends Controller
{
    use JsonResponses; // ← Agregar trait

    public function createStaff(Request $request)
    {
        // ANTES
        return response()->json([
            'success' => true,
            'message' => 'Staff creado',
            'data' => $staff
        ], 201);

        // DESPUÉS
        return $this->created($staff, 'Staff creado');
    }
}
```

**Reducción esperada**: -1,500 líneas (3 líneas → 1 línea × 500 ocurrencias)

---

### 2.3 Consolidar Servicios Firebase
**Impacto**: -600 líneas, elimina duplicación
**Riesgo**: Medio (requiere testing extensivo)
**Tiempo**: 1 día

#### Problema Actual
```
FirebaseService.php (905 líneas)
FirebaseNotificationService.php (616 líneas)
↓
Duplicación de métodos:
- sendToDevice() duplicado
- sendBatch() vs sendToMultipleDevices()
- buildPayload() vs buildMessagePayload()
```

#### Solución: Consolidar en 1 Servicio
```
ANTES:
app/Services/
├── FirebaseService.php (905 líneas)
└── FirebaseNotificationService.php (616 líneas)

DESPUÉS:
app/Services/Firebase/
├── FirebaseService.php (400 líneas)
│   ├── sendToUser()
│   ├── sendBatch()
│   ├── writeToPath()
│   └── deleteFromPath()
├── FirebaseClient.php (150 líneas)
│   ├── getValidAccessToken() ✅ Auto-refresh
│   └── sendRequest()
└── MessageBuilder.php (100 líneas)
    ├── buildNotificationMessage()
    └── buildDataOnlyMessage()
```

#### Plan de Migración
```
1. Crear FirebaseClient (extraer auth/HTTP) (2h)
2. Crear MessageBuilder (extraer payload building) (1h)
3. Refactorizar FirebaseService usando ambos (2h)
4. Actualizar todos los usos de FirebaseNotificationService (1h)
5. Eliminar FirebaseNotificationService.php (5min)
6. Ejecutar tests de notificaciones (30min)
```

#### Tests Críticos
```php
✓ test_send_to_user_works_after_refactor
✓ test_send_batch_parallel_still_works
✓ test_access_token_auto_refreshes
✓ test_firebase_rtdb_write_works
✓ test_notification_format_unchanged
```

**Reducción esperada**: -616 líneas (eliminar archivo completo)

---

### 2.4 Extraer Helpers Globales
**Impacto**: Reduce boilerplate
**Riesgo**: Muy bajo
**Tiempo**: 2 horas

#### Problema: Lógica repetida sin hogar
```php
// Aparece 50+ veces
$formattedTime = Carbon::parse($time)->diffForHumans();

// Aparece 30+ veces
$phoneClean = preg_replace('/[^0-9]/', '', $phone);
```

#### Solución
```php
// app/Helpers/helpers.php
if (!function_exists('format_time_diff')) {
    function format_time_diff($datetime): string
    {
        return Carbon::parse($datetime)->diffForHumans();
    }
}

if (!function_exists('clean_phone')) {
    function clean_phone(?string $phone): ?string
    {
        if (!$phone) return null;
        return preg_replace('/[^0-9]/', '', $phone);
    }
}

if (!function_exists('log_action')) {
    function log_action(string $action, array $context = []): void
    {
        Log::info($action, array_merge([
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }
}
```

#### Autoload en composer.json
```json
{
    "autoload": {
        "files": [
            "app/Helpers/helpers.php"
        ]
    }
}
```

**Reducción esperada**: -200 líneas

---

### ✅ Checklist Fase 2

- [ ] Middleware `EnsureActiveBusiness` creado y testeado
- [ ] BusinessResolver service implementado
- [ ] Middleware aplicado en todos los controladores (123 lugares)
- [ ] Tests: ✓ Smoke tests pasando
- [ ] Trait `JsonResponses` creado
- [ ] Trait aplicado en 8 controladores principales
- [ ] Servicios Firebase consolidados (3 archivos → 1 carpeta)
- [ ] FirebaseNotificationService.php eliminado
- [ ] Tests de notificaciones pasando
- [ ] Helpers globales creados y aplicados
- [ ] `composer dump-autoload` ejecutado
- [ ] **Reducción total: -3,300 líneas** ✅

**Tiempo estimado**: 3-4 días
**Reducción esperada**: 18.7% del código total

---

## FASE 3: REFACTORIZACIÓN CONTROLADORES (ALTO IMPACTO)
**Duración**: 1-2 semanas
**Riesgo**: 🟡 MEDIO
**Prioridad**: 🟠 MEDIA

### Objetivos
- Dividir controladores gigantes (>1000 líneas)
- Extraer lógica de negocio a Actions
- Mantener funcionalidad 100% intacta

---

### 3.1 Refactorizar WaiterCallController (2,693 → 300 líneas)
**Impacto**: -89% del archivo más grande
**Riesgo**: Medio (endpoint crítico)
**Tiempo**: 3-4 días

#### Estructura Actual
```
WaiterCallController.php (2,693 líneas)
├── callWaiter() - 191 líneas
├── acknowledgeCall() - 42 líneas
├── completeCall() - 45 líneas
├── getPendingCalls() - 35 líneas
├── getCallHistory() - 72 líneas
├── silenceTable() - 61 líneas
├── unsilenceTable() - 28 líneas
├── activateTable() - 71 líneas
├── deactivateTable() - 35 líneas
├── ... 40 métodos más
└── Métodos privados de ayuda
```

#### Estructura Propuesta
```
app/Http/Controllers/Api/
├── WaiterCallController.php (300 líneas) ← CRUD básico
│   ├── index() - Lista llamadas
│   ├── store() - Crear llamada
│   ├── show() - Ver llamada
│   ├── update() - Actualizar
│   └── destroy() - Eliminar
│
├── WaiterCall/
│   ├── CallStatusController.php (150 líneas)
│   │   ├── acknowledge() - Aceptar llamada
│   │   ├── complete() - Completar llamada
│   │   └── cancel() - Cancelar llamada
│   │
│   ├── TableManagementController.php (200 líneas)
│   │   ├── silence() - Silenciar mesa
│   │   ├── unsilence() - Activar mesa
│   │   ├── activate() - Activar mesa
│   │   └── deactivate() - Desactivar mesa
│   │
│   └── CallHistoryController.php (150 líneas)
│       ├── history() - Historial
│       ├── statistics() - Estadísticas
│       └── dashboard() - Dashboard mozo
│
└── Actions/WaiterCall/
    ├── CreateCallAction.php (80 líneas)
    ├── AcknowledgeCallAction.php (60 líneas)
    ├── CompleteCallAction.php (70 líneas)
    ├── SilenceTableAction.php (50 líneas)
    └── ActivateTableAction.php (60 líneas)
```

#### Ejemplo de Extracción: CreateCallAction

**ANTES (en controlador):**
```php
public function callWaiter(Request $request, Table $table): JsonResponse
{
    // 191 líneas de lógica
    try {
        // Validar IP bloqueada
        $clientIp = $request->ip();
        // ... 20 líneas

        // Validar mesa silenciada
        $activeSilence = TableSilence::where(...);
        // ... 15 líneas

        // Validar spam
        $recentCalls = WaiterCall::where(...);
        // ... 25 líneas

        // Crear llamada
        $call = WaiterCall::create([...]);
        // ... 10 líneas

        // Enviar notificación
        dispatch(new ProcessWaiterCallNotification($call));
        // ... 5 líneas

        return response()->json([...]);
    } catch (\Exception $e) {
        // ... 15 líneas
    }
}
```

**DESPUÉS (con Action):**

```php
// WaiterCallController.php
public function store(Request $request, Table $table): JsonResponse
{
    try {
        $call = app(CreateCallAction::class)
            ->execute($table, $request->validated());

        return $this->success($call, 'Mozo llamado exitosamente');
    } catch (ValidationException $e) {
        return $this->error($e->getMessage(), 400);
    }
}
```

```php
// app/Actions/WaiterCall/CreateCallAction.php
class CreateCallAction
{
    public function __construct(
        private IpBlockService $ipBlockService,
        private TableSilenceService $silenceService,
        private SpamProtectionService $spamService,
        private WaiterCallNotificationService $notificationService
    ) {}

    public function execute(Table $table, array $data): WaiterCall
    {
        // Validaciones delegadas a servicios
        $this->validateNotBlocked($table, request()->ip());
        $this->validateNotSilenced($table);
        $this->validateNotSpam($table);

        // Crear llamada
        $call = WaiterCall::create([
            'table_id' => $table->id,
            'waiter_id' => $table->active_waiter_id,
            'status' => 'pending',
            'message' => $data['message'] ?? "Llamada desde mesa {$table->number}",
            'called_at' => now(),
            'metadata' => $this->buildMetadata($data)
        ]);

        // Notificar asíncronamente
        dispatch(new ProcessWaiterCallNotification($call));

        return $call;
    }

    private function validateNotBlocked(Table $table, string $ip): void
    {
        if ($this->ipBlockService->isBlocked($ip, $table->business_id)) {
            throw new ValidationException('IP bloqueada');
        }
    }

    // ... métodos privados auxiliares
}
```

#### Plan de Migración WaiterCallController

```
DÍA 1: Preparación
├── Crear estructura de carpetas
├── Crear Actions vacíos (esqueletos)
├── Crear tests para cada Action
└── Verificar baseline tests

DÍA 2: Extraer Actions Críticos
├── CreateCallAction (4h)
│   ├── Extraer lógica
│   ├── Crear servicios auxiliares
│   └── Tests
├── AcknowledgeCallAction (2h)
└── CompleteCallAction (2h)

DÍA 3: Dividir Controlador
├── Crear CallStatusController (3h)
├── Crear TableManagementController (3h)
├── Actualizar routes (1h)
└── Tests de integración

DÍA 4: Migrar Resto de Métodos
├── Mover métodos a nuevos controladores (4h)
├── Actualizar references (2h)
└── Tests finales (2h)

DÍA 5: Limpieza y Verificación
├── Eliminar código muerto (1h)
├── Ejecutar suite completa de tests (1h)
├── Testing manual de endpoints críticos (2h)
├── Code review (2h)
└── Merge a rama principal
```

#### Tests Críticos Post-Refactor
```php
// tests/Feature/WaiterCall/CreateCallTest.php
✓ test_call_waiter_creates_notification_after_refactor
✓ test_blocked_ip_cannot_call_waiter
✓ test_silenced_table_rejects_calls
✓ test_spam_protection_works

// tests/Feature/WaiterCall/CallStatusTest.php
✓ test_acknowledge_call_updates_status
✓ test_complete_call_removes_from_firebase
✓ test_cannot_acknowledge_others_call

// tests/Feature/WaiterCall/TableManagementTest.php
✓ test_silence_table_blocks_calls
✓ test_activate_table_enables_notifications
```

**Reducción esperada**: 2,693 → 800 líneas total (-70%)

---

### 3.2 Refactorizar AdminController (2,027 → 600 líneas)
**Impacto**: -70% del segundo archivo más grande
**Riesgo**: Medio
**Tiempo**: 2-3 días

#### Estructura Propuesta
```
app/Http/Controllers/Admin/
├── BusinessController.php (200 líneas)
│   ├── index() - Listar negocios
│   ├── store() - Crear negocio
│   ├── update() - Actualizar
│   └── destroy() - Eliminar
│
├── StaffController.php (300 líneas)
│   ├── index() - Listar staff
│   ├── store() - Crear staff
│   ├── destroy() - Remover staff
│   └── invite() - Invitar staff
│
├── StaffRequestController.php (150 líneas)
│   ├── index() - Listar solicitudes
│   ├── approve() - Aprobar
│   ├── reject() - Rechazar
│   └── show() - Ver detalle
│
└── BusinessConfigController.php (100 líneas)
    ├── show() - Ver config
    ├── update() - Actualizar config
    └── reset() - Resetear config

Actions/Admin/
├── RemoveStaffAction.php (100 líneas)
│   ├── Lógica de eliminación
│   ├── Limpieza de Firebase
│   └── Notificaciones
│
├── ApproveStaffRequestAction.php (80 líneas)
└── InviteStaffAction.php (60 líneas)
```

#### Migración Similar a WaiterCallController
```
DÍA 1: Crear estructura + tests
DÍA 2: Extraer Actions
DÍA 3: Dividir controlador en 4 archivos
DÍA 4: Migrar routes y referencias
DÍA 5: Tests y verificación
```

**Reducción esperada**: 2,027 → 750 líneas (-63%)

---

### 3.3 Refactorizar User.php (358 → 150 líneas)
**Impacto**: Modelo limpio, lógica en servicios
**Riesgo**: Bajo
**Tiempo**: 1 día

#### Problema: Fat Model
```php
// User.php contiene:
- 15 relaciones
- 8 métodos de lógica de negocio
- 5 métodos de cálculo (membership, subscriptions)
- 10 accessors/mutators
```

#### Solución: Extraer a Servicios
```
ANTES:
app/Models/User.php (358 líneas)

DESPUÉS:
app/Models/User.php (150 líneas) ← Solo relaciones + atributos
app/Services/User/
├── MembershipService.php (100 líneas)
│   ├── hasActiveMembership()
│   ├── membershipDaysRemaining()
│   └── renewMembership()
├── UserProfileService.php (80 líneas)
│   ├── getActiveProfile()
│   ├── switchProfile()
│   └── updateProfile()
└── UserBusinessService.php (60 líneas)
    ├── getActiveBusinessId()
    ├── switchBusiness()
    └── hasAccessToBusiness()
```

#### Migración User.php
```php
// ANTES (en modelo)
public function hasActiveMembership(): bool
{
    return $this->memberships()
        ->where('status', 'active')
        ->where('expires_at', '>', now())
        ->exists();
}

// DESPUÉS (en servicio)
class MembershipService
{
    public function hasActiveMembership(User $user): bool
    {
        return $user->memberships()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }
}

// Uso en controlador
app(MembershipService::class)->hasActiveMembership($user);
```

**Reducción esperada**: 358 → 150 líneas (-58%)

---

### ✅ Checklist Fase 3

- [ ] WaiterCallController dividido en 4 archivos (2,693 → 800 líneas)
- [ ] Actions de WaiterCall creados (5 archivos)
- [ ] Tests de WaiterCall pasando (20+ tests)
- [ ] AdminController dividido en 4 archivos (2,027 → 750 líneas)
- [ ] Actions de Admin creados (3 archivos)
- [ ] User.php refactorizado (358 → 150 líneas)
- [ ] MembershipService creado
- [ ] Routes actualizadas
- [ ] **Smoke tests pasando** ✅
- [ ] **Reducción total: -3,578 líneas** ✅

**Tiempo estimado**: 1-2 semanas
**Reducción esperada**: 20.3% del código total

---

## FASE 4: OPTIMIZACIÓN (OPCIONAL)
**Duración**: 1 semana
**Riesgo**: 🟢 BAJO
**Prioridad**: 🔵 BAJA (puede posponerse)

### 4.1 Consolidar Migraciones (50 → 15)
**Impacto**: Simplifica DB setup
**Riesgo**: Ninguno (solo afecta instancias nuevas)
**Tiempo**: 2 días

#### Usar Laravel Migrations Squashing
```bash
# Consolidar migraciones antiguas
php artisan schema:dump

# Esto crea:
database/schema/mysql-schema.sql

# Permite eliminar migraciones viejas manualmente
```

**Reducción esperada**: 50 → ~15 archivos de migración

---

### 4.2 Implementar Repository Pattern (Opcional)
**Impacto**: Queries centralizados
**Riesgo**: Bajo
**Tiempo**: 2-3 días

#### Solo si te interesa mayor abstracción
```
app/Repositories/
├── WaiterCallRepository.php
├── StaffRepository.php
└── BusinessRepository.php
```

**Nota**: Esto es opcional, solo si quieres llevar arquitectura al siguiente nivel.

---

### ✅ Checklist Fase 4

- [ ] Migraciones consolidadas (50 → 15)
- [ ] Schema dump creado
- [ ] Repository pattern implementado (opcional)
- [ ] Documentation actualizada

**Tiempo estimado**: 1 semana
**Reducción esperada**: 35 archivos de migración

---

## 📋 PLAN DE TESTING Y VERIFICACIÓN

### Estrategia de Testing en Cada Fase

#### Nivel 1: Tests Automáticos (OBLIGATORIO)
```bash
# Ejecutar ANTES de cada commit
php artisan test --testsuite=Smoke

# Verificar 0 fallos
```

#### Nivel 2: Tests de Integración (OBLIGATORIO)
```bash
# Ejecutar DESPUÉS de cada refactor
php artisan test --testsuite=Feature

# Tests críticos:
- WaiterCallTest (20 tests)
- StaffManagementTest (15 tests)
- NotificationTest (12 tests)
- AdminTest (18 tests)
```

#### Nivel 3: Testing Manual (RECOMENDADO)
```
Checklist manual por fase:

FASE 2 (Quick Wins):
- [ ] Login como admin
- [ ] Crear solicitud de staff
- [ ] Aprobar solicitud
- [ ] Login como mozo
- [ ] Ver llamadas pendientes
- [ ] Llamar desde QR
- [ ] Aceptar llamada
- [ ] Completar llamada

FASE 3 (Refactorización):
- [ ] Todos los pasos de FASE 2
- [ ] Dashboard de admin carga correctamente
- [ ] Dashboard de mozo carga correctamente
- [ ] Notificaciones se envían
- [ ] Firebase RTDB se actualiza
```

---

## 🔄 ROLLBACK STRATEGY

### En Caso de Problemas

#### Nivel 1: Revertir Último Commit
```bash
# Si un commit rompe algo:
git revert HEAD
git push
```

#### Nivel 2: Revertir Fase Completa
```bash
# Si toda la fase tiene problemas:
git reset --hard <commit-antes-de-fase>
git push --force origin refactor/phase-X
```

#### Nivel 3: Rollback Total
```bash
# Si todo falla, volver al tag inicial:
git reset --hard v1.0-pre-refactor
git push --force origin main

# Restaurar desde backup
cp -r backups/pre_refactor_2025_01_04/app/* app/
```

### Red Flags (Detener Refactorización)
```
🚨 DETENER SI:
- Más de 5 tests fallan simultáneamente
- Endpoint crítico devuelve 500
- Firebase deja de enviar notificaciones
- Base de datos se corrompe
- Performance cae >50%

✅ CONTINUAR SI:
- 1-2 tests fallan (fix y continuar)
- Warnings (no errors)
- Performance similar o mejor
```

---

## ✅ CHECKLIST DE APROBACIÓN FINAL

### Antes de Merge a Main

- [ ] **Todos los tests pasan** (100% success rate)
- [ ] **0 errores en logs** de producción (después de deploy)
- [ ] **Performance igual o mejor** (benchmarks)
- [ ] **Smoke tests manuales completados** (checklist arriba)
- [ ] **Code review aprobado** por otro developer (si aplica)
- [ ] **Documentación actualizada** (API_NOTIFICACIONES.md, CLAUDE.md)
- [ ] **Changelog creado** con lista de cambios
- [ ] **Tag de versión creado** (v2.0-refactored)

### Métricas de Éxito

| Métrica | Antes | Después | ✅ |
|---------|-------|---------|-----|
| Líneas controladores | 17,664 | ~8,000 | ✓ |
| Líneas servicios | 5,643 | ~2,500 | ✓ |
| Archivos >1000 líneas | 5 | 0 | ✓ |
| Código duplicado | 123 patrones | 0 | ✓ |
| Tests automatizados | 0 | 65+ | ✓ |
| Coverage | 0% | >60% | ✓ |

---

## 📊 RESUMEN EJECUTIVO

### Reducción Total Esperada

```
FASE 1 (Preparación):           0 líneas     (0%)
FASE 2 (Quick Wins):       -3,300 líneas   (-18.7%)
FASE 3 (Refactorización):  -3,578 líneas   (-20.3%)
FASE 4 (Optimización):        -35 archivos
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TOTAL:                     -6,878 líneas   (-39%)
```

### Timeline

```
┌────────────────────────────────────────────────────────────┐
│  SEMANA 1       │  SEMANA 2       │  SEMANA 3-4           │
│  FASE 1 + 2     │  FASE 3         │  FASE 4 (Opcional)    │
│  (Quick Wins)   │  (Refactoring)  │  (Optimization)       │
├────────────────────────────────────────────────────────────│
│  Día 1-3: Prep  │  Día 8-10:      │  Día 15-21:           │
│  Día 4-7: Quick │  WaiterCall     │  Migraciones          │
│         Wins    │  Día 11-14:     │  Repository pattern   │
│                 │  AdminCtrl      │  (Opcional)           │
└────────────────────────────────────────────────────────────┘
         ↓               ↓                   ↓
    Tests OK        Tests OK            Tests OK
```

### Nivel de Confianza

```
FASE 1: 🟢🟢🟢🟢🟢 100% (Solo preparación)
FASE 2: 🟢🟢🟢🟢⚪  90% (Cambios simples)
FASE 3: 🟢🟢🟢⚪⚪  75% (Cambios complejos)
FASE 4: 🟢🟢🟢🟢⚪  85% (Opcional, bajo riesgo)
```

---

## 🎯 DECISIÓN REQUERIDA

### Opciones

**Opción A: Refactorización Completa** (Recomendada)
- Ejecutar FASE 1 + 2 + 3
- Duración: 2-3 semanas
- Reducción: -39% del código
- Riesgo: Medio, mitigado con tests

**Opción B: Quick Wins Solo**
- Ejecutar FASE 1 + 2 únicamente
- Duración: 1 semana
- Reducción: -18.7% del código
- Riesgo: Bajo

**Opción C: Personalizado**
- Elegir fases específicas
- Tú decides el alcance

---

## 📝 PRÓXIMOS PASOS

1. **Revisar este plan** y aprobar/modificar
2. **Decidir alcance**: Opción A, B, o C
3. **Confirmar timeline**: ¿Cuándo empezar?
4. **Iniciar FASE 1**: Crear tests y backup

---

**¿Apruebas este plan? ¿Alguna modificación?**

Responde con:
- ✅ "Aprobado - Opción A/B/C"
- ✏️ "Modificar: [cambios solicitados]"
- ❓ "Preguntas: [dudas específicas]"
