<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Restaurant;
use App\Models\Table;
use App\Models\Business;

class RestaurantSeeder extends Seeder
{
    public function run()
    {
        // Crear restaurante de prueba
        $restaurant = Restaurant::updateOrCreate(
            ['slug' => 'mcdonalds'],
            [
                'name' => "McDonald's",
                'slug' => 'mcdonalds',
                'logo' => null,
                'menu_pdf' => 'menus/mcdonalds-menu.pdf'
            ]
        );

        // Buscar el primer business o crearlo si no existe
        $business = Business::first();
        if (!$business) {
            $business = Business::create([
                'name' => 'Restaurante Demo',
                'description' => 'Business de prueba para QR system',
                'phone' => '+1234567890',
                'email' => 'demo@restaurant.com'
            ]);
        }

        // Crear mesa de prueba solo si no existe
        Table::updateOrCreate(
            ['code' => 'aVnyOv', 'restaurant_id' => $restaurant->id],
            [
                'name' => 'Mesa 1',
                'code' => 'aVnyOv',
                'number' => 1,
                'restaurant_id' => $restaurant->id,
                'business_id' => $business->id,
                'notifications_enabled' => true
            ]
        );
    }
}