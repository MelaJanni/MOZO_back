<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Servicio centralizado para resolver el business_id activo de un usuario.
 * 
 * Prioridades de resolución:
 * 1. user_active_roles (rol preferido -> último switched_at)
 * 2. users.active_business_id
 * 3. users.business_id
 * 4. Si solo pertenece a un negocio como admin/waiter
 * 
 * @package App\Services
 */
class BusinessResolver
{
    /**
     * Resuelve el business_id activo para el usuario según el rol preferido.
     *
     * @param User $user Usuario autenticado
     * @param string|null $preferredRole Rol preferido para resolver business ('admin', 'waiter', null)
     * @param bool $throwIfNotFound Si es true, lanza excepción si no se encuentra business
     * @return int|null ID del negocio activo o null si no se encuentra
     * @throws \RuntimeException Si $throwIfNotFound=true y no se encuentra business
     */
    public function resolve(User $user, ?string $preferredRole = null, bool $throwIfNotFound = false): ?int
    {
        $businessId = null;

        // 1) Buscar en user_active_roles (tabla de sesiones por rol)
        $businessId = $this->resolveFromActiveRoles($user, $preferredRole);

        // 2) Fallback a users.active_business_id
        if (!$businessId && !empty($user->active_business_id)) {
            $businessId = (int) $user->active_business_id;
            Log::debug('BusinessResolver: Usando active_business_id', [
                'user_id' => $user->id,
                'business_id' => $businessId
            ]);
        }

        // 3) Fallback a users.business_id
        if (!$businessId && !empty($user->business_id)) {
            $businessId = (int) $user->business_id;
            Log::debug('BusinessResolver: Usando business_id legacy', [
                'user_id' => $user->id,
                'business_id' => $businessId
            ]);
        }

        // 4) Fallback si solo pertenece a un negocio
        if (!$businessId) {
            $businessId = $this->resolveFromUniqueBusiness($user, $preferredRole);
        }

        // Si no se encontró y se debe lanzar excepción
        if (!$businessId && $throwIfNotFound) {
            throw new \RuntimeException(
                "No se pudo resolver business_id para user_id={$user->id} con rol={$preferredRole}"
            );
        }

        return $businessId;
    }

