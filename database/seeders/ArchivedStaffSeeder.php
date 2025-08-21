<?php

namespace Database\Seeders;

use App\Models\ArchivedStaff;
use App\Models\Business;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ArchivedStaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los negocios
        $businesses = Business::all();
        
        if ($businesses->isEmpty()) {
            // Si no hay negocios, salir - este seeder depende de que haya negocios
            return;
        }
        
        // Posiciones comunes en restaurantes
        $positions = ['Mozo', 'Chef', 'Ayudante de cocina', 'Bartender', 'Recepcionista', 'Gerente', 'Limpieza'];
        
        // Estados posibles
        $statuses = ['rejected', 'confirmed', 'archived'];
        
        // Para cada negocio, crear algunos registros de personal archivado
        foreach ($businesses as $business) {
            // Crear 5 registros de personal archivado para cada negocio
            for ($i = 1; $i <= 5; $i++) {
                $email = 'archived' . $i . '_' . $business->id . '@example.com';
                
                // Verificar si ya existe un staff archivado con este email
                if (!ArchivedStaff::where('email', $email)->exists()) {
                    // Fecha de contratación
                    $hireDate = now()->subDays(rand(30, 365));
                    
                    // Fecha de terminación (posterior a la contratación)
                    $terminationDate = clone $hireDate;
                    $terminationDate->addDays(rand(30, 180));
                    
                    ArchivedStaff::create([
                        'business_id' => $business->id,
                        'name' => 'Archived Staff ' . $i . ' ' . $business->name,
                        'position' => $positions[array_rand($positions)],
                        'email' => $email,
                        'phone' => '+549112345' . sprintf('%04d', $i + 100),
                        'hire_date' => $hireDate,
                        'termination_date' => $terminationDate,
                        'salary' => rand(30000, 100000),
                        'status' => $statuses[array_rand($statuses)],
                        'notes' => rand(0, 1) ? 'Notas sobre el personal archivado ' . $i : null,
                        'archived_at' => now()->subDays(rand(1, 30)),
                        'employment_type' => ['por horas', 'tiempo completo', 'tiempo parcial', 'solo fines de semana'][array_rand(['por horas', 'tiempo completo', 'tiempo parcial', 'solo fines de semana'])],
                        'experience_years' => rand(0, 15),
                        'birth_date' => now()->subYears(rand(18, 50))->subDays(rand(1, 365)),
                        'height' => round(rand(150, 200) / 100, 2),
                        'weight' => rand(50, 120),
                        'gender' => ['masculino', 'femenino', 'otro'][array_rand(['masculino', 'femenino', 'otro'])],
                        'current_schedule' => ['mañana', 'tarde', 'noche', 'mixto'][array_rand(['mañana', 'tarde', 'noche', 'mixto'])],
                    ]);
                }
            }
        }
    }
} 