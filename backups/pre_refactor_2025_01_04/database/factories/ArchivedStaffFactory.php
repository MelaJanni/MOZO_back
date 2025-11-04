<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\ArchivedStaff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArchivedStaff>
 */
class ArchivedStaffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $positions = ['Mozo', 'Bartender', 'Cocinero', 'Recepcionista', 'Gerente', 'Ayudante de cocina', 'Limpieza'];
        $reasons = ['Renuncia', 'Despido', 'Fin de contrato', 'Jubilación', 'Reestructuración'];
        
        $hireDate = fake()->dateTimeBetween('-5 years', '-3 months');
        
        $birthDate = fake()->dateTimeBetween('-50 years', '-18 years');
        $height = fake()->randomFloat(2, 1.50, 1.95);
        $weight = fake()->randomFloat(2, 50, 100);
        
        return [
            'business_id' => Business::factory(),
            'name' => fake()->name(),
            'position' => fake()->randomElement($positions),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'hire_date' => $hireDate,
            'termination_date' => fake()->dateTimeBetween($hireDate, 'now'),
            'termination_reason' => fake()->randomElement($reasons),
            'last_salary' => fake()->numberBetween(30000, 150000),
            'notes' => fake()->optional(0.7)->paragraph(),
            'birth_date' => $birthDate,
            'height' => $height,
            'weight' => $weight,
            'gender' => fake()->randomElement(['male', 'female']),
            'experience_years' => fake()->numberBetween(0, 20),
            'seniority_years' => fake()->numberBetween(0, 10),
            'education' => fake()->randomElement(['Secundario', 'Terciario', 'Universitario', 'Estudiante']),
            'employment_type' => fake()->randomElement(['por horas', 'tiempo completo', 'tiempo parcial', 'solo fines de semana']),
            'current_schedule' => fake()->randomElement(['9-18h', '17-00h', '6-14h']),
            'avatar_path' => null,
        ];
    }
} 