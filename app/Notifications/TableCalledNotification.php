<?php

namespace App\Notifications;

use App\Models\Table;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class TableCalledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $table;

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast', FcmChannel::class];
    }

    public function toDatabase($notifiable)
    {
        return [
            'table_id' => $this->table->id,
            'table_number' => $this->table->number,
            'business_id' => $this->table->business_id,
            'message' => "La mesa {$this->table->number} requiere atención",
            'type' => 'table_call',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'table_id' => $this->table->id,
            'table_number' => $this->table->number,
            'business_id' => $this->table->business_id,
            'message' => "La mesa {$this->table->number} requiere atención",
            'type' => 'table_call',
        ]);
    }

    public function toFcm($notifiable)
    {
        return FcmMessage::create()
            ->setNotification(
                FcmNotification::create()
                    ->setTitle('Mesa ' . $this->table->number)
                    ->setBody('¡Requiere atención!')
            )
            ->setData([
                'type' => 'table_call',
                'table_id' => (string) $this->table->id,
                'table_number' => (string) $this->table->number,
            ]);
    }
} 