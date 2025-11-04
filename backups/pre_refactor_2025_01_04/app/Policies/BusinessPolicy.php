<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    /** Determina si el usuario es admin REAL del negocio (pivot business_admins). */
    public function manage(User $user, Business $business): bool
    {
        return $business->isAdministratedBy($user);
    }

    /** Determina si el usuario puede ver el negocio (admin o waiter). */
    public function view(User $user, Business $business): bool
    {
        return $business->isAdministratedBy($user) || $business->hasWaiter($user);
    }
}
