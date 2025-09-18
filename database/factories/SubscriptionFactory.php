<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'provider' => 'manual',
            'provider_subscription_id' => null,
            'status' => 'active',
            'auto_renew' => $this->faker->boolean(),
            'current_period_end' => now()->addDays(30),
            'trial_ends_at' => null,
            'coupon_id' => null,
            'metadata' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'current_period_end' => now()->addDays(30),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'canceled',
            'current_period_end' => now()->subDays(5),
        ]);
    }

    public function inTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_trial',
            'trial_ends_at' => now()->addDays(7),
            'current_period_end' => now()->addDays(37),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'canceled',
        ]);
    }
}