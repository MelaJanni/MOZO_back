<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TableSilence extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_id',
        'silenced_by',
        'reason',
        'silenced_at',
        'unsilenced_at',
        'call_count',
        'notes'
    ];

    protected $casts = [
        'silenced_at' => 'datetime',
        'unsilenced_at' => 'datetime'
    ];

    // Relationships
    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function silencedBy()
    {
        return $this->belongsTo(User::class, 'silenced_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('unsilenced_at')
                    ->where('silenced_at', '<=', now());
    }

    public function scopeAutomatic($query)
    {
        return $query->where('reason', 'automatic');
    }

    public function scopeManual($query)
    {
        return $query->where('reason', 'manual');
    }

    public function scopeExpired($query)
    {
        return $query->whereNull('unsilenced_at')
                    ->where('reason', 'automatic')
                    ->where('silenced_at', '<=', Carbon::now()->subMinutes(10));
    }

    // Methods
    public function unsilence()
    {
        $this->update([
            'unsilenced_at' => now()
        ]);
    }

    public function isActive()
    {
        if ($this->unsilenced_at) {
            return false;
        }

        // Si es automático, verificar si han pasado 10 minutos
        if ($this->reason === 'automatic') {
            return $this->silenced_at->addMinutes(10)->isFuture();
        }

        // Si es manual, permanece activo hasta que se quite manualmente
        return true;
    }

    public function getRemainingTimeAttribute()
    {
        if (!$this->isActive()) {
            return 0;
        }

        if ($this->reason === 'manual') {
            return null; // Silencio manual no tiene tiempo límite
        }

        $endTime = $this->silenced_at->addMinutes(10);
        return max(0, $endTime->diffInSeconds(now()));
    }

    public function getFormattedRemainingTimeAttribute()
    {
        $seconds = $this->remaining_time;
        
        if ($seconds === null) {
            return 'Silenciado manualmente';
        }

        if ($seconds === 0) {
            return 'Expirado';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return $minutes > 0 
            ? "{$minutes}m {$remainingSeconds}s"
            : "{$seconds}s";
    }

    /**
     * Safe wrapper methods that handle missing table gracefully
     */
    public static function safeWhere(...$args)
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('table_silences')) {
                return collect(); // Return empty collection
            }
            return static::where(...$args);
        } catch (\Exception $e) {
            return collect();
        }
    }

    public static function safeWhereIn(...$args)
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('table_silences')) {
                return collect();
            }
            return static::whereIn(...$args);
        } catch (\Exception $e) {
            return collect();
        }
    }

    public static function safeCreate(array $attributes = [])
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('table_silences')) {
                return null; // Table doesn't exist, return null
            }
            return static::create($attributes);
        } catch (\Exception $e) {
            return null;
        }
    }
}