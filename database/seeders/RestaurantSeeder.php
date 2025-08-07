<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Restaurant;
use App\Models\Table;

class RestaurantSeeder extends Seeder
{
    public function run()
    {
        // Crear restaurante de prueba
        $restaurant = Restaurant::create([
            'name' => "McDonald's",
            'slug' => 'mcdonalds',
            'logo' => null,
            'menu_pdf' => 'menus/mcdonalds-menu.pdf'
        ]);

        // Crear mesa de prueba
        Table::create([
            'name' => 'Mesa 1',
            'code' => 'aVnyOv',
            'number' => 1,
            'restaurant_id' => $restaurant->id,
            'business_id' => 1, // Asumiendo que existe un business con ID 1
            'notifications_enabled' => true
        ]);
    }
}