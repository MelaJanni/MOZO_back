<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $profileTypes = ['Mañana', 'Tarde', 'Noche', 'Fin de semana', 'Premium', 'Exterior', 'Interior', 'Terraza'];
        
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement($profileTypes) . ' ' . fake()->word(),
        ];
    }
} 