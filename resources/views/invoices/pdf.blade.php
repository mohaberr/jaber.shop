<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
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
        .legal-footer {
            margin-top: 18px;
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
                @if($invoice->due_date)<div class="meta"><strong>Échéance:</strong> {{ $invoice->due_date?->format('d/m/Y') }}</div>@endif
                
                <div class="customer-box" style="margin-top: 12px;">
                    <div style="font-weight: bold; margin-bottom: 4px; color: #555;">FACTURER À :</div>
                    <div style="font-size: 14px; font-weight: bold;">{{ $invoice->customer_name }}</div>
                    @if($invoice->customer_address)<div>{{ $invoice->customer_address }}</div>@endif
                    @if($invoice->customer_phone)<div>Tél: {{ $invoice->customer_phone }}</div>@endif
                    @if($invoice->customer_email)<div>Email: {{ $invoice->customer_email }}</div>@endif
                </div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
        <tr>
            <th>Photo</th>
            <th>Produit</th>
            <th>Qte</th>
            <th>Prix U.</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($invoice->items as $item)
            <tr>
                <td style="width: 60px; text-align: center;">
                    @if($item->product?->image_path && file_exists(public_path('storage/' . $item->product->image_path)))
                        <img src="{{ public_path('storage/' . $item->product->image_path) }}" alt="{{ $item->product->name }}" style="max-height: 50px; max-width: 50px; object-fit: cover;">
                    @else
                        <span style="font-size: 10px; color: #999;">-</span>
                    @endif
                </td>
                <td>{{ $item->product?->name ?? 'Produit supprime' }}</td>
                <td>{{ $item->quantity }}</td>
                <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="right">{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <p class="right">Sous-total: {{ number_format((float) $invoice->subtotal, 2) }}</p>
    <p class="right">Réduction: {{ $invoice->discount_type === 'percent' ? number_format((float) $invoice->discount_value, 2) . ' %' : number_format((float) $invoice->discount_value, 2) }}</p>
    <p class="right">TVA: {{ number_format((float) $invoice->tax, 2) }} % ({{ number_format($taxAmount, 2) }})</p>
    <p class="right"><strong>Total: {{ number_format($invoice->computedTotal(), 2) }}</strong></p>

    @if($legalMentions !== '')
        <div class="legal-footer">
            <strong>Mentions legales:</strong>
            <div>{{ $legalMentions }}</div>
        </div>
    @endif
</body>
</html>
