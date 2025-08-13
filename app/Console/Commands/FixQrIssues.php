<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\Table;
use App\Models\User;
use Illuminate\Console\Command;

class FixQrIssues extends Command
{
    protected $signature = 'qr:fix-issues';
    protected $description = 'Fix QR code issues: create missing tables and assign waiters';

    public function handle()
    {
        $this->info('🔧 Iniciando reparación de problemas QR...');
        
        // 1. Verificar que existe el negocio McDonalds
        $mcdonalds = Business::where('name', 'McDonalds')
            ->orWhere('code', 'mcdonalds')
            ->first();
            
        if (!$mcdonalds) {
            $this->error('❌ No se encontró el negocio McDonalds');
            return;
        }
        
        $this->info("✅ Negocio encontrado: {$mcdonalds->name} (ID: {$mcdonalds->id})");
        
        // 2. Crear mesa mDWlbd si no existe
        $tableMDWlbd = Table::where('code', 'mDWlbd')->first();
        if (!$tableMDWlbd) {
            // Buscar el siguiente número de mesa disponible
            $nextNumber = Table::where('business_id', $mcdonalds->id)->max('number') + 1;
            
            $tableMDWlbd = Table::create([
                'business_id' => $mcdonalds->id,
                'number' => $nextNumber,
                'code' => 'mDWlbd',
                'name' => "Mesa {$nextNumber}",
                'capacity' => 4,
                'location' => 'Principal',
                'notifications_enabled' => true,
            ]);
            
            $this->info("✅ Mesa mDWlbd creada: Mesa #{$nextNumber} (ID: {$tableMDWlbd->id})");
        } else {
            $this->info("✅ Mesa mDWlbd ya existe: Mesa #{$tableMDWlbd->number}");
        }
        
        // 3. Verificar mesa JoA4vw
        $tableJoA4vw = Table::where('code', 'JoA4vw')->first();
        if (!$tableJoA4vw) {
            $this->error('❌ Mesa JoA4vw no encontrada');
            return;
        }
        
        $this->info("✅ Mesa JoA4vw encontrada: Mesa #{$tableJoA4vw->number}");
        
        // 4. Verificar/crear mozos de prueba
        $this->info('🔍 Verificando mozos disponibles...');
        
        $waiters = User::where('role', 'waiter')->get();
        if ($waiters->isEmpty()) {
            $this->info('⚠️  No hay mozos registrados, creando mozo de prueba...');
            
            // Crear mozo de prueba
            $testWaiter = User::create([
                'name' => 'Mozo Test',
                'email' => 'mozo.test@mcdonalds.com',
                'password' => bcrypt('password123'),
                'role' => 'waiter',
                'active_business_id' => $mcdonalds->id,
                'phone' => '+5491123456789',
            ]);
            
            // Asociar el mozo al negocio
            $testWaiter->businesses()->attach($mcdonalds->id, [
                'joined_at' => now(),
                'status' => 'active',
                'role' => 'waiter'
            ]);
            
            $this->info("✅ Mozo de prueba creado: {$testWaiter->name} (ID: {$testWaiter->id})");
            $waiters = collect([$testWaiter]);
        }
        
        $availableWaiter = $waiters->first();
        
        // 5. Asignar mozo a las mesas si no lo tienen
        $tables = [$tableMDWlbd, $tableJoA4vw];
        
        foreach ($tables as $table) {
            if (!$table->active_waiter_id) {
                $table->update([
                    'active_waiter_id' => $availableWaiter->id,
                    'waiter_assigned_at' => now(),
                    'notifications_enabled' => true
                ]);
                
                $this->info("✅ Mozo {$availableWaiter->name} asignado a Mesa {$table->code}");
            } else {
                $waiterName = $table->activeWaiter->name ?? 'Desconocido';
                $this->info("✅ Mesa {$table->code} ya tiene mozo: {$waiterName}");
            }
        }
        
        // 6. Mostrar resumen final
        $this->info('');
        $this->info('📋 RESUMEN:');
        $this->info("- Negocio: {$mcdonalds->name}");
        $this->info("- Mesa mDWlbd: Mesa #{$tableMDWlbd->number} - Mozo: " . ($tableMDWlbd->activeWaiter->name ?? 'Sin asignar'));
        $this->info("- Mesa JoA4vw: Mesa #{$tableJoA4vw->number} - Mozo: " . ($tableJoA4vw->activeWaiter->name ?? 'Sin asignar'));
        
        // 7. Mostrar URLs para probar
        $this->info('');
        $this->info('🔗 URLs para probar:');
        $this->info('- https://mozoqr.com/QR/mcdonalds/mDWlbd');
        $this->info('- https://mozoqr.com/QR/mcdonalds/JoA4vw');
        
        $this->info('');
        $this->info('🎉 ¡Reparación completada!');
    }
}