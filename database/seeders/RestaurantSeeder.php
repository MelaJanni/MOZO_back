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
        // Primero intentamos buscar por código QR único
        $existingTable = Table::where('code', 'aVnyOv')->first();
        
        if (!$existingTable) {
            // Buscar un número de mesa disponible para este business
            $availableNumber = 1;
            while (Table::where('number', $availableNumber)->where('business_id', $business->id)->exists()) {
                $availableNumber++;
            }
            
            Table::create([
                'name' => 'Mesa QR McDonald\'s',
                'code' => 'aVnyOv',
                'number' => $availableNumber,
                'restaurant_id' => $restaurant->id,
                'business_id' => $business->id,
                'notifications_enabled' => true
            ]);
        }
    }
}