<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class IpBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'business_id',
        'blocked_by',
        'reason',
        'notes',
        'blocked_at',
        'expires_at',
        'unblocked_at',
        'metadata'
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
        'expires_at' => 'datetime',
        'unblocked_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function blockedBy()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('unblocked_at')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForIp($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    public function scopeExpired($query)
    {
        return $query->whereNull('unblocked_at')
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
    }

    // Methods
    public function unblock()
    {
        $this->update([
            'unblocked_at' => now()
        ]);
    }

    public function isActive()
    {
        if ($this->unblocked_at) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function getRemainingTimeAttribute()
    {
        if (!$this->isActive() || !$this->expires_at) {
            return null;
        }

        return max(0, $this->expires_at->diffInSeconds(now()));
    }

    public function getFormattedRemainingTimeAttribute()
    {
        $seconds = $this->remaining_time;
        
        if ($seconds === null) {
            return $this->isActive() ? 'Permanente' : 'Expirado';
        }

        if ($seconds === 0) {
            return 'Expirado';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($minutes > 0) {
            return "{$minutes}m {$remainingSeconds}s";
        } else {
            return "{$seconds}s";
        }
    }

    // Static helpers
    public static function isIpBlocked($ipAddress, $businessId)
    {
        return self::where('ip_address', $ipAddress)
                  ->where('business_id', $businessId)
                  ->active()
                  ->exists();
    }

    public static function blockIp($ipAddress, $businessId, $blockedBy, $options = [])
    {
        $defaults = [
            'reason' => 'manual',
            'notes' => null,
            'expires_at' => null,
            'metadata' => []
        ];

        $options = array_merge($defaults, $options);

        return self::create([
            'ip_address' => $ipAddress,
            'business_id' => $businessId,
            'blocked_by' => $blockedBy,
            'reason' => $options['reason'],
            'notes' => $options['notes'],
            'blocked_at' => now(),
            'expires_at' => $options['expires_at'],
            'metadata' => $options['metadata']
        ]);
    }

    public static function unblockIp($ipAddress, $businessId)
    {
        return self::where('ip_address', $ipAddress)
                  ->where('business_id', $businessId)
                  ->active()
                  ->update(['unblocked_at' => now()]);
    }
}