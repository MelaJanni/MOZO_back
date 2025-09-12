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
}
