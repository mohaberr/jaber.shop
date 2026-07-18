<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Brouillon',
        self::STATUS_SENT => 'Envoyee',
        self::STATUS_PENDING => 'En attente',
        self::STATUS_PAID => 'Payee',
        self::STATUS_CANCELLED => 'Annulee',
    ];

    protected $fillable = [
        'client_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'status',
        'subtotal',
        'tax',
        'discount_type',
        'discount_value',
        'paid_amount',
        'total',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public static function allowedStatuses(): array
    {
        return array_keys(self::STATUS_LABELS);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function discountAmount(): float
    {
        $subtotal = (float) $this->subtotal;
        $value = (float) $this->discount_value;

        if ($value <= 0) {
            return 0.0;
        }

        if ($this->discount_type === 'percent') {
            return min($subtotal, $subtotal * ($value / 100));
        }

        return min($subtotal, $value);
    }

    public function netSubtotal(): float
    {
        return max(0, (float) $this->subtotal - $this->discountAmount());
    }

    public function taxAmount(): float
    {
        return $this->netSubtotal() * ((float) $this->tax / 100);
    }

    public function computedTotal(): float
    {
        return $this->netSubtotal() + $this->taxAmount();
    }

    public function normalizedPaidAmount(): float
    {
        return max(0, min((float) $this->paid_amount, $this->computedTotal()));
    }

    public function amountDue(): float
    {
        return max(0, $this->computedTotal() - $this->normalizedPaidAmount());
    }

    public function paidPercent(): float
    {
        $total = $this->computedTotal();

        if ($total <= 0) {
            return 0;
        }

        return round(($this->normalizedPaidAmount() / $total) * 100, 2);
    }
}
