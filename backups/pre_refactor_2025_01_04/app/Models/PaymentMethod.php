<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'config',
        'fees',
        'supported_currencies',
        'is_enabled',
        'is_test_mode',
        'sort_order',
        'min_amount',
        'max_amount',
        'webhook_url',
        'webhook_secret',
        'logo_url',
        'color_primary',
        'color_secondary',
    ];

    protected $casts = [
        'config' => 'array',
        'fees' => 'array',
        'supported_currencies' => 'array',
        'is_enabled' => 'boolean',
        'is_test_mode' => 'boolean',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
    ];

    // Relaciones
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Scopes
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Métodos de utilidad
    public function isAvailable(): bool
    {
        return $this->is_enabled;
    }

    public function supportsCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->supported_currencies ?? []);
    }

    public function isAmountValid(float $amount): bool
    {
        if ($this->min_amount && $amount < $this->min_amount) {
            return false;
        }

        if ($this->max_amount && $amount > $this->max_amount) {
            return false;
        }

        return true;
    }

    public function calculateFees(float $amount): array
    {
        $fees = $this->fees ?? [];
        $fixedFee = $fees['fixed'] ?? 0;
        $percentageFee = ($fees['percentage'] ?? 0) / 100;

        $calculatedFee = $fixedFee + ($amount * $percentageFee);

        return [
            'fixed' => $fixedFee,
            'percentage' => $fees['percentage'] ?? 0,
            'calculated' => round($calculatedFee, 2),
            'total_amount' => round($amount + $calculatedFee, 2),
        ];
    }

    public function getApiCredentials(): array
    {
        $config = $this->config ?? [];

        if ($this->is_test_mode) {
            return [
                'access_token' => $config['test_access_token'] ?? null,
                'public_key' => $config['test_public_key'] ?? null,
                'client_id' => $config['test_client_id'] ?? null,
                'client_secret' => $config['test_client_secret'] ?? null,
            ];
        }

        return [
            'access_token' => $config['access_token'] ?? null,
            'public_key' => $config['public_key'] ?? null,
            'client_id' => $config['client_id'] ?? null,
            'client_secret' => $config['client_secret'] ?? null,
        ];
    }

    public function isMercadoPago(): bool
    {
        return $this->slug === 'mercadopago';
    }

    public function isPayPal(): bool
    {
        return $this->slug === 'paypal';
    }

    public function isStripe(): bool
    {
        return $this->slug === 'stripe';
    }
}