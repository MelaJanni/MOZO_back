<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'point_of_sale',
        'invoice_number',
        'full_number',
        'invoice_type',
        'cae',
        'cae_expiration',
        'afip_response',
        'afip_request_id',
        'customer_name',
        'customer_email',
        'customer_cuit',
        'customer_address',
        'customer_city',
        'customer_state',
        'customer_zip_code',
        'customer_country',
        'company_name',
        'company_cuit',
        'company_address',
        'company_city',
        'company_state',
        'company_zip_code',
        'line_items',
        'description',
        'subtotal_cents',
        'tax_cents',
        'total_cents',
        'currency',
        'tax_percentage',
        'discount_percentage',
        'status',
        'sent_at',
        'paid_at',
        'due_date',
        'pdf_path',
        'xml_path',
        'notes',
        'internal_notes',
    ];

    protected $casts = [
        'afip_response' => 'array',
        'line_items' => 'array',
        'tax_percentage' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'due_date' => 'datetime',
        'cae_expiration' => 'date',
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

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'sent')
                    ->where('due_date', '<', now());
    }

    // Métodos de estado
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'sent';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function isOverdue(): bool
    {
        return $this->isPending() && $this->due_date && $this->due_date->isPast();
    }

    // Métodos de montos
    public function getSubtotal(): float
    {
        return $this->subtotal_cents / 100;
    }

    public function getTax(): float
    {
        return $this->tax_cents / 100;
    }

    public function getTotal(): float
    {
        return $this->total_cents / 100;
    }

    public function getFormattedSubtotal(): string
    {
        return $this->formatAmount($this->getSubtotal());
    }

    public function getFormattedTax(): string
    {
        return $this->formatAmount($this->getTax());
    }

    public function getFormattedTotal(): string
    {
        return $this->formatAmount($this->getTotal());
    }

    private function formatAmount(float $amount): string
    {
        $symbol = $this->currency === 'USD' ? 'USD $' : '$';
        return $symbol . number_format($amount, 2);
    }

    // Métodos de AFIP
    public function hasCAE(): bool
    {
        return !empty($this->cae);
    }

    public function isCAEExpired(): bool
    {
        return $this->cae_expiration && $this->cae_expiration->isPast();
    }

    public function isCAEValid(): bool
    {
        return $this->hasCAE() && !$this->isCAEExpired();
    }

    // Métodos de numeración
    public static function generateNextInvoiceNumber(string $pointOfSale = '0001'): string
    {
        $lastInvoice = static::where('point_of_sale', $pointOfSale)
                           ->orderBy('invoice_number', 'desc')
                           ->first();

        $nextNumber = $lastInvoice
            ? (int)$lastInvoice->invoice_number + 1
            : 1;

        return str_pad($nextNumber, 8, '0', STR_PAD_LEFT);
    }

    public static function generateFullNumber(string $pointOfSale, string $invoiceNumber): string
    {
        return $pointOfSale . '-' . $invoiceNumber;
    }

    // Mutators
    public function setSubtotalCentsAttribute($value)
    {
        $this->attributes['subtotal_cents'] = is_float($value) ? (int)($value * 100) : $value;
    }

    public function setTaxCentsAttribute($value)
    {
        $this->attributes['tax_cents'] = is_float($value) ? (int)($value * 100) : $value;
    }

    public function setTotalCentsAttribute($value)
    {
        $this->attributes['total_cents'] = is_float($value) ? (int)($value * 100) : $value;
    }

    // Métodos de acciones
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'canceled']);
    }

    // Métodos para line items
    public function addLineItem(array $item): void
    {
        $lineItems = $this->line_items ?? [];
        $lineItems[] = $item;
        $this->update(['line_items' => $lineItems]);
    }

    public function getTotalLineItems(): float
    {
        return collect($this->line_items ?? [])->sum(function ($item) {
            return ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
        });
    }
}