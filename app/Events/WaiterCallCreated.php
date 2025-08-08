<?php

namespace App\Events;

use App\Models\WaiterCall;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaiterCallCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $call;

    public function __construct(WaiterCall $call)
    {
        $this->call = $call->load(['table', 'waiter']);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Canal para el mozo que recibe la llamada
            new PrivateChannel('waiter.' . $this->call->waiter_id),
            // Canal para la mesa que hizo la llamada
            new Channel('table.' . $this->call->table->id),
            // Canal para admins del negocio
            new PrivateChannel('business.' . $this->call->table->business_id)
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'waiter.call.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'call' => [
                'id' => $this->call->id,
                'table_id' => $this->call->table->id,
                'table_number' => $this->call->table->number,
                'table_name' => $this->call->table->name,
                'waiter_id' => $this->call->waiter_id,
                'waiter_name' => $this->call->waiter->name ?? 'Mozo',
                'message' => $this->call->message,
                'status' => $this->call->status,
                'called_at' => $this->call->called_at->toISOString(),
                'urgency' => $this->call->metadata['urgency'] ?? 'normal'
            ],
            'timestamp' => now()->toISOString()
        ];
    }
}