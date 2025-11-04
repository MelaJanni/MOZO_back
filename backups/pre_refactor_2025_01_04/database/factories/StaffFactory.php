<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Staff>
 */
class StaffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $positions = ['Mozo', 'Bartender', 'Cocinero', 'Recepcionista', 'Gerente', 'Ayudante de cocina', 'Limpieza'];
        $status = ['active', 'on_leave', 'part_time'];
        
        $birthDate = fake()->dateTimeBetween('-50 years', '-18 years');

        return [
            'business_id' => Business::factory(),
            'name' => fake()->name(),
            'position' => fake()->randomElement($positions),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'hire_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'salary' => fake()->numberBetween(30000, 150000),
            'status' => fake()->randomElement($status),
            'notes' => fake()->optional(0.7)->paragraph(),
            'birth_date' => $birthDate,
            'height' => fake()->randomFloat(2, 1.50, 1.95),
            'weight' => fake()->randomFloat(2, 50, 100),
            'gender' => fake()->randomElement(['male', 'female']),
            'experience_years' => fake()->numberBetween(0, 20),
            'seniority_years' => fake()->numberBetween(0, 10),
            'education' => fake()->randomElement(['Secundario', 'Terciario', 'Universitario', 'Estudiante']),
            'employment_type' => fake()->randomElement(['por horas', 'tiempo completo', 'tiempo parcial', 'solo fines de semana']),
            'current_schedule' => fake()->randomElement(['9-18h', '17-00h', '6-14h']),
            'avatar_path' => null,
        ];
    }
    
    /**
     * Indicate that the staff is a waiter.
     */
    public function waiter(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'Mozo',
        ]);
    }
    
    /**
     * Indicate that the staff is a chef.
     */
    public function chef(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'Cocinero',
        ]);
    }
} 