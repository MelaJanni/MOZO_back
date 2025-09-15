<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type', // percent|fixed|free_time
        'value', // ej. 10 para 10% o 1000 para $10.00
        'free_days',
        'max_redemptions',
        'redeemed_count',
        'expires_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'value' => 'integer',
        'free_days' => 'integer',
        'max_redemptions' => 'integer',
        'redeemed_count' => 'integer',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_redemptions && $this->redeemed_count >= $this->max_redemptions) {
            return false;
        }

        return true;
    }

    public function canBeRedeemed(): bool
    {
        return $this->isValid();
    }

    public function redeem(): bool
    {
        if (!$this->canBeRedeemed()) {
            return false;
        }

        $this->increment('redeemed_count');
        return true;
    }

    public function getDiscountDescription(): string
    {
        switch ($this->type) {
            case 'percent':
                return "{$this->value}% de descuento";
            case 'fixed':
                return '$' . number_format($this->value / 100, 2) . ' de descuento';
            case 'free_time':
                return "{$this->free_days} días gratis";
            default:
                return 'Descuento aplicado';
        }
    }
}
