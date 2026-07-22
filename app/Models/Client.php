<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'custom_fields',
    ];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}

