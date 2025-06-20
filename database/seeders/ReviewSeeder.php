<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Review;
use App\Models\Table;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $business = Business::where('code', 'TEST123')->first();
        
        if ($business) {
            $tables = Table::where('business_id', $business->id)->get();
            
            if ($tables->count() > 0) {
                // Distribuir reseñas entre las mesas disponibles
                foreach ($tables as $table) {
                    // Crear 2-5 reseñas por mesa
                    $count = rand(2, 5);
                    
                    // Reseñas positivas (70%)
                    Review::factory()->positive()->count(ceil($count * 0.7))->create([
                        'business_id' => $business->id,
                        'table_id' => $table->id,
                        'is_approved' => true,
                    ]);
                    
                    // Reseñas negativas (30%)
                    Review::factory()->negative()->count(floor($count * 0.3))->create([
                        'business_id' => $business->id,
                        'table_id' => $table->id,
                        'is_approved' => fake()->boolean(70),
                    ]);
                }
                
                // Crear algunas reseñas destacadas
                Review::factory()->featured()->positive()->count(3)->create([
                    'business_id' => $business->id,
                    'table_id' => $tables->random()->id,
                    'comment' => 'Excelente servicio y comida. Definitivamente regresaré.',
                ]);
            }
        }
    }
} 