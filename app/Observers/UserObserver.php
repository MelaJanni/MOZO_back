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
            try {
                // Verificar con transacción para evitar condiciones de carrera
                \DB::transaction(function () use ($user) {
                    // Verificar explícitamente si ya existe
                    $existing = WaiterProfile::where('user_id', $user->id)->exists();
                    if (!$existing) {
                        WaiterProfile::create([
                            'user_id' => $user->id,
                            'display_name' => $user->name,
                            'is_available' => true,
                            'is_available_for_hire' => true,
                        ]);
                    }
                });
            } catch (\Exception $e) {
                // Log el error pero no fallar la creación del usuario
                \Log::warning('Error creando WaiterProfile para usuario ' . $user->id, [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            }
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
        // Eliminar perfiles asociados cuando se elimina el usuario
        $user->adminProfile()?->delete();
        $user->waiterProfile()?->delete();
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
