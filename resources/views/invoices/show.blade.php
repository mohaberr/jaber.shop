@extends('layouts.app')

@section('content')
@php
    $company = \App\Models\CompanySetting::current();
    $legalMentionsTitle = trim((string) ($company->legal_mentions_title ?? ''));
    $taxAmount = $invoice->taxAmount();
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
    $rawInvoiceMetaFields = $invoice->invoice_custom_fields ?? [];
    $invoiceMetaFields = collect($rawInvoiceMetaFields)
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
@endphp
<div class="card">
    <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 12px;">
        <div>
            <h2 style="margin: 0 0 4px; color: var(--accent);">{{ $company->company_name ?? 'Facturation & Stock' }}</h2>
            <div class="muted">{{ $company->address ?? '' }}</div>
            <div class="muted">{{ $company->phone ? 'Tél: '.$company->phone : '' }}</div>
            <div class="muted">{{ $company->tax_number ?? '' }}</div>
        </div>
        <div style="text-align: right;">
            <h2 style="margin: 0;">Facture {{ $invoice->invoice_number }}</h2>
            <div><strong>Date :</strong> {{ $invoice->invoice_date?->format('d/m/Y') }}</div>
            @if(($visibility['due_date'] ?? true) && $invoice->due_date)<div><strong>Échéance :</strong> {{ $invoice->due_date?->format('d/m/Y') }}</div>@endif
            <div><span style="display: inline-block; padding: 2px 8px; border-radius: 6px; background: #eee; font-size: 0.85rem; font-weight: bold; margin-top: 4px;">Statut : {{ strtoupper($invoice->statusLabel()) }}</span></div>
        </div>
    </div>

    <div class="grid" style="background: #fdfdfd; padding: 12px; border-radius: 8px; border: 1px solid #f0f0f0;">
        <div><strong>Client :</strong> {{ $invoice->customer_name }}</div>
        @if($visibility['customer_phone'] ?? true)
            <div><strong>Téléphone :</strong> {{ $invoice->customer_phone ?? '-' }}</div>
        @endif
        @if($visibility['customer_email'] ?? true)
            <div><strong>Email :</strong> {{ $invoice->customer_email ?? '-' }}</div>
        @endif
        @if($visibility['customer_address'] ?? true)
            <div><strong>Adresse :</strong> {{ $invoice->customer_address ?? '-' }}</div>
        @endif
        @if($visibility['client_custom_fields'] ?? true)
            @foreach($invoiceCustomFields as $field)
                <div><strong>{{ $field['label'] ?? 'Champ' }} :</strong> {{ $field['value'] ?? '-' }}</div>
            @endforeach
        @endif
    </div>

    @if($invoice->paymentMethodLabel() || $invoiceMetaFields->isNotEmpty())
        <div class="grid" style="margin-top: 12px; background: #fcfffe; padding: 12px; border-radius: 8px; border: 1px solid #e5f3f2;">
            @if($invoice->paymentMethodLabel())
                <div><strong>Mode de paiement :</strong> {{ $invoice->paymentMethodLabel() }}</div>
            @endif
            @foreach($invoiceMetaFields as $field)
                <div><strong>{{ $field['label'] ?? 'Champ' }} :</strong> {{ $field['value'] ?? '-' }}</div>
            @endforeach
        </div>
    @endif

    <div class="actions" style="margin-top: 12px;">
        <a class="btn btn-warn btn-sm btn-icon" href="{{ route('invoices.edit', $invoice) }}" title="Modifier" aria-label="Modifier">M</a>
        <a class="btn btn-alt btn-sm btn-icon" href="{{ route('invoices.pdf', $invoice) }}" title="Telecharger PDF" aria-label="Telecharger PDF">PDF</a>
        <a class="btn btn-main btn-sm btn-icon" href="{{ route('invoices.send-whatsapp', $invoice) }}" target="_blank" title="Envoyer WhatsApp" aria-label="Envoyer WhatsApp">WA</a>
        <form method="POST" action="{{ route('invoices.send-email', $invoice) }}">
            @csrf
            <button class="btn btn-main btn-sm btn-icon" type="submit" title="Envoyer Email" aria-label="Envoyer Email">@</button>
        </form>
        <form method="POST" action="{{ route('invoices.status', $invoice) }}" style="display: inline-flex; gap: 8px;">
            @csrf
            <select class="control-sm" name="status" style="min-width: 135px;">
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected($invoice->status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-alt btn-sm" type="submit">Marquer</button>
        </form>
    </div>
</div>

<div class="card">
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
                    <td>
                        @if($item->product?->image_path)
                            <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" style="max-height: 40px; max-width: 60px; object-fit: cover; border-radius: 4px;">
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                @endif
                <td>{{ $item->product?->name ?? 'Produit supprime' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                <td>{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div style="margin-top: 10px;">
        @if($visibility['subtotal'] ?? true)
            <div><strong>MONTANT H.T:</strong> {{ number_format((float) $invoice->subtotal, 2) }}</div>
        @endif
        @if($visibility['discount'] ?? true)
            <div><strong>Réduction:</strong> {{ $invoice->discount_type === 'percent' ? number_format((float) $invoice->discount_value, 2) . ' %' : number_format((float) $invoice->discount_value, 2) }}</div>
        @endif
        @if($visibility['tax'] ?? true)
            <div><strong>TVA:</strong> {{ number_format((float) $invoice->tax, 2) }} % ({{ number_format($taxAmount, 2) }})</div>
        @endif
        <div><strong>MONTANT TTC:</strong> {{ number_format($invoice->computedTotal(), 2) }}</div>
        <div style="margin-top: 8px;"><strong>Arrêtée la présente facture à la somme de :</strong> {{ $invoice->totalInWords() }}</div>
        @if($visibility['paid_amount'] ?? true)
            <div><strong>Paye:</strong> {{ number_format($invoice->normalizedPaidAmount(), 2) }} ({{ number_format($invoice->paidPercent(), 2) }}%)</div>
        @endif
        @if($visibility['amount_due'] ?? true)
            <div><strong>Reste a payer:</strong> {{ number_format($invoice->amountDue(), 2) }}</div>
        @endif
        @if(($visibility['notes'] ?? true) && !empty($invoice->notes))
            <div><strong>Notes:</strong> {{ $invoice->notes }}</div>
        @endif
        @if(($visibility['legal_mentions'] ?? true) && !empty($company->legal_mentions))
            <div style="margin-top: 8px;">
                @if($legalMentionsTitle !== '')
                    <strong>{{ $legalMentionsTitle }}:</strong>
                @endif
                {!! nl2br(e($company->legal_mentions)) !!}
            </div>
        @endif
    </div>

    <div class="card" style="margin-top: 14px; background: #fcfffe; border: 1px solid #e5f3f2;">
        <h3 style="margin-top: 0;">Paiement partiel</h3>
        <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}" class="actions compact">
            @csrf
            <input class="control-sm" type="number" name="amount" step="0.01" min="0.01" placeholder="Montant" style="max-width: 130px;">
            <button class="btn btn-main btn-sm" type="submit">Encaisser</button>
        </form>
        <p class="muted" style="margin-bottom: 0;">Exemple: total 100, paiement 20 => reste 80.</p>
    </div>
</div>
@endsection
