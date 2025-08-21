<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code', 
        'number',
        'business_id',
        'restaurant_id',
        'notifications_enabled',
        'capacity',
        'location',
        'status',
        'active_waiter_id',
        'waiter_assigned_at'
    ];

    protected $casts = [
        'notifications_enabled' => 'boolean',
        'waiter_assigned_at' => 'datetime'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function qrCodes()
    {
        return $this->hasMany(QrCode::class);
    }

    public function qrCode()
    {
        return $this->hasOne(QrCode::class)->latestOfMany();
    }


    public function activeWaiter()
    {
        return $this->belongsTo(User::class, 'active_waiter_id');
    }

    public function waiterCalls()
    {
        return $this->hasMany(WaiterCall::class);
    }

    public function pendingCalls()
    {
        return $this->waiterCalls()->where('status', 'pending');
    }

    public function silences()
    {
        return $this->hasMany(TableSilence::class);
    }

    public function activeSilence()
    {
        return $this->silences()->active();
    }

    public function isSilenced()
    {
        $silence = $this->silences()->active()->first();
        return $silence && $silence->isActive();
    }

    public function canReceiveCalls()
    {
        return $this->notifications_enabled && 
               $this->active_waiter_id && 
               !$this->isSilenced();
    }

    public function assignWaiter(User $waiter)
    {
        $this->update([
            'active_waiter_id' => $waiter->id,
            'waiter_assigned_at' => now()
        ]);
    }

    public function unassignWaiter()
    {
        $this->update([
            'active_waiter_id' => null,
            'waiter_assigned_at' => null
        ]);
    }
} 