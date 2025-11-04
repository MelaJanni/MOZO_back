<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL');
        if (!$email) {
            return; // No configurado, no hacemos nada
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return;
        }

        try {
            $user->assignRole('super_admin');
        } catch (\Throwable $e) {
            // Silenciar si el rol ya existe o está asignado
        }
    }
}
