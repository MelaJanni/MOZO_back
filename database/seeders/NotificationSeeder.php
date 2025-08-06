<?php

namespace Database\Seeders;

use App\Models\Table;
use App\Models\User;
use App\Models\WaiterCall;
use App\Notifications\FcmDatabaseNotification;
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
                // Obtener mesas del negocio del mozo que tienen activo este mozo
                $tables = Table::where('business_id', $waiter->business_id)
                    ->where('notifications_enabled', true)
                    ->where('active_waiter_id', $waiter->id)
                    ->get();
                
                if ($tables->count() > 0) {
                    // Generar entre 3 y 7 notificaciones por mozo
                    $notificationCount = rand(3, 7);
                    
                    for ($i = 0; $i < $notificationCount; $i++) {
                        // Seleccionar una mesa aleatoria
                        $table = $tables->random();
                        
                        // Crear una llamada de muestra
                        $call = WaiterCall::create([
                            'table_id' => $table->id,
                            'waiter_id' => $waiter->id,
                            'status' => rand(0, 1) ? 'pending' : (rand(0, 1) ? 'acknowledged' : 'completed'),
                            'message' => 'Llamada desde mesa ' . $table->number,
                            'called_at' => now()->subMinutes(rand(5, 60)),
                            'metadata' => [
                                'urgency' => rand(0, 1) ? 'normal' : 'high',
                                'ip_address' => '192.168.1.' . rand(1, 255)
                            ]
                        ]);
                        
                        // Crear datos de notificación FCM
                        $title = "🔔 Llamada de Mesa {$table->number}";
                        $body = $call->message;
                        $data = [
                            'type' => 'waiter_call',
                            'call_id' => (string)$call->id,
                            'table_id' => (string)$table->id,
                            'table_number' => (string)$table->number,
                            'urgency' => $call->metadata['urgency'] ?? 'normal',
                            'action' => 'acknowledge_call'
                        ];
                        
                        // Notificar al mozo
                        $waiter->notify(new FcmDatabaseNotification($title, $body, $data));
                        
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