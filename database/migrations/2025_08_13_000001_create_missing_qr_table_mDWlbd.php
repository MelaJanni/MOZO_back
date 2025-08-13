<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Business;
use App\Models\Table;
use App\Models\User;

return new class extends Migration
{
    public function up()
    {
        // Verificar si la mesa mDWlbd ya existe
        $existingTable = Table::where('code', 'mDWlbd')->first();
        
        if ($existingTable) {
            echo "✅ Mesa mDWlbd ya existe (ID: {$existingTable->id})\n";
            return;
        }

        // Buscar el negocio McDonalds
        $mcdonalds = Business::where('name', 'McDonalds')
            ->orWhere('code', 'mcdonalds')
            ->first();
            
        if (!$mcdonalds) {
            echo "❌ No se encontró el negocio McDonalds\n";
            return;
        }

        echo "✅ Negocio encontrado: {$mcdonalds->name} (ID: {$mcdonalds->id})\n";

        // Buscar el siguiente número de mesa disponible
        $nextNumber = Table::where('business_id', $mcdonalds->id)->max('number') + 1;

        // Crear la mesa mDWlbd
        $table = Table::create([
            'business_id' => $mcdonalds->id,
            'number' => $nextNumber,
            'code' => 'mDWlbd',
            'name' => "Mesa {$nextNumber}",
            'capacity' => 4,
            'location' => 'Principal',
            'notifications_enabled' => true,
        ]);

        echo "✅ Mesa mDWlbd creada: Mesa #{$nextNumber} (ID: {$table->id})\n";

        // Buscar un mozo disponible para asignar
        $waiter = User::where('role', 'waiter')
            ->whereHas('businesses', function($query) use ($mcdonalds) {
                $query->where('businesses.id', $mcdonalds->id);
            })
            ->first();

        // Si no hay mozos, crear uno de prueba
        if (!$waiter) {
            $waiter = User::create([
                'name' => 'Mozo Test McDonalds',
                'email' => 'mozo.test@mcdonalds.com',
                'password' => bcrypt('password123'),
                'role' => 'waiter',
                'active_business_id' => $mcdonalds->id,
                'phone' => '+5491123456789',
            ]);

            // Asociar el mozo al negocio
            $waiter->businesses()->attach($mcdonalds->id, [
                'joined_at' => now(),
                'status' => 'active',
                'role' => 'waiter'
            ]);

            echo "✅ Mozo de prueba creado: {$waiter->name} (ID: {$waiter->id})\n";
        }

        // Asignar el mozo a la mesa
        $table->update([
            'active_waiter_id' => $waiter->id,
            'waiter_assigned_at' => now(),
        ]);

        echo "✅ Mozo {$waiter->name} asignado a Mesa {$table->code}\n";
        echo "🔗 URL para probar: https://mozoqr.com/QR/mcdonalds/mDWlbd\n";
        
        // Verificar también la mesa JoA4vw
        $tableJoA4vw = Table::where('code', 'JoA4vw')->first();
        if ($tableJoA4vw && !$tableJoA4vw->active_waiter_id) {
            $tableJoA4vw->update([
                'active_waiter_id' => $waiter->id,
                'waiter_assigned_at' => now(),
            ]);
            echo "✅ Mozo {$waiter->name} también asignado a Mesa JoA4vw\n";
        }
    }

    public function down()
    {
        // Eliminar la mesa mDWlbd si existe
        $table = Table::where('code', 'mDWlbd')->first();
        if ($table) {
            $table->delete();
            echo "❌ Mesa mDWlbd eliminada\n";
        }
    }
};