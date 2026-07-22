<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    public const PAYMENT_METHOD_OPTIONS = [
        'especes' => 'Especes',
        'virement' => 'Virement bancaire',
        'cheque' => 'Cheque',
        'carte' => 'Carte bancaire',
        'traite' => 'Traite',
        'autre' => 'Autre',
    ];

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
        'customer_custom_fields',
        'payment_method',
        'invoice_custom_fields',
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
        'customer_custom_fields' => 'array',
        'invoice_custom_fields' => 'array',
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

    public static function paymentMethodOptions(): array
    {
        return self::PAYMENT_METHOD_OPTIONS;
    }

    public function paymentMethodLabel(): ?string
    {
        if (!$this->payment_method) {
            return null;
        }

        return self::PAYMENT_METHOD_OPTIONS[$this->payment_method] ?? $this->payment_method;
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

    public function totalInWords(): string
    {
        $total = round($this->computedTotal(), 2);
        $dirhams = (int) floor($total);
        $centimes = (int) round(($total - $dirhams) * 100);

        if ($centimes === 100) {
            $dirhams++;
            $centimes = 0;
        }

        $parts = [
            static::spellFrenchNumber($dirhams).' '.($dirhams > 1 ? 'dirhams' : 'dirham'),
        ];

        if ($centimes > 0) {
            $parts[] = static::spellFrenchNumber($centimes).' '.($centimes > 1 ? 'centimes' : 'centime');
        }

        return ucfirst(implode(' et ', $parts));
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

    protected static function spellFrenchNumber(int $number): string
    {
        if (class_exists('NumberFormatter')) {
            $formatter = new \NumberFormatter('fr_FR', \NumberFormatter::SPELLOUT);
            $formatted = $formatter->format($number);

            if (is_string($formatted) && $formatted !== '') {
                return str_replace('-', ' ', $formatted);
            }
        }

        return static::spellFrenchNumberFallback($number);
    }

    protected static function spellFrenchNumberFallback(int $number): string
    {
        $units = [
            0 => 'zero',
            1 => 'un',
            2 => 'deux',
            3 => 'trois',
            4 => 'quatre',
            5 => 'cinq',
            6 => 'six',
            7 => 'sept',
            8 => 'huit',
            9 => 'neuf',
            10 => 'dix',
            11 => 'onze',
            12 => 'douze',
            13 => 'treize',
            14 => 'quatorze',
            15 => 'quinze',
            16 => 'seize',
            17 => 'dix sept',
            18 => 'dix huit',
            19 => 'dix neuf',
        ];

        $tens = [
            20 => 'vingt',
            30 => 'trente',
            40 => 'quarante',
            50 => 'cinquante',
            60 => 'soixante',
        ];

        if ($number < 20) {
            return $units[$number];
        }

        if ($number < 70) {
            $ten = (int) (floor($number / 10) * 10);
            $unit = $number % 10;

            if ($unit === 0) {
                return $tens[$ten];
            }

            if ($unit === 1) {
                return $tens[$ten].' et un';
            }

            return $tens[$ten].' '.$units[$unit];
        }

        if ($number < 80) {
            if ($number === 71) {
                return 'soixante et onze';
            }

            return 'soixante '.static::spellFrenchNumberFallback($number - 60);
        }

        if ($number < 100) {
            if ($number === 80) {
                return 'quatre vingts';
            }

            return 'quatre vingt '.static::spellFrenchNumberFallback($number - 80);
        }

        if ($number < 1000) {
            $hundreds = (int) floor($number / 100);
            $remainder = $number % 100;

            if ($hundreds === 1) {
                $prefix = 'cent';
            } else {
                $prefix = $units[$hundreds].' cent';
            }

            if ($remainder === 0) {
                return $hundreds > 1 ? $prefix.'s' : $prefix;
            }

            return $prefix.' '.static::spellFrenchNumberFallback($remainder);
        }

        if ($number < 1000000) {
            $thousands = (int) floor($number / 1000);
            $remainder = $number % 1000;

            $prefix = $thousands === 1
                ? 'mille'
                : static::spellFrenchNumberFallback($thousands).' mille';

            if ($remainder === 0) {
                return $prefix;
            }

            return $prefix.' '.static::spellFrenchNumberFallback($remainder);
        }

        if ($number < 1000000000) {
            $millions = (int) floor($number / 1000000);
            $remainder = $number % 1000000;

            $prefix = $millions === 1
                ? 'un million'
                : static::spellFrenchNumberFallback($millions).' millions';

            if ($remainder === 0) {
                return $prefix;
            }

            return $prefix.' '.static::spellFrenchNumberFallback($remainder);
        }

        $billions = (int) floor($number / 1000000000);
        $remainder = $number % 1000000000;

        $prefix = $billions === 1
            ? 'un milliard'
            : static::spellFrenchNumberFallback($billions).' milliards';

        if ($remainder === 0) {
            return $prefix;
        }

        return $prefix.' '.static::spellFrenchNumberFallback($remainder);
    }
}
