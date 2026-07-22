<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;

    public const INVOICE_FIELD_OPTIONS = [
        'due_date' => 'Afficher l\'echeance',
        'customer_phone' => 'Afficher le telephone client',
        'customer_email' => 'Afficher l\'email client',
        'customer_address' => 'Afficher l\'adresse client',
        'client_custom_fields' => 'Afficher les champs client personnalises',
        'item_product_images' => 'Afficher les photos des produits',
        'subtotal' => 'Afficher le sous-total',
        'discount' => 'Afficher la reduction',
        'tax' => 'Afficher la TVA',
        'paid_amount' => 'Afficher le montant paye',
        'amount_due' => 'Afficher le reste a payer',
        'notes' => 'Afficher les notes facture',
        'legal_mentions' => 'Afficher les mentions legales',
    ];

    protected $fillable = [
        'company_name',
        'email',
        'phone',
        'address',
        'tax_number',
        'default_tax_rate',
        'legal_mentions_title',
        'logo_path',
        'legal_mentions',
        'invoice_field_visibility',
        'custom_fields',
    ];

    protected $casts = [
        'default_tax_rate' => 'decimal:2',
        'invoice_field_visibility' => 'array',
        'custom_fields' => 'array',
    ];

    public static function defaultInvoiceFieldVisibility(): array
    {
        return collect(self::INVOICE_FIELD_OPTIONS)
            ->mapWithKeys(fn ($_label, $key) => [$key => true])
            ->all();
    }

    public function invoiceFieldVisibility(): array
    {
        return array_merge(
            self::defaultInvoiceFieldVisibility(),
            is_array($this->invoice_field_visibility) ? $this->invoice_field_visibility : []
        );
    }

    public static function current(): self
    {
        return self::firstOrCreate(['id' => 1], [
            'company_name' => 'Ma Société',
            'default_tax_rate' => 0,
            'legal_mentions_title' => 'Mentions legales',
        ]);
    }
}
