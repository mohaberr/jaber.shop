@extends('layouts.app')

@section('content')
@php
    $company = \App\Models\CompanySetting::current();
    $taxAmount = $invoice->taxAmount();
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
            @if($invoice->due_date)<div><strong>Échéance :</strong> {{ $invoice->due_date?->format('d/m/Y') }}</div>@endif
            <div><span style="display: inline-block; padding: 2px 8px; border-radius: 6px; background: #eee; font-size: 0.85rem; font-weight: bold; margin-top: 4px;">Statut : {{ strtoupper($invoice->statusLabel()) }}</span></div>
        </div>
    </div>

    <div class="grid" style="background: #fdfdfd; padding: 12px; border-radius: 8px; border: 1px solid #f0f0f0;">
        <div><strong>Client :</strong> {{ $invoice->customer_name }}</div>
        <div><strong>Téléphone :</strong> {{ $invoice->customer_phone ?? '-' }}</div>
        <div><strong>Email :</strong> {{ $invoice->customer_email ?? '-' }}</div>
        <div><strong>Adresse :</strong> {{ $invoice->customer_address ?? '-' }}</div>
    </div>

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
                <td>
                    @if($item->product?->image_path)
                        <img src="{{ asset('storage/' . $item->product->image_path) }}" alt="{{ $item->product->name }}" style="max-height: 40px; max-width: 60px; object-fit: cover; border-radius: 4px;">
                    @else
                        <span class="muted">-</span>
                    @endif
                </td>
                <td>{{ $item->product?->name ?? 'Produit supprime' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                <td>{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div style="margin-top: 10px;">
        <div><strong>Sous-total:</strong> {{ number_format((float) $invoice->subtotal, 2) }}</div>
        <div><strong>Réduction:</strong> {{ $invoice->discount_type === 'percent' ? number_format((float) $invoice->discount_value, 2) . ' %' : number_format((float) $invoice->discount_value, 2) }}</div>
        <div><strong>TVA:</strong> {{ number_format((float) $invoice->tax, 2) }} % ({{ number_format($taxAmount, 2) }})</div>
        <div><strong>Total:</strong> {{ number_format($invoice->computedTotal(), 2) }}</div>
        <div><strong>Paye:</strong> {{ number_format($invoice->normalizedPaidAmount(), 2) }} ({{ number_format($invoice->paidPercent(), 2) }}%)</div>
        <div><strong>Reste a payer:</strong> {{ number_format($invoice->amountDue(), 2) }}</div>
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
