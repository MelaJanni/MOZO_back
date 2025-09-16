<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'provider',
        'provider_subscription_id',
        'status',
        'auto_renew',
        'current_period_end',
        'trial_ends_at',
        'coupon_id',
        'metadata',
    ];

    protected $attributes = [
        'provider' => 'manual',
        'status' => 'active',
        'auto_renew' => false,
    ];

    protected $casts = [
        'auto_renew' => 'boolean',
        'current_period_end' => 'datetime',
        'trial_ends_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isInTrial(): bool
    {
        return $this->status === 'in_trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function isExpired(): bool
    {
        if ($this->isInTrial()) {
            return $this->trial_ends_at->isPast();
        }

        if ($this->isActive()) {
            $graceEndDate = $this->current_period_end
                ? $this->current_period_end->addDays(config('billing.grace_days', 0))
                : null;
            return $graceEndDate && $graceEndDate->isPast();
        }

        return true;
    }

    public function getDaysRemaining(): ?int
    {
        if ($this->isInTrial()) {
            return $this->trial_ends_at ? now()->diffInDays($this->trial_ends_at, false) : null;
        }

        if ($this->isActive() && $this->current_period_end) {
            $graceEndDate = $this->current_period_end->addDays(config('billing.grace_days', 0));
            return now()->diffInDays($graceEndDate, false);
        }

        return null;
    }
}
