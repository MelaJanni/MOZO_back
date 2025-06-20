<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = Business::all();

        if ($businesses->isEmpty()) {
            return;
        }

        foreach ($businesses as $business) {
            // Crear usuario administrador si no existe
            $adminEmail = 'admin@' . strtolower(str_replace(' ', '', $business->name)) . '.com';
            if (!User::where('email', $adminEmail)->exists()) {
                $admin = User::create([
                    'name' => 'Admin ' . $business->name,
                    'email' => $adminEmail,
                    'password' => Hash::make('password'),
                    'role' => 'admin',
                    'active_business_id' => $business->id,
                ]);
                $admin->businesses()->attach($business->id);
            }

            // Crear varios usuarios camareros para cada negocio
            for ($i = 1; $i <= 5; $i++) {
                $waiterEmail = 'waiter' . $i . '@' . strtolower(str_replace(' ', '', $business->name)) . '.com';
                
                if (!User::where('email', $waiterEmail)->exists()) {
                    $waiter = User::create([
                        'name' => 'Waiter ' . $i . ' ' . $business->name,
                        'email' => $waiterEmail,
                        'password' => Hash::make('password'),
                        'role' => 'waiter',
                        'active_business_id' => $business->id,
                    ]);
                    $waiter->businesses()->attach($business->id);
                }
            }
        }
    }
}
