# 📱 SISTEMA DE NOTIFICACIONES - MOZO APP
## Documentación Completa y Guía de Mantenimiento

**Fecha**: 2025-11-04
**Versión**: 2.0 (Rediseño completo)
**Autor**: Arquitectura Modular V2

---

## ⚡ ARQUITECTURA V2 - REDISEÑO COMPLETO

### 🎯 Principios de Diseño

Esta versión fue rediseñada desde cero eliminando:
- ❌ Código duplicado en múltiples servicios
- ❌ Métodos legacy sin uso
- ❌ Lógica mezclada en archivos gigantes
- ❌ Dependencias circulares y acoplamiento

Aplicando:
- ✅ **Separación de Responsabilidades** (Single Responsibility Principle)
- ✅ **Composición sobre herencia** (DI y agregación)
- ✅ **Modularidad** (cada clase tiene un propósito único)
- ✅ **Reutilización** (DRY - Don't Repeat Yourself)
- ✅ **Testabilidad** (inyección de dependencias)

---

### 🏗️ Arquitectura Simplificada (KISS + SOLID)

```
┌─────────────────────────────────────────────────────────┐
│              CAPA DE PRESENTACIÓN                       │
│  Controllers + Jobs                                     │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│            CAPA DE SERVICIOS (4 servicios)              │
│                                                          │
│  ┌────────────────────────────────────────────────┐    │
│  │  FirebaseNotificationService (Base común)      │    │
│  │  - sendToUser() - sendBatch()                  │    │
│  │  - writeToPath() - deleteFromPath()            │    │
│  │  - getValidAccessToken() [auto-refresh]        │    │
│  └───────────┬──────────────────────────┬─────────┘    │
│              │                          │               │
│  ┌───────────▼────────────┐  ┌─────────▼─────────────┐ │
│  │ WaiterCallNotification│  │  StaffNotification    │ │
│  │ Service (Mesas)       │  │  Service (Staff)      │ │
│  │ - processNewCall()    │  │ - processStaffEvent() │ │
│  │ - processAcknowledged │  │ - notifyAdmins()      │ │
│  │ - processCompleted()  │  │ - sendInvitation()    │ │
│  └────────────┬──────────┘  └────────────┬──────────┘ │
│               │                           │             │
│               └──────────┬────────────────┘             │
│                          │                              │
│               ┌──────────▼──────────┐                   │
│               │   TokenManager      │                   │
│               │ - getUserTokens()   │                   │
│               │ - groupByPlatform() │                   │
│               │ - cleanExpired()    │                   │
│               └─────────────────────┘                   │
└─────────────────────────────────────────────────────────┘
                          │
                ┌─────────▼──────────┐
                │  FCM API + Firebase│
                │  Realtime Database │
                └────────────────────┘
```

---

### 📦 Los 4 Servicios V2 - Detalle

#### 1. **TokenManager.php** `app/Services/TokenManager.php`
**Responsabilidad**: Gestión centralizada de tokens FCM (~150 líneas)

```php
class TokenManager
{
    // Obtención de tokens
    public function getUserTokens(int $userId, ?string $platform = null): array
    public function getBusinessAdminTokens(int $businessId): array

    // Filtrado y agrupación
    public function filterExpiredTokens(array $tokens): array
    public function groupByPlatform(array $tokens): array

    // Mantenimiento
    public function refreshToken(int $userId, string $token, string $platform): bool
    public function cleanExpiredTokens(): int  // Para comando cron
}
```

**Elimina duplicación de**:
- ❌ `getUserTokens()` duplicado en múltiples servicios
- ❌ `getBusinessAdminTokens()` duplicado
- ❌ Lógica de separación de tokens por plataforma (3 lugares)

---

#### 2. **FirebaseNotificationService.php** `app/Services/FirebaseNotificationService.php`
**Responsabilidad**: Base común para FCM + Firebase RTDB (~250 líneas)

```php
class FirebaseNotificationService
{
    // FCM - Envío de notificaciones
    public function sendToUser(int $userId, string $title, string $body, array $data, string $priority): array
    public function sendToMultipleUsers(array $userIds, string $title, string $body, array $data): array
    public function sendBatch(array $tokens, string $title, string $body, array $data): array  // PARALELO

    // Firebase Realtime Database - Operaciones comunes
    public function writeToPath(string $path, array $data): bool
    public function deleteFromPath(string $path): bool
    public function readFromPath(string $path): ?array

    // Helpers privados
    private function getValidAccessToken(): string  // Auto-refresh cada 50 min
    private function formatDataForFcm(array $data): array
    private function buildMessagePayload(string $token, ...): array
    private function sendMessage(array $message): array
    private function handleFcmError(RequestException $e, string $token): void  // Limpia tokens 404/410
}
```

**Mejoras vs V1**:
- ✅ Access token se auto-renueva (resuelve problema crítico #1)
- ✅ Batch processing con Guzzle Pool (paralelo real)
- ✅ Manejo automático de tokens inválidos
- ✅ Sin código duplicado (writeToPath/deleteFromPath centralizados)

---

#### 3. **WaiterCallNotificationService.php** `app/Services/WaiterCallNotificationService.php`
**Responsabilidad**: Notificaciones de llamadas de mesa (~200 líneas)

```php
class WaiterCallNotificationService
{
    public function __construct(
        private FirebaseNotificationService $firebase,
        private TokenManager $tokenManager
    ) {}

    // API pública - Eventos de llamadas
    public function processNewCall(WaiterCall $call): bool
    public function processAcknowledgedCall(WaiterCall $call): bool
    public function processCompletedCall(WaiterCall $call): bool

    // Métodos privados especializados
    private function writeCallToFirebase(WaiterCall $call, string $event): void
    private function updateWaiterIndex(WaiterCall $call): void
    private function updateTableIndex(WaiterCall $call): void
    private function updateBusinessIndex(WaiterCall $call): void
    private function sendNotificationToWaiter(WaiterCall $call): void
}
```

**Reemplaza completamente**:
- ❌ UnifiedFirebaseService.php

---

#### 4. **StaffNotificationService.php** `app/Services/StaffNotificationService.php`
**Responsabilidad**: Notificaciones de solicitudes de staff (~200 líneas)

```php
class StaffNotificationService
{
    public function __construct(
        private FirebaseNotificationService $firebase,
        private TokenManager $tokenManager
    ) {}

    // API pública - Eventos de staff
    public function processStaffEvent(Staff $staff, string $event): bool

    // Métodos privados por evento (en vez de 1 método gigante de 233 líneas)
    private function handleCreatedEvent(Staff $staff): void       // Notificar admins
    private function handleConfirmedEvent(Staff $staff): void     // Notificar staff + admins
    private function handleRejectedEvent(Staff $staff): void      // Notificar staff
    private function handleInvitedEvent(Staff $staff): void       // Enviar invitación

    // Firebase RTDB
    private function writeStaffToFirebase(Staff $staff, string $event): void
    private function updateBusinessStaffIndex(Staff $staff): void
    private function updateUserStaffIndex(Staff $staff): void

    // Comunicaciones externas (email/WhatsApp)
    private function sendInvitationEmail(Staff $staff): void
    private function sendInvitationWhatsApp(Staff $staff): void
}
```

**Mejoras vs V1**:
- ✅ Método de 233 líneas dividido en 4 métodos pequeños
- ✅ Usa match() para limpieza
- ✅ Sin duplicación de lógica de notificaciones

---

### 🔄 Flujo de Notificación V2 (Simplificado)

#### Ejemplo: Mesa llama al mozo

```php
// 1. Controller recibe solicitud
WaiterCallController::store()
    ↓
// 2. Crea registro en DB
$call = WaiterCall::create([
    'table_id' => $request->table_id,
    'waiter_id' => $table->waiter_id,
    'status' => 'pending'
]);
    ↓
// 3. Dispara job asíncrono
ProcessWaiterCallNotification::dispatch($call)->onQueue('high-priority');
    ↓
// 4. Job ejecuta WaiterCallNotificationService
app(WaiterCallNotificationService::class)->processNewCall($call);
    ↓
// 5. WaiterCallNotificationService orquesta todo
WaiterCallNotificationService {

    // 5.1. Escribe en Firebase RTDB
    $this->firebase->writeToPath("active_calls/{$call->id}", $callData);

    // 5.2. Actualiza índices (en paralelo usando FirebaseNotificationService)
    $this->updateWaiterIndex($call);   // waiters/{waiter_id}/calls/{call_id}
    $this->updateTableIndex($call);    // tables/{table_id}/current_call
    $this->updateBusinessIndex($call); // businesses/{business_id}/summary

    // 5.3. Envía notificaciones FCM
    $this->sendNotificationToWaiter($call) {

        // Obtiene tokens válidos del mozo usando TokenManager
        $tokens = $this->tokenManager->getUserTokens($call->waiter_id);
        $validTokens = $this->tokenManager->filterExpiredTokens($tokens);
        $grouped = $this->tokenManager->groupByPlatform($validTokens);

        // Usa FirebaseNotificationService para enviar
        // Web: data-only (service worker)
        $this->firebase->sendToUser(
            $call->waiter_id,
            "Mesa {$call->table->number}",
            "Nueva llamada",
            ['type' => 'waiter_call', 'call_id' => $call->id],
            'high'
        );
    }
}
```

---

### ✨ Beneficios del Rediseño

| Aspecto | Antes (V1) | Después (V2) |
|---------|-----------|--------------|
| **Archivos de servicios** | 3 archivos (FirebaseService, UnifiedFirebaseService, StaffNotificationService) | **4 servicios modulares** |
| **Líneas de código promedio** | ~600 líneas/archivo | **~200 líneas/archivo** |
| **Código duplicado** | 8 duplicaciones críticas | **0 duplicaciones** |
| **Métodos legacy** | 4 métodos sin uso | **0 métodos legacy** |
| **Testabilidad** | Baja (alta dependencia) | **Alta (DI completa)** |
| **Mantenibilidad** | Baja (código mezclado) | **Alta (SRP aplicado)** |
| **Performance** | Secuencial (lento) | **Paralelo con Guzzle Pool** |
| **Access Token** | Expira en 1 hora (falla) | **Auto-refresh cada 50min** |
| **Batch Processing** | 10 requests secuenciales | **10 requests en paralelo** |

---

### 📁 Estructura de Archivos V2 (Simplificada - KISS)

```
app/Services/
├── TokenManager.php                      [~150 líneas - Gestión tokens]
├── FirebaseNotificationService.php       [~250 líneas - FCM + RTDB base]
├── WaiterCallNotificationService.php     [~200 líneas - Llamadas mesa]
└── StaffNotificationService.php          [~200 líneas - Solicitudes staff]

app/Console/Commands/
└── CleanExpiredTokens.php                [Limpieza diaria tokens]

app/Jobs/
└── ProcessWaiterCallNotification.php     [Job simplificado]

app/Http/Controllers/
├── FcmTokenController.php                [Usa TokenManager]
└── NotificationController.php            [Envío manual]
```

**Total: 4 servicios, ~800 líneas limpias y modulares**

---

### 🎯 Plan de Implementación

#### Fase 1: Core (TokenManager + FirebaseNotificationService)
1. ✅ Implementar `TokenManager.php`
   - getUserTokens(), getBusinessAdminTokens()
   - filterExpiredTokens(), groupByPlatform()
   - refreshToken(), cleanExpiredTokens()

2. ✅ Implementar `FirebaseNotificationService.php`
   - **Fix Crítico**: getValidAccessToken() con auto-refresh
   - **Fix Crítico**: sendBatch() con Guzzle Pool (paralelo)
   - **Fix Crítico**: handleFcmError() elimina tokens 404/410
   - writeToPath(), deleteFromPath() (Firebase RTDB)
   - formatDataForFcm(), buildMessagePayload()

#### Fase 2: Servicios Especializados
3. ✅ Implementar `WaiterCallNotificationService.php`
   - processNewCall(), processAcknowledgedCall(), processCompletedCall()
   - updateWaiterIndex(), updateTableIndex(), updateBusinessIndex()

4. ✅ Refactorizar `StaffNotificationService.php`
   - Dividir método gigante de 233 líneas en 4 métodos
   - handleCreatedEvent(), handleConfirmedEvent(), etc.

#### Fase 3: Mantenimiento
5. ✅ Crear comando `CleanExpiredTokens.php` + schedule diario
6. ✅ Actualizar `ProcessWaiterCallNotification.php` para usar nuevo servicio
7. ✅ Actualizar `FcmTokenController.php` para usar TokenManager

#### Fase 4: Testing
8. ✅ Probar flujo completo de notificaciones
9. ✅ Verificar access token auto-refresh
10. ✅ Verificar batch paralelo con múltiples tokens

---

## 📋 TABLA DE CONTENIDOS (V1 - Legacy)

1. [Arquitectura General](#arquitectura-general)
2. [Componentes del Sistema](#componentes-del-sistema)
3. [Problemas Identificados](#problemas-identificados)
4. [Medidas Preventivas](#medidas-preventivas)
5. [Plan de Acción](#plan-de-acción)
6. [Checklist de Mantenimiento](#checklist-de-mantenimiento)
7. [Guía de Troubleshooting](#guía-de-troubleshooting)

---

## 🏗️ ARQUITECTURA GENERAL

### Flujo de Notificaciones

```
┌─────────────────┐
│   Cliente QR    │
│  (Mesa llama)   │
└────────┬────────┘
         │
         v
┌─────────────────────────────────┐
│  WaiterCallController::store()  │
│  Crea WaiterCall en DB          │
└────────┬────────────────────────┘
         │
         v
┌──────────────────────────────────┐
│ ProcessWaiterCallNotification    │
│ Job (Queue: high-priority)       │
└────────┬─────────────────────────┘
         │
         v
┌─────────────────────────────────────────┐
│  UnifiedFirebaseService::writeCall()    │
│  1. Escribe en Firebase Realtime DB     │
│  2. Actualiza índices (mozo/mesa/biz)   │
└────────┬────────────────────────────────┘
         │
         v
┌─────────────────────────────────────────┐
│  UnifiedFirebaseService::                │
│  sendUnifiedFcmEvent()                   │
│  Envía notificación FCM                  │
└────────┬────────────────────────────────┘
         │
         v
┌─────────────────────────────────────────┐
│  FirebaseService::                       │
│  sendUnifiedNotificationToTokens()       │
│  - Separa tokens por plataforma          │
│  - Web: data-only                        │
│  - Mobile: notification + data           │
└────────┬────────────────────────────────┘
         │
         v
┌──────────────────────────────────────────┐
│         FCM API (Firebase)               │
│  Envía push notification a dispositivos  │
└────────┬─────────────────────────────────┘
         │
         v
┌──────────────────────────────────────────┐
│  Dispositivos Mozos                      │
│  - Web: Service Worker                   │
│  - Android: Channel waiter_urgent        │
│  - iOS: APNS                             │
└──────────────────────────────────────────┘
```

---

## 🔧 COMPONENTES DEL SISTEMA

### 1. Backend Services

#### **FirebaseService.php** `app/Services/FirebaseService.php`
**Responsabilidad**: Envío de notificaciones FCM usando HTTP v1 API

**Métodos Clave**:
- `sendToDevice($token, $title, $body, $data, $priority)` - Enviar a un token
- `sendToUser($userId, $title, $body, $data, $priority)` - Enviar a usuario por ID
- `sendUnifiedNotificationToTokens($tokens, $tableNumber, $message, $data)` - Envío unificado
- `cancelNotification($userId, $notificationId)` - Cancelar notificación Android
- `refreshUserToken($userId, $newToken, $platform)` - Actualizar token

**Problemas Conocidos**:
- ⚠️ Access token expira en 1 hora sin refresh automático
- ⚠️ `sendToMultipleDevices()` es secuencial (no batch)
- ⚠️ No valida tokens antes de enviar

---

#### **UnifiedFirebaseService.php** `app/Services/UnifiedFirebaseService.php`
**Responsabilidad**: Sincronización con Firebase Realtime Database

**Métodos Clave**:
- `writeCall($call, $eventType)` - Escribir llamada + enviar FCM
- `removeCall($call)` - Eliminar llamada completada
- `updateWaiterIndex($call)` - Actualizar índice de mozo
- `updateTableIndex($call)` - Actualizar índice de mesa
- `updateBusinessIndex($call)` - Actualizar índice de negocio
- `sendUnifiedFcmEvent($call, $eventType)` - Enviar notificación FCM
- `deleteBusinessData($businessId)` - Limpiar datos de negocio

**Problemas Conocidos**:
- ⚠️ `executeParallel()` NO ejecuta en paralelo (es secuencial)
- ⚠️ No maneja race conditions en updates concurrentes
- ⚠️ Cache puede estar desactualizado

---

#### **StaffNotificationService.php** `app/Services/StaffNotificationService.php`
**Responsabilidad**: Notificaciones de solicitudes de staff

**Métodos Clave**:
- `writeStaffRequest($staff, $eventType)` - Escribir solicitud + FCM
- `sendStaffNotification($staff, $eventType)` - Enviar notificación
- `sendInvitationNotification($staff)` - Enviar invitación por email
- `getBusinessAdminTokens($businessId)` - Obtener tokens de admins
- `markStaffUnlinked($staff)` - Marcar staff desvinculado

**Eventos Soportados**: `created`, `confirmed`, `rejected`, `invited`, `unlinked`

---

### 2. Models

#### **DeviceToken.php** `app/Models/DeviceToken.php`
**Campos**:
- `user_id` - ID del usuario
- `token` - Token FCM
- `platform` - android | ios | web
- `channel` - Canal de notificación
- `device_type` - Tipo de dispositivo
- `device_name` - Nombre del dispositivo
- `last_used_at` - Última vez usado
- `expires_at` - Fecha de expiración (60 días)

**Relaciones**:
- `belongsTo(User::class)`

**Problemas Conocidos**:
- ⚠️ No hay limpieza automática de tokens expirados
- ⚠️ No hay índices optimizados para queries frecuentes
- ⚠️ `refreshUserToken()` elimina TODOS los tokens de la plataforma

---

### 3. Controllers

#### **FcmTokenController.php** `app/Http/Controllers/FcmTokenController.php`
**Endpoints**:
- `POST /fcm/register-token` - Registrar token
- `POST /fcm/refresh-token` - Refrescar token
- `GET /fcm/token-status` - Estado de tokens
- `POST /fcm/test` - Test de notificación
- `DELETE /fcm/delete-token` - Eliminar token

**Restricciones**: Solo mozos pueden registrar tokens

---

#### **NotificationController.php** `app/Http/Controllers/NotificationController.php`
**Endpoints**:
- `GET /user/notifications` - Notificaciones del usuario
- `POST /user/notifications/{id}/read` - Marcar como leída
- `POST /admin/notifications/send-to-all` - Broadcast
- `POST /admin/notifications/send-to-user` - Enviar a usuario
- `POST /admin/notifications/send-to-device` - Enviar a dispositivo
- `POST /admin/notifications/send-to-topic` - Enviar a topic

---

### 4. Jobs

#### **ProcessWaiterCallNotification.php** `app/Jobs/ProcessWaiterCallNotification.php`
**Configuración**:
- Queue: `high-priority`
- Timeout: 30s
- Retries: 3
- Max Exceptions: 2

**Flujo**:
1. Cargar relaciones (table, waiter)
2. Validar status = 'pending'
3. Llamar a `UnifiedFirebaseService::writeCall()`
4. Log de éxito/error

**Problemas Conocidos**:
- ⚠️ Si la queue no está corriendo, falla silenciosamente
- ⚠️ No hay fallback si Firebase está caído
- ⚠️ No hay deduplicación

---

### 5. Frontend

#### **firebase-messaging-sw.js** `public/firebase-messaging-sw.js`
**Responsabilidad**: Service Worker para notificaciones Web

**Handlers**:
- `onBackgroundMessage()` - Mensajes en background
- `push` event - Fallback para Web Push API directo
- `notificationclick` - Click en notificación

**Problemas Conocidos**:
- ⚠️ Credenciales Firebase hardcodeadas (seguridad)
- ⚠️ `requireInteraction: true` puede causar problemas
- ⚠️ No maneja errores apropiadamente

---

## 🚨 PROBLEMAS IDENTIFICADOS

### 🔴 CRÍTICOS (Requieren Acción Inmediata)

#### 1. **Access Token de Firebase Expira** (FirebaseService.php:32)
**Severidad**: CRÍTICA
**Impacto**: Todas las notificaciones fallan después de 1 hora

**Problema**:
```php
// Constructor solo genera el token UNA VEZ
public function __construct()
{
    $this->accessToken = $this->getAccessToken(); // Expira en 1 hora
}
```

**Solución**:
```php
// Método para obtener/refrescar token automáticamente
private function getValidAccessToken()
{
    if (!$this->accessToken || $this->tokenExpiresAt < now()) {
        $this->accessToken = $this->getAccessToken();
        $this->tokenExpiresAt = now()->addMinutes(50); // Refresh antes de expirar
    }
    return $this->accessToken;
}

// Usar en sendMessage()
private function sendMessage($message)
{
    $token = $this->getValidAccessToken(); // En lugar de $this->accessToken
    // ...
}
```

---

#### 2. **RefreshUserToken Elimina Múltiples Dispositivos** (FirebaseService.php:517)
**Severidad**: CRÍTICA
**Impacto**: Usuarios con múltiples dispositivos pierden notificaciones

**Problema**:
```php
// Elimina TODOS los tokens de la plataforma
DeviceToken::where('user_id', $userId)
    ->where('platform', $platform)
    ->delete(); // ❌ Borra todos los dispositivos Android del usuario
```

**Solución**:
```php
// Opción 1: Solo eliminar el token específico si existe
DeviceToken::where('user_id', $userId)
    ->where('platform', $platform)
    ->where('token', $newToken)
    ->delete();

// Opción 2: Permitir múltiples tokens por plataforma
DeviceToken::updateOrCreate(
    [
        'user_id' => $userId,
        'token' => $newToken,
        'platform' => $platform
    ],
    ['expires_at' => now()->addDays(60)]
);
```

---

#### 3. **No Hay Limpieza de Tokens Expirados**
**Severidad**: CRÍTICA
**Impacto**: Base de datos crece infinitamente, queries lentos, envíos a tokens inválidos

**Solución**:
Crear comando Artisan que corra diariamente:

```php
// app/Console/Commands/CleanExpiredTokens.php
namespace App\Console\Commands;

use App\Models\DeviceToken;
use Illuminate\Console\Command;

class CleanExpiredTokens extends Command
{
    protected $signature = 'tokens:clean';
    protected $description = 'Eliminar tokens FCM expirados';

    public function handle()
    {
        $deleted = DeviceToken::where('expires_at', '<', now())->delete();
        $this->info("Eliminados {$deleted} tokens expirados");

        // También eliminar tokens de hace más de 90 días sin fecha de expiración
        $oldDeleted = DeviceToken::whereNull('expires_at')
            ->where('created_at', '<', now()->subDays(90))
            ->delete();
        $this->info("Eliminados {$oldDeleted} tokens antiguos sin expiración");
    }
}
```

Agregar a `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('tokens:clean')->daily();
}
```

---

#### 4. **Batch Processing es Secuencial** (FirebaseService.php:210)
**Severidad**: ALTA
**Impacto**: Latencia extrema con muchos usuarios (10 mozos = 10 requests secuenciales)

**Problema**:
```php
foreach ($tokens as $token) {
    $result = $this->sendToDevice($token, $title, $body, $data, $priority);
    // ❌ Espera respuesta antes de enviar siguiente
}
```

**Solución**:
Usar Guzzle Pool para requests paralelos:

```php
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

public function sendToMultipleDevices($tokens, $title, $body, $data = [], $priority = 'normal')
{
    $results = [];
    $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
    $accessToken = $this->getValidAccessToken();

    $requests = function ($tokens) use ($url, $accessToken, $title, $body, $data, $priority) {
        foreach ($tokens as $token) {
            $message = $this->buildMessage($token, $title, $body, $data, $priority);
            yield new Request('POST', $url, [
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ], json_encode($message));
        }
    };

    $pool = new Pool($this->client, $requests($tokens), [
        'concurrency' => 10, // 10 requests paralelos
        'fulfilled' => function ($response, $index) use (&$results, $tokens) {
            $results[] = ['token' => $tokens[$index], 'success' => true];
        },
        'rejected' => function ($reason, $index) use (&$results, $tokens) {
            $results[] = ['token' => $tokens[$index], 'success' => false, 'error' => $reason];
        },
    ]);

    $pool->promise()->wait();
    return $results;
}
```

---

#### 5. **executeParallel() NO es Paralelo** (UnifiedFirebaseService.php:336)
**Severidad**: ALTA
**Impacto**: Latencia al actualizar Firebase Realtime DB

**Problema**:
```php
private function executeParallel(array $promises): array
{
    // Por simplicidad, ejecutamos secuencialmente
    return $promises; // ❌ NO hace nada en paralelo
}
```

**Solución**:
```php
private function executeParallel(array $promises): array
{
    // Usar Guzzle Pool o HTTP::pool()
    return Http::pool(fn ($pool) => $promises);
}
```

---

### 🟡 MEDIAS (Requieren Atención)

#### 6. **Service Worker con Credenciales Hardcodeadas**
**Severidad**: MEDIA (Seguridad)
**Impacto**: Credenciales expuestas públicamente

**Solución**:
Mover credenciales a endpoint dinámico:

```javascript
// public/firebase-messaging-sw.js
importScripts('/js/firebase-config.js'); // Genera dinámicamente desde backend

// routes/api.php
Route::get('/firebase/config', [FirebaseConfigController::class, 'getPublicConfig']);
```

---

#### 7. **No Hay Validación de Tokens Antes de Enviar**
**Severidad**: MEDIA
**Impacto**: Requests fallidos a FCM con tokens inválidos

**Solución**:
Filtrar tokens expirados antes de enviar:

```php
$tokens = DeviceToken::where('user_id', $userId)
    ->where(function($q) {
        $q->where('expires_at', '>', now())
          ->orWhereNull('expires_at');
    })
    ->pluck('token')
    ->toArray();
```

---

#### 8. **No Hay Manejo de Errores de FCM**
**Severidad**: MEDIA
**Impacto**: Tokens inválidos permanecen en DB

**Solución**:
Capturar errores 404/410 de FCM y eliminar tokens:

```php
catch (RequestException $e) {
    if ($e->hasResponse()) {
        $statusCode = $e->getResponse()->getStatusCode();

        // Token inválido o no registrado
        if (in_array($statusCode, [404, 410])) {
            DeviceToken::where('token', $token)->delete();
            Log::info("Token inválido eliminado: {$token}");
        }
    }
    throw $e;
}
```

---

### 🟢 BAJAS (Mejoras Sugeridas)

#### 9. **Job Puede Fallar Silenciosamente**
**Severidad**: BAJA
**Impacto**: Sin queue runner, notificaciones no se envían

**Solución**:
Agregar fallback síncrono si queue está caída:

```php
// WaiterCallController.php
try {
    ProcessWaiterCallNotification::dispatch($call);
} catch (\Exception $e) {
    // Fallback: ejecutar síncronamente
    app(UnifiedFirebaseService::class)->writeCall($call, 'created');
}
```

---

#### 10. **No Hay Deduplicación de Notificaciones**
**Severidad**: BAJA
**Impacto**: Usuarios pueden recibir notificaciones duplicadas

**Solución**:
Usar Redis para tracking de notificaciones enviadas:

```php
$key = "notification_sent:{$userId}:{$callId}";
if (Cache::has($key)) {
    return; // Ya enviada
}
Cache::put($key, true, 60); // 60 segundos
```

---

## 🛡️ MEDIDAS PREVENTIVAS

### 1. **Monitoring y Alertas**

#### Implementar Health Check Endpoint
```php
// routes/api.php
Route::get('/health/notifications', function() {
    $firebaseService = app(\App\Services\FirebaseService::class);

    return [
        'firebase_enabled' => config('services.firebase.enabled'),
        'access_token_valid' => $firebaseService->hasValidToken(),
        'queue_running' => Queue::isRunning(),
        'active_tokens_count' => DeviceToken::where('expires_at', '>', now())->count(),
        'expired_tokens_count' => DeviceToken::where('expires_at', '<', now())->count(),
    ];
});
```

#### Logs Estructurados
Asegurar que todos los errores se logueen con contexto:

```php
Log::error('FCM notification failed', [
    'user_id' => $userId,
    'token_preview' => substr($token, 0, 20),
    'error_code' => $e->getCode(),
    'error_message' => $e->getMessage(),
    'timestamp' => now()->toISOString(),
]);
```

---

### 2. **Pruebas Automatizadas**

#### Test de Envío de Notificaciones
```php
// tests/Feature/NotificationTest.php
public function test_notification_sent_successfully()
{
    $user = User::factory()->create();
    $token = DeviceToken::factory()->create(['user_id' => $user->id]);

    $result = app(FirebaseService::class)
        ->sendToUser($user->id, 'Test', 'Body', [], 'normal');

    $this->assertNotFalse($result);
}

public function test_expired_tokens_are_filtered()
{
    $user = User::factory()->create();
    DeviceToken::factory()->create([
        'user_id' => $user->id,
        'expires_at' => now()->subDays(1) // Expirado
    ]);

    $tokens = DeviceToken::where('user_id', $user->id)
        ->where('expires_at', '>', now())
        ->count();

    $this->assertEquals(0, $tokens);
}
```

---

### 3. **Rate Limiting**

Prevenir spam de notificaciones:

```php
// app/Http/Controllers/NotificationController.php
use Illuminate\Support\Facades\RateLimiter;

public function sendToUser(Request $request)
{
    $key = "send_notification:{$request->user()->id}";

    if (RateLimiter::tooManyAttempts($key, 10)) { // 10 por minuto
        return response()->json([
            'error' => 'Too many notifications sent'
        ], 429);
    }

    RateLimiter::hit($key, 60);

    // ... enviar notificación
}
```

---

### 4. **Database Indices**

Optimizar queries frecuentes:

```php
// database/migrations/xxxx_add_indices_to_device_tokens.php
Schema::table('device_tokens', function (Blueprint $table) {
    $table->index(['user_id', 'platform']);
    $table->index('expires_at');
    $table->index('created_at');
});
```

---

### 5. **Configuración por Ambiente**

```env
# .env.production
FIREBASE_ENABLED=true
FIREBASE_PROJECT_ID=mozoqr-7d32c
FIREBASE_SERVICE_ACCOUNT_PATH=/var/www/storage/firebase/production.json

# .env.staging
FIREBASE_ENABLED=true
FIREBASE_PROJECT_ID=mozoqr-staging
FIREBASE_SERVICE_ACCOUNT_PATH=/var/www/storage/firebase/staging.json

# .env.local
FIREBASE_ENABLED=false  # Deshabilitar en desarrollo local
```

---

## 📋 PLAN DE ACCIÓN

### Fase 1: Fixes Críticos (1-2 días)
- [ ] Fix access token expiration (FirebaseService.php)
- [ ] Fix refreshUserToken para múltiples dispositivos
- [ ] Implementar limpieza de tokens expirados (comando + cron)
- [ ] Agregar índices a database

### Fase 2: Optimizaciones (2-3 días)
- [ ] Implementar batch processing paralelo (Guzzle Pool)
- [ ] Implementar executeParallel real
- [ ] Agregar validación de tokens antes de enviar
- [ ] Implementar manejo de errores de FCM (404/410)

### Fase 3: Monitoring (1 día)
- [ ] Crear health check endpoint
- [ ] Mejorar logs estructurados
- [ ] Configurar alertas para failures

### Fase 4: Mejoras (2 días)
- [ ] Mover credenciales de service worker a endpoint
- [ ] Implementar deduplicación con Redis
- [ ] Agregar rate limiting
- [ ] Crear tests automatizados

### Fase 5: Documentación (1 día)
- [ ] Documentar arquitectura final
- [ ] Crear guía de troubleshooting
- [ ] Documentar runbook para operaciones

---

## ✅ CHECKLIST DE MANTENIMIENTO

### Diario
- [ ] Verificar logs de errores de notificaciones
- [ ] Revisar queue status (`php artisan queue:work`)
- [ ] Verificar health check endpoint

### Semanal
- [ ] Revisar tokens expirados eliminados
- [ ] Analizar latencia de envío de notificaciones
- [ ] Verificar tasa de éxito/fallo de FCM

### Mensual
- [ ] Revisar credenciales de Firebase
- [ ] Actualizar dependencias (Guzzle, Laravel, etc.)
- [ ] Revisar logs de acceso a Firebase Realtime DB
- [ ] Optimizar índices de base de datos si es necesario

---

## 🔧 GUÍA DE TROUBLESHOOTING

### Problema: Notificaciones no llegan

**Diagnóstico**:
1. Verificar queue: `php artisan queue:work`
2. Verificar logs: `tail -f storage/logs/laravel.log`
3. Verificar token válido: `GET /fcm/token-status`
4. Verificar Firebase config: `config('services.firebase.enabled')`

**Soluciones**:
- Si queue no está corriendo: `php artisan queue:work --queue=high-priority`
- Si access token expiró: Reiniciar servicio (temporal) o aplicar fix
- Si token inválido: Re-registrar desde app móvil

---

### Problema: Notificaciones duplicadas

**Diagnóstico**:
1. Verificar logs para múltiples envíos
2. Revisar si job se ejecutó múltiples veces

**Soluciones**:
- Implementar deduplicación con Redis
- Verificar que job tenga `ShouldQueue` y `SerializesModels`
- Agregar `unique()` constraint en jobs

---

### Problema: Latencia alta

**Diagnóstico**:
1. Medir tiempo de envío en logs
2. Verificar si batch processing es secuencial
3. Revisar cantidad de tokens por usuario

**Soluciones**:
- Implementar Guzzle Pool para parallelismo
- Limitar tokens por usuario (e.g., 5 dispositivos máximo)
- Usar Redis queue en lugar de database

---

### Problema: Service Worker no funciona

**Diagnóstico**:
1. Verificar en DevTools → Application → Service Workers
2. Revisar errores en Console del navegador
3. Verificar permisos de notificaciones

**Soluciones**:
- Re-registrar service worker
- Verificar credenciales Firebase en `/firebase/config`
- Solicitar permisos de notificaciones explícitamente

---

## 📊 MÉTRICAS CLAVE

### Para Monitorear

1. **Tasa de Éxito de Envío**: `(notificaciones_exitosas / total_intentos) * 100`
   - Objetivo: >95%

2. **Latencia de Envío**: Tiempo desde llamada de mesa hasta recepción en app
   - Objetivo: <3 segundos

3. **Tokens Activos vs Expirados**: Ratio de tokens válidos
   - Objetivo: >90% activos

4. **Queue Depth**: Cantidad de jobs pendientes
   - Objetivo: <10 jobs en queue

5. **Tasa de Error de FCM**: Errores 4xx/5xx de Firebase
   - Objetivo: <2%

---

## 🚀 COMANDOS ÚTILES

```bash
# Ver queue en tiempo real
php artisan queue:work --queue=high-priority --verbose

# Limpiar tokens expirados manualmente
php artisan tokens:clean

# Ver jobs fallidos
php artisan queue:failed

# Reintentar jobs fallidos
php artisan queue:retry all

# Ver estado de workers
php artisan queue:monitor

# Test de notificación
curl -X POST http://localhost/api/mozo/fcm/test \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test","body":"Testing notifications"}'
```

---

## 📚 REFERENCIAS

### Archivos Clave
- `app/Services/FirebaseService.php` - Servicio principal FCM
- `app/Services/UnifiedFirebaseService.php` - Firebase Realtime DB
- `app/Services/StaffNotificationService.php` - Notificaciones staff
- `app/Models/DeviceToken.php` - Modelo de tokens
- `app/Jobs/ProcessWaiterCallNotification.php` - Job de notificaciones
- `public/firebase-messaging-sw.js` - Service Worker Web

### Documentación Externa
- [Firebase Cloud Messaging HTTP v1 API](https://firebase.google.com/docs/cloud-messaging/http-server-ref)
- [Web Push Protocol](https://developers.google.com/web/fundamentals/push-notifications)
- [Laravel Queues](https://laravel.com/docs/queues)
- [Guzzle Pool](https://docs.guzzlephp.org/en/stable/quickstart.html#concurrent-requests)

---

## 🔐 SEGURIDAD

### Buenas Prácticas

1. **Nunca exponer credenciales en frontend**
   - Mover Firebase config a endpoint backend
   - Usar variables de entorno

2. **Validar permisos antes de enviar**
   - Solo mozos pueden recibir notificaciones de llamadas
   - Solo admins pueden enviar broadcasts

3. **Rate limiting en todos los endpoints**
   - Prevenir spam de notificaciones
   - Proteger contra ataques DDoS

4. **Sanitizar datos antes de enviar**
   - Validar input en todos los campos
   - Escapar caracteres especiales

5. **Rotar credenciales periódicamente**
   - Service account de Firebase cada 6 meses
   - Access tokens automáticamente

---

## 📞 CONTACTO Y SOPORTE

Para reportar bugs o solicitar features relacionadas con notificaciones:

1. Crear issue en GitHub con etiqueta `notifications`
2. Incluir logs relevantes de `storage/logs/laravel.log`
3. Especificar ambiente (production/staging/local)
4. Incluir pasos para reproducir

---

**Última actualización**: 2025-11-04
**Próxima revisión**: 2025-12-04
