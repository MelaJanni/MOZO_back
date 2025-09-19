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
        'description',
        'billing_period',
        'price_cents',
        'prices',
        'default_currency',
        'yearly_discount_percentage',
        'quarterly_discount_percentage',
        'trial_days',
        'trial_enabled',
        'trial_requires_payment_method',
        'limits',
        'features',
        'sort_order',
        'is_featured',
        'is_popular',
        'is_active',
        'tax_percentage',
        'tax_inclusive',
        'metadata',
        'provider_plan_ids',
    ];

    protected $casts = [
        'prices' => 'array',
        'yearly_discount_percentage' => 'decimal:2',
        'quarterly_discount_percentage' => 'decimal:2',
        'trial_enabled' => 'boolean',
        'trial_requires_payment_method' => 'boolean',
        'limits' => 'array',
        'features' => 'array',
        'is_featured' => 'boolean',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'tax_percentage' => 'decimal:2',
        'tax_inclusive' => 'boolean',
        'metadata' => 'array',
        'provider_plan_ids' => 'array',
    ];

    // Relaciones
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices()
    {
        return $this->hasManyThrough(Invoice::class, Subscription::class);
    }

    // Métodos de precios mejorados
    public function getPrice($currency = null): float
    {
        $currency = $currency ?? $this->default_currency ?? 'ARS';
        $currency = strtoupper($currency);

        return $this->prices[$currency] ?? $this->prices['ARS'] ?? 0;
    }

    public function getFormattedPrice($currency = null): string
    {
        $currency = $currency ?? $this->default_currency ?? 'ARS';
        $price = $this->getPrice($currency);
        $symbol = $currency === 'USD' ? '$' : '$';
        return $symbol . number_format($price, 0, ',', '.');
    }

    public function getAvailableCurrencies(): array
    {
        return array_keys($this->prices ?? []);
    }

    public function hasPriceForCurrency($currency): bool
    {
        return isset($this->prices[strtoupper($currency)]);
    }

    public function addPrice($currency, $price): void
    {
        $prices = $this->prices ?? [];
        $prices[strtoupper($currency)] = (float) $price;
        $this->prices = $prices;
    }

    public function removePrice($currency): void
    {
        $prices = $this->prices ?? [];
        unset($prices[strtoupper($currency)]);
        $this->prices = $prices;
    }

    public function getPriceWithDiscount($period = null, $currency = 'ARS'): float
    {
        $basePrice = $this->getPrice($currency);

        if ($period === 'yearly' && $this->yearly_discount_percentage > 0) {
            return $basePrice * (1 - ($this->yearly_discount_percentage / 100));
        }

        if ($period === 'quarterly' && $this->quarterly_discount_percentage > 0) {
            return $basePrice * (1 - ($this->quarterly_discount_percentage / 100));
        }

        return $basePrice;
    }

    // Métodos de período
    public function isMonthly(): bool
    {
        return $this->billing_period === 'monthly';
    }

    public function isQuarterly(): bool
    {
        return $this->billing_period === 'quarterly';
    }

    public function isYearly(): bool
    {
        return $this->billing_period === 'yearly';
    }

    // Métodos de límites y features
    public function getLimit($key, $default = null)
    {
        return $this->limits[$key] ?? $default;
    }

    public function hasFeature($feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function getMaxBusinesses(): int
    {
        return $this->getLimit('max_businesses', 1);
    }

    public function getMaxTables(): int
    {
        return $this->getLimit('max_tables', 10);
    }

    public function getMaxStaff(): int
    {
        return $this->getLimit('max_staff', 5);
    }

    // Métodos de trial
    public function hasTrialEnabled(): bool
    {
        return $this->trial_enabled && $this->trial_days > 0;
    }

    public function getTrialDays(): int
    {
        return $this->hasTrialEnabled() ? $this->trial_days : 0;
    }

    // Métodos de cupones (mejorado)
    public function getDiscountedPrice(Coupon $coupon, $currency = 'ARS'): float
    {
        $basePrice = $this->getPrice($currency);

        if ($coupon->type === 'percentage') {
            $discount = ($basePrice * $coupon->value) / 100;
            return max(0, $basePrice - $discount);
        }

        if ($coupon->type === 'fixed') {
            // Convertir el descuento a la moneda correcta si es necesario
            $discountAmount = $coupon->currency === $currency ? $coupon->value : $this->convertCurrency($coupon->value, $coupon->currency, $currency);
            return max(0, $basePrice - $discountAmount);
        }

        return $basePrice;
    }

    // Método de conversión de moneda (placeholder)
    private function convertCurrency($amount, $from, $to): float
    {
        // TODO: Implementar conversión real de moneda
        if ($from === 'USD' && $to === 'ARS') {
            return $amount * 350; // Tasa de cambio placeholder
        }
        if ($from === 'ARS' && $to === 'USD') {
            return $amount / 350;
        }
        return $amount;
    }

    // Scopes para consultas
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    // Métodos para gestión de eliminación segura
    public function hasActiveSubscriptions(): bool
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trialing', 'past_due'])
            ->exists();
    }

    public function getActiveSubscriptionsCount(): int
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trialing', 'past_due'])
            ->count();
    }

    public function canBeDeleted(): bool
    {
        return !$this->hasActiveSubscriptions();
    }

    public function getDeletionRestrictionReason(): ?string
    {
        if ($this->hasActiveSubscriptions()) {
            $count = $this->getActiveSubscriptionsCount();
            return "Este plan tiene {$count} suscripciones activas y no puede ser eliminado.";
        }

        return null;
    }
}
