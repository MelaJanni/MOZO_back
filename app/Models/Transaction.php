<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'invoice_id',
        'payment_method_id',
        'gateway_transaction_id',
        'gateway_order_id',
        'gateway_reference',
        'amount',
        'amount_cents',
        'currency',
        'gateway_fee',
        'platform_fee',
        'net_amount',
        'status',
        'type',
        'gateway_response',
        'gateway_metadata',
        'customer_email',
        'customer_data',
        'card_last_four',
        'card_brand',
        'card_country',
        'processed_at',
        'failed_at',
        'refunded_at',
        'description',
        'failure_reason',
        'internal_notes',
        'webhooks_received',
        'last_webhook_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_fee' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'gateway_response' => 'array',
        'gateway_metadata' => 'array',
        'customer_data' => 'array',
        'webhooks_received' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'last_webhook_at' => 'datetime',
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->whereIn('status', ['refunded', 'partially_refunded']);
    }

    public function scopePayments($query)
    {
        return $query->where('type', 'payment');
    }

    public function scopeRefunds($query)
    {
        return $query->whereIn('type', ['refund', 'partial_refund']);
    }

    // Métodos de estado
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function isRefunded(): bool
    {
        return in_array($this->status, ['refunded', 'partially_refunded']);
    }

    // Métodos de tipo
    public function isPayment(): bool
    {
        return $this->type === 'payment';
    }

    public function isRefund(): bool
    {
        return in_array($this->type, ['refund', 'partial_refund']);
    }

    public function isChargeback(): bool
    {
        return $this->type === 'chargeback';
    }

    // Métodos de montos
    public function getAmountInCents(): int
    {
        return $this->amount_cents;
    }

    public function getFormattedAmount(): string
    {
        $symbol = $this->currency === 'USD' ? 'USD $' : '$';
        return $symbol . number_format($this->amount, 2);
    }

    public function getFormattedNetAmount(): string
    {
        $symbol = $this->currency === 'USD' ? 'USD $' : '$';
        return $symbol . number_format($this->net_amount, 2);
    }

    public function getTotalFees(): float
    {
        return $this->gateway_fee + $this->platform_fee;
    }

    public function getFormattedTotalFees(): string
    {
        $symbol = $this->currency === 'USD' ? 'USD $' : '$';
        return $symbol . number_format($this->getTotalFees(), 2);
    }

    // Mutators
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = $value;
        $this->attributes['amount_cents'] = (int)($value * 100);
    }

    // Métodos de acciones
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    public function markAsRefunded(): void
    {
        $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    // Métodos de webhooks
    public function addWebhookReceived(array $webhook): void
    {
        $webhooks = $this->webhooks_received ?? [];
        $webhooks[] = array_merge($webhook, ['received_at' => now()]);

        $this->update([
            'webhooks_received' => $webhooks,
            'last_webhook_at' => now(),
        ]);
    }

    public function getLastWebhook(): ?array
    {
        $webhooks = $this->webhooks_received ?? [];
        return end($webhooks) ?: null;
    }

    // Métodos de gateway
    public function isMercadoPago(): bool
    {
        return $this->paymentMethod && $this->paymentMethod->isMercadoPago();
    }

    public function isPayPal(): bool
    {
        return $this->paymentMethod && $this->paymentMethod->isPayPal();
    }

    public function isStripe(): bool
    {
        return $this->paymentMethod && $this->paymentMethod->isStripe();
    }

    // Métodos de información de tarjeta
    public function hasCardInfo(): bool
    {
        return !empty($this->card_last_four) && !empty($this->card_brand);
    }

    public function getCardInfo(): ?string
    {
        if (!$this->hasCardInfo()) {
            return null;
        }

        return ucfirst($this->card_brand) . ' ****' . $this->card_last_four;
    }
}