    /**
     * Resuelve business_id desde user_active_roles (tabla de sesiones).
     *
     * @param User $user
     * @param string|null $preferredRole
     * @return int|null
     */
    protected function resolveFromActiveRoles(User $user, ?string $preferredRole): ?int
    {
        try {
            if (!method_exists($user, 'activeRoles')) {
                return null;
            }

            $query = $user->activeRoles();

            // Filtrar por rol preferido si se especifica
            if ($preferredRole) {
                $query->where('active_role', $preferredRole);
            }

            // Obtener el más reciente (switched_at)
            $activeRole = $query->latest('switched_at')->first();

            if ($activeRole) {
                $businessId = (int) $activeRole->business_id;
                Log::debug('BusinessResolver: business_id desde user_active_roles', [
                    'user_id' => $user->id,
                    'business_id' => $businessId,
                    'role' => $activeRole->active_role,
                    'switched_at' => $activeRole->switched_at
                ]);
                return $businessId;
            }
        } catch (\Throwable $e) {
            Log::warning('BusinessResolver: Error en resolveFromActiveRoles', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Resuelve business_id si el usuario solo pertenece a un negocio.
     *
     * @param User $user
     * @param string|null $preferredRole
     * @return int|null
     */
    protected function resolveFromUniqueBusiness(User $user, ?string $preferredRole): ?int
    {
        try {
            // Intentar con businessesAsAdmin si es rol admin
            if ($preferredRole === 'admin' && method_exists($user, 'businessesAsAdmin')) {
                $ids = $user->businessesAsAdmin()->pluck('business_id');
                if ($ids->count() === 1) {
                    $businessId = (int) $ids->first();
                    Log::debug('BusinessResolver: business_id único como admin', [
                        'user_id' => $user->id,
                        'business_id' => $businessId
                    ]);
                    return $businessId;
                }
            }

            // Intentar con businessesAsWaiter si es rol waiter
            if ($preferredRole === 'waiter' && method_exists($user, 'businessesAsWaiter')) {
                $ids = $user->businessesAsWaiter()->pluck('business_id');
                if ($ids->count() === 1) {
                    $businessId = (int) $ids->first();
                    Log::debug('BusinessResolver: business_id único como waiter', [
                        'user_id' => $user->id,
                        'business_id' => $businessId
                    ]);
                    return $businessId;
                }
            }

            // Si no hay rol preferido, buscar en cualquier relación
            if (!$preferredRole) {
                // Intentar cualquier método disponible
                foreach (['businessesAsAdmin', 'businessesAsWaiter', 'businesses'] as $method) {
                    if (method_exists($user, $method)) {
                        $ids = $user->$method()->pluck('business_id');
                        if ($ids->count() === 1) {
                            $businessId = (int) $ids->first();
                            Log::debug('BusinessResolver: business_id único sin rol preferido', [
                                'user_id' => $user->id,
                                'business_id' => $businessId,
                                'method' => $method
                            ]);
                            return $businessId;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('BusinessResolver: Error en resolveFromUniqueBusiness', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Verifica si el usuario tiene acceso a un business específico.
     *
     * @param User $user
     * @param int $businessId
     * @param string|null $role Rol requerido ('admin', 'waiter', null)
     * @return bool
     */
    public function hasAccessTo(User $user, int $businessId, ?string $role = null): bool
    {
        try {
            // Si se especifica rol, verificar en la relación correspondiente
            if ($role === 'admin' && method_exists($user, 'businessesAsAdmin')) {
                return $user->businessesAsAdmin()
                    ->where('business_id', $businessId)
                    ->exists();
            }

            if ($role === 'waiter' && method_exists($user, 'businessesAsWaiter')) {
                return $user->businessesAsWaiter()
                    ->where('business_id', $businessId)
                    ->exists();
            }

            // Verificar en user_active_roles
            if (method_exists($user, 'activeRoles')) {
                $query = $user->activeRoles()->where('business_id', $businessId);
                if ($role) {
                    $query->where('active_role', $role);
                }
                if ($query->exists()) {
                    return true;
                }
            }

            // Verificar en cualquier relación de negocios
            if (method_exists($user, 'businesses')) {
                return $user->businesses()
                    ->where('business_id', $businessId)
                    ->exists();
            }
        } catch (\Throwable $e) {
            Log::warning('BusinessResolver: Error en hasAccessTo', [
                'user_id' => $user->id,
                'business_id' => $businessId,
                'role' => $role,
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Establece el business activo para el usuario (persiste en user_active_roles).
     *
     * @param User $user
     * @param int $businessId
     * @param string $role Rol con el que se activa el business
     * @return bool
     */
    public function setActiveBusiness(User $user, int $businessId, string $role = 'admin'): bool
    {
        try {
            // Verificar que el usuario tenga acceso al business
            if (!$this->hasAccessTo($user, $businessId, $role)) {
                Log::warning('BusinessResolver: Intento de setActiveBusiness sin acceso', [
                    'user_id' => $user->id,
                    'business_id' => $businessId,
                    'role' => $role
                ]);
                return false;
            }

            // Actualizar/crear en user_active_roles
            if (method_exists($user, 'activeRoles')) {
                $user->activeRoles()->updateOrCreate(
                    ['business_id' => $businessId],
                    [
                        'active_role' => $role,
                        'switched_at' => now()
                    ]
                );

                Log::info('BusinessResolver: Business activo establecido', [
                    'user_id' => $user->id,
                    'business_id' => $businessId,
                    'role' => $role
                ]);

                return true;
            }
        } catch (\Throwable $e) {
            Log::error('BusinessResolver: Error en setActiveBusiness', [
                'user_id' => $user->id,
                'business_id' => $businessId,
                'role' => $role,
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }
}
