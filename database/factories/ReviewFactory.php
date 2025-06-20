<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Table;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'table_id' => Table::factory(),
            'customer_name' => fake()->optional(0.8)->name(),
            'customer_email' => fake()->optional(0.6)->safeEmail(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional(0.9)->paragraph(),
            'service_details' => $this->generateServiceDetails(),
            'is_approved' => fake()->boolean(80),
            'is_featured' => fake()->boolean(20),
        ];
    }
    
    /**
     * Generate detailed service ratings
     */
    protected function generateServiceDetails(): array
    {
        return [
            'food' => fake()->numberBetween(1, 5),
            'service' => fake()->numberBetween(1, 5),
            'ambiance' => fake()->numberBetween(1, 5),
            'value' => fake()->numberBetween(1, 5),
            'cleanliness' => fake()->numberBetween(1, 5),
        ];
    }
    
    /**
     * Indicate that the review is approved and featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
            'is_featured' => true,
        ]);
    }
    
    /**
     * Indicate that the review has a high rating.
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->numberBetween(4, 5),
            'service_details' => [
                'food' => fake()->numberBetween(4, 5),
                'service' => fake()->numberBetween(4, 5),
                'ambiance' => fake()->numberBetween(3, 5),
                'value' => fake()->numberBetween(3, 5),
                'cleanliness' => fake()->numberBetween(4, 5),
            ],
        ]);
    }
    
    /**
     * Indicate that the review has a low rating.
     */
    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->numberBetween(1, 2),
            'service_details' => [
                'food' => fake()->numberBetween(1, 3),
                'service' => fake()->numberBetween(1, 3),
                'ambiance' => fake()->numberBetween(1, 3),
                'value' => fake()->numberBetween(1, 2),
                'cleanliness' => fake()->numberBetween(1, 3),
            ],
        ]);
    }
} 