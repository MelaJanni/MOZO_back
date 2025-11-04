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
        'billing_period',
        'price_at_creation',
        'currency',
        'auto_renew',
        'current_period_end',
        'next_billing_date',
        'trial_ends_at',
        'coupon_id',
        'metadata',
        'grace_ends_at',
        'requires_plan_selection',
    ];

    protected $attributes = [
        'provider' => 'manual',
        'status' => 'active',
        'auto_renew' => false,
    ];

    protected $casts = [
        'price_at_creation' => 'decimal:2',
        'auto_renew' => 'boolean',
        'current_period_end' => 'datetime',
        'next_billing_date' => 'datetime',
        'trial_ends_at' => 'datetime',
        'grace_ends_at' => 'datetime',
        'requires_plan_selection' => 'boolean',
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

    // Métodos para período de gracia cuando el plan se desactiva
    public function isInGracePeriod(): bool
    {
        return $this->status === 'grace_period' &&
               $this->grace_ends_at &&
               $this->grace_ends_at->isFuture();
    }

    public function getGraceDaysRemaining(): ?int
    {
        if ($this->isInGracePeriod() && $this->grace_ends_at) {
            return now()->diffInDays($this->grace_ends_at, false);
        }
        return null;
    }

    public function enterGracePeriod(int $graceDays = 7): void
    {
        $this->update([
            'status' => 'grace_period',
            'grace_ends_at' => now()->addDays($graceDays),
            'requires_plan_selection' => true,
        ]);
    }

    public function hasInactivePlan(): bool
    {
        return $this->plan && !$this->plan->is_active;
    }

    public function shouldEnterGracePeriod(): bool
    {
        return $this->isActive() &&
               $this->hasInactivePlan() &&
               $this->current_period_end &&
               $this->current_period_end->isPast();
    }
}
