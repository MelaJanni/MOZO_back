<?php

namespace Database\Factories;

use App\Models\AdminProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminProfileFactory extends Factory
{
    protected $model = AdminProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'display_name' => $this->faker->name(),
            'business_name' => $this->faker->company(),
            'position' => $this->faker->jobTitle(),
            'corporate_email' => $this->faker->companyEmail(),
            'corporate_phone' => $this->faker->phoneNumber(),
            'office_extension' => $this->faker->numberBetween(100, 999),
            'bio' => $this->faker->text(200),
            'notify_new_orders' => true,
            'notify_staff_requests' => true,
            'notify_reviews' => true,
            'notify_payments' => true,
            'avatar' => null,
            'last_active_at' => now(),
        ];
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'Manager',
        ]);
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'Owner',
        ]);
    }

    public function withMinimalNotifications(): static
    {
        return $this->state(fn (array $attributes) => [
            'notify_new_orders' => false,
            'notify_staff_requests' => false,
            'notify_reviews' => false,
            'notify_payments' => true,
        ]);
    }
}