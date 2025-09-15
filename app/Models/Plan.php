<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'interval',
        'price_cents',
        'currency',
        'trial_days',
        'is_active',
        'metadata',
        'provider_plan_ids',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'provider_plan_ids' => 'array',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price_cents / 100, 2);
    }

    public function isMonthly(): bool
    {
        return $this->interval === 'monthly';
    }

    public function isYearly(): bool
    {
        return $this->interval === 'yearly';
    }

    public function getDiscountedPrice(Coupon $coupon): int
    {
        if ($coupon->type === 'percent') {
            $discount = ($this->price_cents * $coupon->value) / 100;
            return max(0, $this->price_cents - (int) $discount);
        }

        if ($coupon->type === 'fixed') {
            return max(0, $this->price_cents - $coupon->value);
        }

        // For free_time coupons, price remains the same
        return $this->price_cents;
    }
}
