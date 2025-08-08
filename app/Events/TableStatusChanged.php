<?php

namespace App\Events;

use App\Models\Table;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TableStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $table;
    public $statusType;
    public $statusData;

    /**
     * Create a new event instance.
     * 
     * @param Table $table
     * @param string $statusType - silenced|unsilenced|waiter_assigned|waiter_unassigned|notifications_toggled
     * @param array $statusData - Additional data about the status change
     */
    public function __construct(Table $table, string $statusType, array $statusData = [])
    {
        $this->table = $table->load(['activeWaiter', 'activeSilence']);
        $this->statusType = $statusType;
        $this->statusData = $statusData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Canal para el mozo asignado (si hay)
            $this->table->active_waiter_id ? 
                new PrivateChannel('waiter.' . $this->table->active_waiter_id) : null,
            // Canal para la mesa
            new Channel('table.' . $this->table->id),
            // Canal para admins del negocio
            new PrivateChannel('business.' . $this->table->business_id)
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'table.status.changed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'table' => [
                'id' => $this->table->id,
                'number' => $this->table->number,
                'name' => $this->table->name,
                'notifications_enabled' => $this->table->notifications_enabled,
                'active_waiter_id' => $this->table->active_waiter_id,
                'active_waiter_name' => $this->table->activeWaiter->name ?? null,
                'is_silenced' => $this->table->isSilenced(),
                'silence_info' => $this->table->activeSilence() ? [
                    'reason' => $this->table->activeSilence()->reason,
                    'remaining_time' => $this->table->activeSilence()->formatted_remaining_time,
                    'notes' => $this->table->activeSilence()->notes
                ] : null
            ],
            'status_type' => $this->statusType,
            'status_data' => $this->statusData,
            'timestamp' => now()->toISOString()
        ];
    }
}