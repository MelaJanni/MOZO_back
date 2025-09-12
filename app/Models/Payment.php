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
}
