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

class WaiterCallAcknowledged implements ShouldBroadcast
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
            // Canal público para la mesa (para que vean "mozo llamado")
            new Channel('table.' . $this->call->table->id),
            // Canal privado para admins
            new PrivateChannel('business.' . $this->call->table->business_id)
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'waiter.call.acknowledged';
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
                'waiter_id' => $this->call->waiter_id,
                'waiter_name' => $this->call->waiter->name ?? 'Mozo',
                'status' => 'acknowledged',
                'acknowledged_at' => $this->call->acknowledged_at->toISOString(),
                'response_time_seconds' => $this->call->called_at->diffInSeconds($this->call->acknowledged_at),
                'message' => 'El mozo confirmó tu llamada y está en camino'
            ],
            'timestamp' => now()->toISOString()
        ];
    }
}