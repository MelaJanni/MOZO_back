<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WaiterCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_id',
        'waiter_id', 
        'status',
        'message',
        'called_at',
        'acknowledged_at',
        'completed_at',
        'metadata'
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'acknowledged_at' => 'datetime', 
        'completed_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Relationships
    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function waiter()
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForWaiter($query, $waiterId)
    {
        return $query->where('waiter_id', $waiterId);
    }

    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('called_at', '>=', Carbon::now()->subMinutes($minutes));
    }

    // Methods
    public function acknowledge()
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now()
        ]);
    }

    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    public function cancel()
    {
        $this->update([
            'status' => 'cancelled'
        ]);
    }

    public function getResponseTimeAttribute()
    {
        if (!$this->acknowledged_at) {
            return null;
        }
        
        return $this->called_at->diffInSeconds($this->acknowledged_at);
    }

    public function getFormattedResponseTimeAttribute()
    {
        $seconds = $this->response_time;
        if (!$seconds) {
            return 'Sin respuesta';
        }

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return $remainingSeconds > 0 
            ? "{$minutes}m {$remainingSeconds}s"
            : "{$minutes}m";
    }
}