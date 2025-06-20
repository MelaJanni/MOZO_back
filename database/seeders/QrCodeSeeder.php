<?php

namespace Database\Seeders;

use App\Models\QrCode;
use App\Models\Table;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QrCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todas las mesas
        $tables = Table::all();
        
        // Para cada mesa, crear un código QR si no existe
        foreach ($tables as $table) {
            // Verificar si ya existe un código QR para esta mesa
            if (!QrCode::where('table_id', $table->id)->exists()) {
                // URL para el código QR
                $url = 'https://mozo.app/table/' . $table->id . '?business=' . $table->business_id;
                
                // Crear un código QR para la mesa
                QrCode::create([
                    'table_id' => $table->id,
                    'business_id' => $table->business_id,
                    'path' => 'qrcodes/' . $table->business_id . '/table_' . $table->id . '.png',
                    'url' => $url,
                    'code_data' => $url, // Usar la misma URL como datos del código
                    'notes' => rand(0, 1) ? 'Notas para código QR de mesa ' . $table->number : null,
                ]);
            }
        }
    }
} 