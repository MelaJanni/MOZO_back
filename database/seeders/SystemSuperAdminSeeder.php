<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SystemSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SYSTEM_SUPER_ADMIN_EMAIL');
        $password = env('SYSTEM_SUPER_ADMIN_PASSWORD');
        if (!$email || !$password) {
            echo "[SystemSuperAdminSeeder] Variables .env faltantes.\n";
            return;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'System Admin',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_system_super_admin' => true,
            ]
        );

        // Asegurar el flag aunque el usuario ya existiera
        if (!$user->is_system_super_admin) {
            $user->is_system_super_admin = true;
            $user->save();
        }
    }
}
