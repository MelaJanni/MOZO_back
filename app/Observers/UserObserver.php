<?php

namespace App\Observers;

use App\Models\User;
use App\Models\WaiterProfile;

class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * Cuando se crea un usuario, automáticamente se le crea un WaiterProfile
     * porque todos los usuarios son mozos por defecto (rol gratuito).
     */
    public function created(User $user): void
    {
        // Solo crear WaiterProfile si no es un super admin del sistema
        if (!$user->is_system_super_admin) {
            WaiterProfile::create([
                'user_id' => $user->id,
                'display_name' => $user->name,
                'is_active' => true,
                'is_available' => true,
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
