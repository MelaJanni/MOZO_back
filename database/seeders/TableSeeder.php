<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Table;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
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
        
        // Posibles ubicaciones para las mesas
        $locations = ['Salón principal', 'Terraza', 'Balcón', 'VIP', 'Jardín', 'Bar', 'Entrada'];
        
        // Estados posibles para las mesas
        $statuses = ['available', 'occupied', 'reserved', 'maintenance'];
        
        // Para cada negocio, crear mesas
        foreach ($businesses as $business) {
            // Crear 15 mesas para cada negocio
            for ($i = 1; $i <= 15; $i++) {
                // Verificar si ya existe una mesa con este número para este negocio
                if (!Table::where('business_id', $business->id)->where('number', $i)->exists()) {
                    Table::create([
                        'business_id' => $business->id,
                        'number' => $i,
                        'capacity' => rand(2, 10), // Capacidad aleatoria entre 2 y 10 personas
                        'location' => $locations[array_rand($locations)],
                        'status' => $statuses[array_rand($statuses)],
                        'notifications_enabled' => rand(0, 1), // Notificaciones habilitadas o no
                    ]);
                }
            }
        }
    }
} 