<?php

namespace App\Http\Controllers\Concerns;

trait ResolvesActiveBusiness
{
    /**
     * Obtiene el business_id activo para el usuario, priorizando el rol indicado.
     */
    protected function activeBusinessId($user, ?string $preferredRole = 'admin'): ?int
    {
        $activeBusinessId = null;

        // 1) user_active_roles (rol preferido -> último switched)
        try {
            if (method_exists($user, 'activeRoles')) {
                $query = $user->activeRoles();
                if ($preferredRole) {
                    $query->where('active_role', $preferredRole);
                }
                $active = $query->latest('switched_at')->first();
                if ($active) {
                    $activeBusinessId = (int)$active->business_id;
                }
            }
        } catch (\Throwable $e) {
            // noop
        }

        // 2) Propiedad activa si existe
        if (!$activeBusinessId && !empty($user->active_business_id)) {
            $activeBusinessId = (int)$user->active_business_id;
        }

        // 3) Fallback al business_id simple si existe
        if (!$activeBusinessId && !empty($user->business_id)) {
            $activeBusinessId = (int)$user->business_id;
        }

        // 4) Si solo pertenece a un negocio como admin
        if (!$activeBusinessId) {
            try {
                if (method_exists($user, 'businessesAsAdmin')) {
                    $ids = $user->businessesAsAdmin()->pluck('business_id');
                    if ($ids->count() === 1) {
                        $activeBusinessId = (int)$ids->first();
                    }
                }
            } catch (\Throwable $e) {
                // noop
            }
        }

        return $activeBusinessId ?: null;
    }
}
