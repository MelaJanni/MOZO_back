<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu>
 */
class MenuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['Almuerzo', 'Cena', 'Brunch', 'Desayuno', 'Bebidas', 'Postres', 'Especiales'];
        
        return [
            'business_id' => Business::factory(),
            'name' => fake()->randomElement($types) . ' ' . fake()->word(),
            'file_path' => 'menus/' . fake()->uuid() . '.pdf',
            'is_default' => fake()->boolean(20),
        ];
    }
    
    /**
     * Indicate that this is the default menu.
     */
    public function asDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
} 