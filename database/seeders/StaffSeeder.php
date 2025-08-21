<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Staff;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los negocios
        $businesses = Business::all();
        
        if ($businesses->isEmpty()) {
            // Si no hay negocios, crear uno para los seeders
            $business = Business::create([
                'name' => 'Restaurant Demo',
                'address' => 'Av. Corrientes 1234, CABA',
                'phone' => '+5491123456789',
                'email' => 'info@restaurantdemo.com',
            ]);
            
            $businesses = collect([$business]);
        }
        
        // Crear diferentes estados para el personal
        $statuses = ['pending', 'confirmed', 'rejected'];
        
        // Posiciones comunes en restaurantes
        $positions = ['Mozo', 'Chef', 'Ayudante de cocina', 'Bartender', 'Recepcionista', 'Gerente', 'Limpieza'];
        
        // Para cada negocio, crear personal
        foreach ($businesses as $business) {
            // Crear 10 registros de personal para cada negocio si no existen ya
            for ($i = 1; $i <= 10; $i++) {
                $email = 'staff' . $i . '_' . $business->id . '@example.com';
                
                // Verificar si ya existe un staff con este email
                if (!Staff::where('email', $email)->exists()) {
                    Staff::create([
                        'business_id' => $business->id,
                        'name' => 'Staff ' . $i . ' ' . $business->name,
                        'email' => $email,
                        'phone' => '+549112345' . sprintf('%04d', $i + 1000),
                        'position' => $positions[array_rand($positions)],
                        'status' => $statuses[array_rand($statuses)],
                        'employment_type' => ['por horas', 'tiempo completo', 'tiempo parcial', 'solo fines de semana'][array_rand(['por horas', 'tiempo completo', 'tiempo parcial', 'solo fines de semana'])],
                        'experience_years' => rand(0, 15),
                        'birth_date' => now()->subYears(rand(18, 50))->subDays(rand(1, 365)),
                        'height' => round(rand(150, 200) / 100, 2),
                        'weight' => rand(50, 120),
                        'gender' => ['masculino', 'femenino', 'otro'][array_rand(['masculino', 'femenino', 'otro'])],
                        'current_schedule' => ['mañana', 'tarde', 'noche', 'mixto'][array_rand(['mañana', 'tarde', 'noche', 'mixto'])],
                        'salary' => rand(30000, 100000),
                        'hire_date' => now()->subDays(rand(1, 365)),
                    ]);
                }
            }
        }
    }
} 