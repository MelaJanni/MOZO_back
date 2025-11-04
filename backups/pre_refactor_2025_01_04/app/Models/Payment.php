<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'user_id',
        'provider',
        'provider_payment_id',
        'amount_cents',
        'currency',
        'status',
        'paid_at',
        'failure_reason',
        'retry_count',
        'next_retry_at',
        'raw_payload',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'retry_count' => 'integer',
        'raw_payload' => 'array',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Métodos de utilidad
    public function getAmount(): float
    {
        return $this->amount_cents / 100;
    }

    public function getFormattedAmount(): string
    {
        $symbol = $this->currency === 'USD' ? 'USD $' : '$';
        return $symbol . number_format($this->getAmount(), 2);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
