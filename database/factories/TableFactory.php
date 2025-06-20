<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Table>
 */
class TableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numberBetween(1, 50),
            'business_id' => Business::factory(),
            'notifications_enabled' => fake()->boolean(70),
        ];
    }
    
    /**
     * Indicate that notifications are enabled for this table.
     */
    public function withNotifications(): static
    {
        return $this->state(fn (array $attributes) => [
            'notifications_enabled' => true,
        ]);
    }
    
    /**
     * Indicate that notifications are disabled for this table.
     */
    public function withoutNotifications(): static
    {
        return $this->state(fn (array $attributes) => [
            'notifications_enabled' => false,
        ]);
    }
} 