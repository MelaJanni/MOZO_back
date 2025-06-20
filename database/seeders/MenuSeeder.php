<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los negocios
        $businesses = Business::all();
        
        if ($businesses->isEmpty()) {
            // Si no hay negocios, crear uno para los seeders
            $business = Business::create([
                'name' => 'Restaurant Demo',
                'address' => 'Av. Corrientes 1234, CABA',
                'phone' => '+5491123456789',
                'email' => 'info@restaurantdemo.com',
            ]);
            
            $businesses = collect([$business]);
        }
        
        // Categorías para los menús
        $categories = ['Desayuno', 'Almuerzo', 'Cena', 'Vegetariano', 'Vegano', 'Sin TACC', 'Parrilla', 'Cocina Internacional'];
        
        // Para cada negocio, crear menús
        foreach ($businesses as $business) {
            // Crear 5 menús para cada negocio
            for ($i = 1; $i <= 5; $i++) {
                $category = $categories[array_rand($categories)];
                $menuName = 'Menú ' . $category;
                
                // Verificar si ya existe un menú con este nombre para este negocio
                if (!Menu::where('business_id', $business->id)->where('name', $menuName)->exists()) {
                    // Al menos un menú será el predeterminado
                    $isDefault = ($i === 1) ? true : (rand(0, 10) > 8);
                    
                    // Crear el menú
                    Menu::create([
                        'business_id' => $business->id,
                        'name' => $menuName,
                        'file_path' => 'menus/' . $business->id . '/menu_' . $i . '_' . $category . '.pdf',
                        'is_default' => $isDefault,
                        'category' => $category,
                        'description' => 'Descripción del menú ' . $category . ' ' . $i,
                    ]);
                }
            }
        }
    }
} 