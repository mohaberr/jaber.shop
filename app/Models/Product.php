<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'name',
        'sku',
        'image_path',
        'unit_price',
        'quantity',
        'low_stock_threshold',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    public function setImagePathAttribute(?string $value): void
    {
        $this->attributes['image_path'] = $this->normalizeImagePath($value);
    }

    public function getImagePathAttribute(?string $value): ?string
    {
        return $this->normalizeImagePath($value);
    }

    public function getImageUrlAttribute(): ?string
    {
        $path = $this->image_path;

        return $path ? Storage::disk('public')->url($path) : null;
    }

    private function normalizeImagePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        if (Str::startsWith($normalized, 'public/')) {
            $normalized = Str::after($normalized, 'public/');
        }

        if (Str::startsWith($normalized, 'storage/')) {
            $normalized = Str::after($normalized, 'storage/');
        }

        return $normalized ?: null;
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
