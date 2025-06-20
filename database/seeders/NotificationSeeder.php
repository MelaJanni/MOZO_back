<?php

namespace Database\Seeders;

use App\Models\Table;
use App\Models\User;
use App\Notifications\TableCalledNotification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener mozos
        $waiters = User::where('role', 'waiter')->get();
        
        if ($waiters->count() > 0) {
            foreach ($waiters as $waiter) {
                // Obtener mesas del negocio del mozo
                $tables = Table::where('business_id', $waiter->business_id)
                    ->where('notifications_enabled', true)
                    ->get();
                
                if ($tables->count() > 0) {
                    // Generar entre 3 y 7 notificaciones por mozo
                    $notificationCount = rand(3, 7);
                    
                    for ($i = 0; $i < $notificationCount; $i++) {
                        // Seleccionar una mesa aleatoria
                        $table = $tables->random();
                        
                        // Notificar al mozo
                        $waiter->notify(new TableCalledNotification($table));
                        
                        // Marcar algunas como leídas
                        if (rand(0, 1)) {
                            $notification = $waiter->notifications()->latest()->first();
                            if ($notification) {
                                $notification->markAsRead();
                            }
                        }
                    }
                }
            }
        }
    }
} 