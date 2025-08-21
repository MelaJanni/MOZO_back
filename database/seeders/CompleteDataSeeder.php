<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\User;
use App\Models\Profile;
use App\Models\Staff;
use App\Models\WorkExperience;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompleteDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Business (usando firstOrCreate para evitar duplicados)
        $business1 = Business::firstOrCreate(
            ['invitation_code' => 'PLAZA123'],
            [
                'name' => 'Restaurante La Plaza',
                'address' => 'Av. Corrientes 1234, Buenos Aires',
                'phone' => '+54 11 4567-8901',
                'email' => 'info@laplaza.com',
            ]
        );

        $business2 = Business::firstOrCreate(
            ['invitation_code' => 'CAFE456'],
            [
                'name' => 'Café Central',
                'address' => 'San Martín 567, Córdoba',
                'phone' => '+54 351 123-4567',
                'email' => 'contacto@cafecentral.com',
            ]
        );

        // 2. Crear Admins (usando firstOrCreate para evitar duplicados)
        $admin1 = User::firstOrCreate(
            ['email' => 'maria@laplaza.com'],
            [
                'name' => 'María González',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'active_business_id' => $business1->id,
            ]
        );
        $admin1->businesses()->syncWithoutDetaching($business1->id);

        $admin2 = User::firstOrCreate(
            ['email' => 'carlos@cafecentral.com'],
            [
                'name' => 'Carlos Rodríguez',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'active_business_id' => $business2->id,
            ]
        );
        $admin2->businesses()->syncWithoutDetaching($business2->id);

        // 3. Crear Perfiles para Admins (usando firstOrCreate)
        $admin1->profile()->firstOrCreate(
            ['user_id' => $admin1->id],
            [
                'name' => 'María González',
                'phone' => '+54 11 9876-5432',
                'business_id' => $business1->id,
            ]
        );

        $admin2->profile()->firstOrCreate(
            ['user_id' => $admin2->id],
            [
                'name' => 'Carlos Rodríguez',
                'phone' => '+54 351 987-6543',
                'business_id' => $business2->id,
            ]
        );

        // 4. Crear Mozos con perfiles completos
        $mozos = [
            [
                'name' => 'Juan Pérez',
                'email' => 'juan.perez@email.com',
                'business_id' => $business1->id,
                'profile' => [
                    'phone' => '+54 11 1234-5678',
                    'address' => 'Belgrano 123, CABA',
                    'bio' => 'Mozo con 3 años de experiencia en restaurantes.',
                    'date_of_birth' => '1995-03-15',
                    'gender' => 'masculino',
                    'height' => 175.5,
                    'weight' => 70.0,
                    'experience_years' => 3,
                    'employment_type' => 'tiempo_completo',
                    'current_schedule' => 'Lunes a Viernes 18:00-24:00, Sábados 12:00-24:00',
                    'skills' => ['atención al cliente', 'manejo de POS', 'conocimiento de vinos'],
                    'latitude' => -34.6037,
                    'longitude' => -58.3816,
                ],
                'work_experiences' => [
                    [
                        'company' => 'Restaurante El Gaucho',
                        'position' => 'Mozo',
                        'description' => 'Atención al cliente, manejo de mesas VIP',
                        'start_date' => '2021-01-15',
                        'end_date' => '2023-12-31',
                    ],
                    [
                        'company' => 'Bar & Grill Downtown',
                        'position' => 'Ayudante de Mozo',
                        'description' => 'Apoyo en servicio de mesas y bar',
                        'start_date' => '2020-06-01',
                        'end_date' => '2020-12-31',
                    ]
                ]
            ],
            [
                'name' => 'Ana Martínez',
                'email' => 'ana.martinez@email.com',
                'business_id' => $business1->id,
                'profile' => [
                    'phone' => '+54 11 8765-4321',
                    'address' => 'Palermo 456, CABA',
                    'bio' => 'Especialista en servicio de mesa y coctelería.',
                    'date_of_birth' => '1992-07-22',
                    'gender' => 'femenino',
                    'height' => 165.0,
                    'weight' => 58.0,
                    'experience_years' => 5,
                    'employment_type' => 'tiempo_completo',
                    'current_schedule' => 'Martes a Domingo 19:00-01:00',
                    'skills' => ['coctelería', 'servicio de vinos', 'idioma inglés'],
                    'latitude' => -34.5875,
                    'longitude' => -58.4260,
                ],
                'work_experiences' => [
                    [
                        'company' => 'Hotel Sheraton',
                        'position' => 'Bartender',
                        'description' => 'Preparación de cocteles y atención en bar',
                        'start_date' => '2019-03-01',
                        'end_date' => '2024-01-15',
                    ]
                ]
            ],
            [
                'name' => 'Luis García',
                'email' => 'luis.garcia@email.com',
                'business_id' => $business2->id,
                'profile' => [
                    'phone' => '+54 351 555-1234',
                    'address' => 'Nueva Córdoba 789',
                    'bio' => 'Mozo experimentado en cafeterías y restaurantes.',
                    'date_of_birth' => '1988-11-10',
                    'gender' => 'masculino',
                    'height' => 180.0,
                    'weight' => 75.0,
                    'experience_years' => 7,
                    'employment_type' => 'medio_tiempo',
                    'current_schedule' => 'Lunes a Viernes 07:00-14:00',
                    'skills' => ['café specialty', 'atención rápida', 'manejo de caja'],
                    'latitude' => -31.4201,
                    'longitude' => -64.1888,
                ],
                'work_experiences' => [
                    [
                        'company' => 'Starbucks Córdoba',
                        'position' => 'Barista',
                        'description' => 'Preparación de café especiality y atención al cliente',
                        'start_date' => '2017-01-01',
                        'end_date' => null, // Trabajo actual
                    ]
                ]
            ]
        ];

        foreach ($mozos as $mozoData) {
            // Crear User (usando firstOrCreate)
            $user = User::firstOrCreate(
                ['email' => $mozoData['email']],
                [
                    'name' => $mozoData['name'],
                    'password' => Hash::make('password'),
                    'role' => 'waiter',
                    'active_business_id' => $mozoData['business_id'],
                ]
            );
            $user->businesses()->syncWithoutDetaching($mozoData['business_id']);

            // Crear Profile completo (usando firstOrCreate)
            $profile = $user->profile()->firstOrCreate(
                ['user_id' => $user->id],
                array_merge([
                    'name' => $mozoData['name'],
                    'business_id' => $mozoData['business_id'],
                ], $mozoData['profile'])
            );

            // Crear Work Experiences (evitar duplicados)
            foreach ($mozoData['work_experiences'] as $workExp) {
                $user->workExperiences()->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'company' => $workExp['company']
                    ],
                    $workExp
                );
            }

            // Crear Staff Request (solicitud automática)
            Staff::firstOrCreate(
                [
                    'business_id' => $mozoData['business_id'],
                    'user_id' => $user->id
                ],
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $mozoData['profile']['phone'],
                    'status' => 'pending', // Solicitud pendiente
                    'position' => 'Mozo',
                    'hire_date' => null, // No hay fecha de contratación para solicitudes pendientes
                    'birth_date' => $mozoData['profile']['date_of_birth'],
                    'height' => $mozoData['profile']['height'],
                    'weight' => $mozoData['profile']['weight'],
                    'gender' => $mozoData['profile']['gender'],
                    'experience_years' => $mozoData['profile']['experience_years'],
                    'employment_type' => $mozoData['profile']['employment_type'],
                    'current_schedule' => $mozoData['profile']['current_schedule'],
                ]
            );
        }

        // 5. Crear algunos staff confirmados (empleados actuales)
        $confirmedStaff = [
            [
                'name' => 'Roberto Silva',
                'email' => 'roberto.silva@email.com',
                'business_id' => $business1->id,
                'status' => 'confirmed',
                'position' => 'Mozo Senior',
                'salary' => 85000.00,
                'hire_date' => '2023-01-15',
                'notes' => 'Excelente empleado, muy responsable.',
            ],
            [
                'name' => 'Laura Fernández',
                'email' => 'laura.fernandez@email.com',
                'business_id' => $business2->id,
                'status' => 'confirmed',
                'position' => 'Supervisora de Salón',
                'salary' => 95000.00,
                'hire_date' => '2022-06-01',
                'notes' => 'Líder natural, maneja muy bien el equipo.',
            ]
        ];

        foreach ($confirmedStaff as $staffData) {
            Staff::firstOrCreate(
                [
                    'business_id' => $staffData['business_id'],
                    'email' => $staffData['email']
                ],
                $staffData
            );
        }

        $this->command->info('✅ Datos completos creados exitosamente!');
        $this->command->info('👨‍💼 Admins creados:');
        $this->command->info('  - maria@laplaza.com / password');
        $this->command->info('  - carlos@cafecentral.com / password');
        $this->command->info('🍽️ Mozos creados:');
        $this->command->info('  - juan.perez@email.com / password');
        $this->command->info('  - ana.martinez@email.com / password');
        $this->command->info('  - luis.garcia@email.com / password');
        $this->command->info('🏢 Códigos de invitación:');
        $this->command->info('  - Restaurante La Plaza: PLAZA123');
        $this->command->info('  - Café Central: CAFE456');
    }
}