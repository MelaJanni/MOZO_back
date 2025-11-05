<?php

namespace Database\Factories;

use App\Models\WaiterProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WaiterProfileFactory extends Factory
{
    protected $model = WaiterProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'display_name' => $this->faker->name(),
            'bio' => $this->faker->text(200),
            'phone' => $this->faker->phoneNumber(),
            'birth_date' => $this->faker->dateTimeBetween('-50 years', '-18 years'),
            'height' => $this->faker->randomFloat(2, 1.50, 2.00),
            'weight' => $this->faker->numberBetween(50, 120),
            'gender' => $this->faker->randomElement(['masculino', 'femenino', 'otro']),
            'experience_years' => $this->faker->numberBetween(0, 20),
            'employment_type' => $this->faker->randomElement(['employee', 'freelancer', 'contractor']),
            'current_schedule' => $this->faker->randomElement(['morning', 'afternoon', 'night', 'mixed']),
            'current_location' => $this->faker->city(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'availability_hours' => ['09:00-17:00', '18:00-23:00'],
            'skills' => ['servicio_cliente', 'organizacion', 'trabajo_equipo'],
            // Removed 'is_active' - column does not exist in schema
            'is_available' => true,
            'is_available_for_hire' => true,
            'rating' => $this->faker->randomFloat(1, 3.0, 5.0),
            'total_reviews' => $this->faker->numberBetween(0, 100),
            'avatar' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            // Removed 'is_active' reference
            'is_available' => false,
            'is_available_for_hire' => false,
        ]);
    }

    public function experienced(): static
    {
        return $this->state(fn (array $attributes) => [
            'experience_years' => $this->faker->numberBetween(5, 15),
            'rating' => $this->faker->randomFloat(1, 4.0, 5.0),
            'total_reviews' => $this->faker->numberBetween(50, 200),
        ]);
    }

    public function newbie(): static
    {
        return $this->state(fn (array $attributes) => [
            'experience_years' => $this->faker->numberBetween(0, 2),
            'rating' => $this->faker->randomFloat(1, 3.0, 4.5),
            'total_reviews' => $this->faker->numberBetween(0, 20),
        ]);
    }
}