# Fix: Error 500 en Login con Google (Primera vez)

## Problema
Cuando un usuario se registraba por primera vez con Google, la cuenta se creaba correctamente pero el sistema retornaba un error 500 al intentar iniciar sesión.

## Causa Raíz
El problema era causado por **transacciones de base de datos anidadas** y **condiciones de carrera** al crear el `WaiterProfile`:

1. `AuthController::loginWithGoogle()` ejecutaba todo dentro de `\DB::transaction()`
2. `UserObserver::created()` también usaba `\DB::transaction()` internamente
3. Esto causaba conflictos y errores al intentar crear el perfil del mozo

## Solución Implementada

### 1. UserObserver.php
**Cambio principal:** Usar `DB::afterCommit()` en lugar de `DB::transaction()`

```php
public function created(User $user): void
{
    if (!$user->is_system_super_admin) {
        // Usar afterCommit para evitar transacciones anidadas
        \DB::afterCommit(function () use ($user) {
            try {
                // Usar firstOrCreate para evitar duplicados (manejo atómico)
                WaiterProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'display_name' => $user->name,
                        'is_available' => true,
                        'is_available_for_hire' => true,
                    ]
                );
            } catch (\Exception $e) {
                \Log::warning('Error creando WaiterProfile...', [...]);
            }
        });
    }
}
```

**Beneficios:**
- ✅ `DB::afterCommit()` espera a que la transacción padre se complete
- ✅ `firstOrCreate()` es atómico y previene duplicados
- ✅ No hay transacciones anidadas

### 2. AuthController.php
**Cambio:** Remover la creación manual del WaiterProfile

```php
// ANTES: Creaba manualmente el perfil
$user->waiterProfile()->create([...]);

// AHORA: Confía en el Observer
// El UserObserver creará automáticamente el WaiterProfile
// usando DB::afterCommit para evitar problemas de transacciones anidadas
```

**Además:**
- Se añadió `$user->refresh()` antes de retornar la respuesta
- Esto asegura que el usuario tenga todos los datos actualizados

### 3. Comando de Reparación
Se creó el comando `FixMissingWaiterProfiles` para arreglar usuarios existentes:

```bash
php artisan fix:missing-waiter-profiles
```

Este comando:
- Busca usuarios sin WaiterProfile
- Crea los perfiles faltantes usando `firstOrCreate()`
- Reporta cuántos perfiles fueron creados

## Verificación

Ejecuta el script de verificación para confirmar que el fix está aplicado:

```bash
php verify_google_login_fix.php
```

Todos los checks deben estar en ✅

## Prevención Futura

### Reglas a seguir:

1. **NUNCA usar `DB::transaction()` dentro de observers**
   - Usar `DB::afterCommit()` en su lugar
   
2. **Preferir `firstOrCreate()` sobre `create()`**
   - Es atómico y previene duplicados

3. **Evitar transacciones anidadas**
   - Si un método ya está en una transacción, los observers deben usar `afterCommit()`

4. **Siempre hacer `$model->refresh()`**
   - Después de operaciones que puedan modificar el modelo
   - Antes de retornar datos al cliente

## Testing

Para probar el fix:

1. Crear una nueva cuenta con Google (primera vez)
2. Verificar que:
   - La cuenta se crea correctamente
   - No hay error 500
   - El usuario puede iniciar sesión inmediatamente
   - Se crea automáticamente el WaiterProfile

## Monitoreo

Revisar los logs para confirmar que no hay errores:

```bash
# Ver logs de creación de usuarios
tail -f storage/logs/laravel.log | grep "Google login"

# Ver si hay errores de WaiterProfile
tail -f storage/logs/laravel.log | grep "WaiterProfile"
```

## Archivos Modificados

1. `app/Observers/UserObserver.php` - Fix principal
2. `app/Http/Controllers/AuthController.php` - Limpieza de código duplicado
3. `app/Console/Commands/FixMissingWaiterProfiles.php` - Comando de reparación (nuevo)
4. `verify_google_login_fix.php` - Script de verificación (nuevo)

## Conclusión

Este fix es **permanente** porque:
- ✅ Elimina la causa raíz (transacciones anidadas)
- ✅ Usa patrones robustos (`afterCommit`, `firstOrCreate`)
- ✅ Incluye herramientas de verificación y reparación
- ✅ Está documentado para prevenir regresiones futuras
