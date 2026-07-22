<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 24px 32px 96px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; padding-bottom: 72px; }
        h1 { margin: 0 0 8px; color: #006a67; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
        .meta { margin-bottom: 4px; }
        .header-table { width: 100%; border: none; margin-bottom: 20px; }
        .header-table td { border: none; padding: 0; vertical-align: top; }
        .company-box { font-size: 13px; }
        .company-name { font-size: 16px; font-weight: bold; color: #111; margin-bottom: 4px; }
        .customer-box { background: #f9f9f9; padding: 10px; border: 1px solid #eee; border-radius: 4px; }
        .payment-box { background: #fcfffe; padding: 10px; border: 1px solid #e5f3f2; border-radius: 4px; margin-top: 12px; }
        .signatures {
            margin-top: 20px;
            border-right: 1px solid #999;
            padding-right: 12px;
            min-height: 48px;
            width: 45%;
            margin-left: auto;
        }
        .signature-label {
            font-weight: bold;
            color: #444;
        }
        .legal-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -72px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #555;
            white-space: pre-line;
        }
    </style>
</head>
<body>
    @php
        $company = \App\Models\CompanySetting::current();
        $legalMentionsTitle = trim((string) ($company->legal_mentions_title ?? ''));
        $visibility = $company->invoiceFieldVisibility();
        $rawInvoiceCustomFields = $invoice->customer_custom_fields ?? [];
        $invoiceCustomFields = collect($rawInvoiceCustomFields)
            ->map(function ($field, $key): ?array {
                if (is_array($field)) {
                    $label = trim((string) ($field['label'] ?? ''));
                    $value = trim((string) ($field['value'] ?? ''));
                    if ($label === '' && $value === '') {
                        return null;
                    }

                    if (array_key_exists('show_on_invoice', $field) && empty($field['show_on_invoice'])) {
                        return null;
                    }

                    return [
                        'label' => $label !== '' ? $label : 'Champ',
                        'value' => $value,
                    ];
                }

                $value = trim((string) $field);
                if ($value === '') {
                    return null;
                }

                return [
                    'label' => is_string($key) ? $key : 'Champ',
                    'value' => $value,
                ];
            })
            ->filter()
            ->values();
        $invoiceMetaFields = collect($invoice->invoice_custom_fields ?? [])
            ->map(function ($field): ?array {
                if (!is_array($field)) {
                    return null;
                }

                $label = trim((string) ($field['label'] ?? ''));
                $value = trim((string) ($field['value'] ?? ''));
                if ($label === '' && $value === '') {
                    return null;
                }

                if (array_key_exists('show_on_invoice', $field) && empty($field['show_on_invoice'])) {
                    return null;
                }

                return [
                    'label' => $label !== '' ? $label : 'Champ',
                    'value' => $value,
                ];
            })
            ->filter()
            ->values();
        $logoDataUri = null;
        if ($company->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($company->logo_path)) {
            $logoBinary = \Illuminate\Support\Facades\Storage::disk('public')->get($company->logo_path);
            $logoMime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($company->logo_path) ?: 'image/png';
            $logoDataUri = 'data:' . $logoMime . ';base64,' . base64_encode($logoBinary);
        }
        $taxAmount = $invoice->taxAmount();
        $legalMentions = trim((string) ($company->legal_mentions ?? ''));
        $customFields = collect($company->custom_fields ?? [])->filter(function ($field) {
            return !empty($field['name']) || !empty($field['value']);
        });
    @endphp

    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                @if($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="Logo" style="max-height: 60px; max-width: 180px; margin-bottom: 8px;"><br>
                @endif
                <div class="company-box">
                    <div class="company-name">{{ $company->company_name ?? 'Facturation & Stock' }}</div>
                    @if($company->address)<div>{{ $company->address }}</div>@endif
                    @if($company->phone)<div>Tél: {{ $company->phone }}</div>@endif
                    @if($company->email)<div>Email: {{ $company->email }}</div>@endif
                    @if($company->tax_number)<div>{{ $company->tax_number }}</div>@endif
                    @foreach($customFields as $field)
                        <div>
                            @if(!empty($field['name'])){{ $field['name'] }}: @endif{{ $field['value'] ?? '' }}
                        </div>
                    @endforeach
                </div>
            </td>
            <td style="width: 45%;">
                <h1>Facture {{ $invoice->invoice_number }}</h1>
                <div class="meta"><strong>Date:</strong> {{ $invoice->invoice_date?->format('d/m/Y') }}</div>
                @if(($visibility['due_date'] ?? true) && $invoice->due_date)<div class="meta"><strong>Échéance:</strong> {{ $invoice->due_date?->format('d/m/Y') }}</div>@endif
                
                <div class="customer-box" style="margin-top: 12px;">
                    <div style="font-weight: bold; margin-bottom: 4px; color: #555;">FACTURER À :</div>
                    <div style="font-size: 14px; font-weight: bold;">{{ $invoice->customer_name }}</div>
                    @if(($visibility['customer_address'] ?? true) && $invoice->customer_address)<div>{{ $invoice->customer_address }}</div>@endif
                    @if(($visibility['customer_phone'] ?? true) && $invoice->customer_phone)<div>Tél: {{ $invoice->customer_phone }}</div>@endif
                    @if(($visibility['customer_email'] ?? true) && $invoice->customer_email)<div>Email: {{ $invoice->customer_email }}</div>@endif
                    @if($visibility['client_custom_fields'] ?? true)
                        @foreach($invoiceCustomFields as $field)
                            <div>{{ $field['label'] ?? 'Champ' }}: {{ $field['value'] ?? '-' }}</div>
                        @endforeach
                    @endif
                </div>
                @if($invoice->paymentMethodLabel() || $invoiceMetaFields->isNotEmpty())
                    <div class="payment-box">
                        @if($invoice->paymentMethodLabel())<div><strong>Mode de paiement:</strong> {{ $invoice->paymentMethodLabel() }}</div>@endif
                        @foreach($invoiceMetaFields as $field)
                            <div><strong>{{ $field['label'] ?? 'Champ' }}:</strong> {{ $field['value'] ?? '-' }}</div>
                        @endforeach
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <table>
        <thead>
        <tr>
            @if($visibility['item_product_images'] ?? true)
                <th>Photo</th>
            @endif
            <th>Produit</th>
            <th>Qte</th>
            <th>Prix U.</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($invoice->items as $item)
            <tr>
                @if($visibility['item_product_images'] ?? true)
                    <td style="width: 60px; text-align: center;">
                        @if($item->product?->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($item->product->image_path))
                            @php
                                $itemImageBinary = \Illuminate\Support\Facades\Storage::disk('public')->get($item->product->image_path);
                                $itemImageMime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($item->product->image_path) ?: 'image/png';
                                $itemImageDataUri = 'data:' . $itemImageMime . ';base64,' . base64_encode($itemImageBinary);
                            @endphp
                            <img src="{{ $itemImageDataUri }}" alt="{{ $item->product->name }}" style="max-height: 50px; max-width: 50px; object-fit: cover;">
                        @else
                            <span style="font-size: 10px; color: #999;">-</span>
                        @endif
                    </td>
                @endif
                <td>{{ $item->product?->name ?? 'Produit supprime' }}</td>
                <td>{{ $item->quantity }}</td>
                <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="right">{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if($visibility['subtotal'] ?? true)
        <p class="right">MONTANT H.T: {{ number_format((float) $invoice->subtotal, 2) }}</p>
    @endif
    @if($visibility['discount'] ?? true)
        <p class="right">Réduction: {{ $invoice->discount_type === 'percent' ? number_format((float) $invoice->discount_value, 2) . ' %' : number_format((float) $invoice->discount_value, 2) }}</p>
    @endif
    @if($visibility['tax'] ?? true)
        <p class="right">TVA: {{ number_format((float) $invoice->tax, 2) }} % ({{ number_format($taxAmount, 2) }})</p>
    @endif
    <p class="right"><strong>MONTANT TTC: {{ number_format($invoice->computedTotal(), 2) }}</strong></p>
    <p><strong>Arrêtée la présente facture à la somme de :</strong> {{ $invoice->totalInWords() }}</p>
    @if($visibility['paid_amount'] ?? true)
        <p class="right">Paye: {{ number_format($invoice->normalizedPaidAmount(), 2) }} ({{ number_format($invoice->paidPercent(), 2) }}%)</p>
    @endif
    @if($visibility['amount_due'] ?? true)
        <p class="right">Reste a payer: {{ number_format($invoice->amountDue(), 2) }}</p>
    @endif
    @if(($visibility['notes'] ?? true) && !empty($invoice->notes))
        <p><strong>Notes:</strong> {{ $invoice->notes }}</p>
    @endif

    <div class="signatures">
        <div class="signature-label">Cachet et Signature</div>
    </div>

    @if(($visibility['legal_mentions'] ?? true) && $legalMentions !== '')
        <div class="legal-footer">
            @if($legalMentionsTitle !== '')
                <strong>{{ $legalMentionsTitle }}:</strong>
            @endif
            <div>{{ $legalMentions }}</div>
        </div>
    @endif
</body>
</html>
