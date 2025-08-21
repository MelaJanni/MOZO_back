<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## MOZO Back-end (Unificación Firebase / FCM)

Este proyecto Laravel provee la API y lógica de notificaciones en tiempo real para el ecosistema MOZO.

### Arquitectura de Notificaciones (Estado Actual)
1. FUENTE ÚNICA: `UnifiedFirebaseService` escribe la llamada en estructura unificada Realtime Database:
	 - `active_calls/{call_id}` (datos completos)
	 - Índices: `waiters/{waiter_id}`, `tables/{table_id}`, `businesses/{business_id}`
2. FCM HTTP v1: Se envía push (visible con app cerrada) con payload estándar:
	 - channel: `mozo_waiter`
	 - data: `{ type: new_call|acknowledged|completed, call_id, table_number, urgency, status }`
	 - collapse_key: por `call_id` para evitar duplicados
	 - ttl: 60s (dedupe rápido)
3. Estados subsecuentes (acknowledged / completed) también via `UnifiedFirebaseService` (update o remove + FCM).

### Eliminado (Legacy Eliminado) ✅
Se removieron completamente servicios anteriores:
- `FirebaseRealtimeService` (Firestore REST multi-path)
- `FirebaseRealtimeDatabaseService`
- `FirebaseRealtimeNotificationService`
- `PushNotificationService` (server key legacy)

Ahora SOLO se utiliza `FirebaseService` (FCM HTTP v1) + `UnifiedFirebaseService` (estructura unificada y disparo de eventos).

### Flujo Básico
1. Mesa genera llamada -> crea `WaiterCall` en BD.
2. `UnifiedFirebaseService::writeCall()` escribe datos + envía FCM `new_call`.
3. Mozo reconoce -> `writeCall(...,'acknowledged')` + FCM `acknowledged`.
4. Mozo completa -> `removeCall()` + FCM `completed` + cleanup índices.

### Estructura de Datos Unificada (Ejemplo `active_calls/{id}`)
```json
{
	"id": "123",
	"table_id": "7",
	"table_number": 7,
	"waiter_id": "42",
	"status": "pending",
	"message": "Mesa 7 solicita atención",
	"urgency": "normal",
	"called_at": 1734567890000,
	"acknowledged_at": null,
	"completed_at": null,
	"business_id": "5",
	"table": { "id": "7", "number": 7, "name": "Mesa 7", "notifications_enabled": true },
	"waiter": { "id": "42", "name": "Carlos", "is_online": true, "last_seen": 1734567900000 },
	"last_updated": 1734567900000,
	"event_type": "created"
}
```

### Payload FCM (ejemplo new_call)
```json
{
	"notification": {
		"title": "Mesa 7",
		"body": "Mesa 7 solicita atención"
	},
	"data": {
		"type": "new_call",
		"call_id": "123",
		"table_number": "7",
		"waiter_id": "42",
		"urgency": "normal",
		"status": "pending",
		"source": "unified"
	},
	"android": {
		"notification": { "channel_id": "mozo_waiter" },
		"collapse_key": "call_123",
		"ttl": "60s"
	}
}
```

### Migraciones Pendientes (Opcional Futuro)
- Propagar silencios de mesa a estructura unificada (campo `table.stats` o `table_state`).
- Topic FCM por negocio para métricas globales en dashboards.
- Optimizar escrituras paralelas reales (Guzzle Pool) si la carga aumenta.

### Administración de Negocios (Single Admin Actual / Multi-Admin Futuro)

Estado actual:
- Cada negocio tiene exactamente UN admin activo (enforced por índice único `business_admins_business_id_unique_single`).
- El método `Business::addAdmin()` reemplaza al existente si se indica `$replaceIfExists = true`.
- El "rol activo" (`user_active_roles`) solo controla qué UI ve el usuario (frontend); las políticas backend usan exclusivamente pivotes reales.

Extender a multi-admin (plan futuro):
1. Crear migración que haga `Schema::table('business_admins', fn($t) => $t->dropUnique('business_admins_business_id_unique_single'));`.
2. Eliminar lógica de reemplazo en `addAdmin()` o permitir múltiples con validación de niveles (`permission_level`).
3. Ajustar UI para listar admins y permitir revocar.
4. (Opcional) Añadir columna `is_primary` para destacar uno.

Políticas de autorización:
- `BusinessPolicy::manage` verifica admin real (pivot) ignorando `active_role`.
- `BusinessPolicy::view` permite tanto admin como waiter.

Tests clave:
- `SingleAdminTest` asegura restricción actual.
- `ActiveRoleDoesNotAffectPermissionsTest` asegura que cambiar `active_role` no otorga permisos.

### Limpieza de Código Legacy

El sistema legacy de perfiles fue eliminado. Use exclusivamente los endpoints `user-profile/*`.

Estado actual:
- Eliminados: `ProfileController`, `TableProfileController`, y `App\Models\Profile`.
- Rutas legacy removidas de `routes/api.php`.
- Referencias a `profiles()` en controladores fueron limpiadas.

Acción pendiente (frontend):
- Migrar cualquier uso de endpoints legacy a `user-profile/*`.


### Variables de Entorno Clave
```
FIREBASE_PROJECT_ID=mozoqr-7d32c
FIREBASE_SERVICE_ACCOUNT_PATH=storage/app/firebase/firebase.json
FIREBASE_WEB_API_KEY=...
```

### Comando de Diagnóstico
`php artisan firebase:setup --test` (ajustar para unified si se desea ampliar)

---
Documentación genérica de Laravel omitida para mantener claridad en la arquitectura específica de MOZO.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
