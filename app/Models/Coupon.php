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
}
