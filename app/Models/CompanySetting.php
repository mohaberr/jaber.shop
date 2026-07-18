<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'email',
        'phone',
        'address',
        'tax_number',
        'logo_path',
        'legal_mentions',
        'custom_fields',
    ];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    public static function current(): self
    {
        return self::firstOrCreate(['id' => 1], [
            'company_name' => 'Ma Société',
        ]);
    }
}
