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
            'code' => strtoupper($this->faker->lexify('PLAN-????')),
            'name' => $this->faker->words(2, true) . ' Plan',
            'description' => $this->faker->sentence(),
            'billing_period' => $this->faker->randomElement(['monthly', 'quarterly', 'yearly']),
            'price_cents' => $this->faker->numberBetween(999, 9999),
            'prices' => [
                'ARS' => $this->faker->numberBetween(9999, 99999),
                'USD' => $this->faker->numberBetween(99, 999),
            ],
            'default_currency' => 'USD',
            'yearly_discount_percentage' => $this->faker->randomFloat(2, 0, 20),
            'quarterly_discount_percentage' => $this->faker->randomFloat(2, 0, 10),
            'trial_days' => $this->faker->numberBetween(7, 30),
            'trial_enabled' => $this->faker->boolean(70),
            'trial_requires_payment_method' => $this->faker->boolean(30),
            'features' => [
                'max_tables' => $this->faker->numberBetween(5, 50),
                'max_staff' => $this->faker->numberBetween(3, 20),
                'support_level' => $this->faker->randomElement(['basic', 'standard', 'premium']),
            ],
            'sort_order' => $this->faker->numberBetween(1, 10),
            'is_featured' => $this->faker->boolean(20),
            'is_popular' => $this->faker->boolean(30),
            'is_active' => true,
            'tax_percentage' => $this->faker->randomElement([0, 10.5, 21]),
            'tax_inclusive' => $this->faker->boolean(),
            'provider_plan_ids' => null,
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
            'price_cents' => 2999,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Yearly Plan',
            'price_cents' => 29999,
        ]);
    }
}