<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true) . ' Plan',
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 9.99, 99.99),
            'duration_days' => $this->faker->randomElement([30, 90, 365]),
            'features' => ['feature_' . $this->faker->word(), 'feature_' . $this->faker->word()],
            'limits' => [
                'max_businesses' => $this->faker->numberBetween(1, 10),
                'max_tables' => $this->faker->numberBetween(10, 100),
            ],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Monthly Plan',
            'price' => 29.99,
            'duration_days' => 30,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Yearly Plan',
            'price' => 299.99,
            'duration_days' => 365,
        ]);
    }
}