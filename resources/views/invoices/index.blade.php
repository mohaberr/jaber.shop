@extends('layouts.app')

@section('content')
<div class="card" style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
    <h2 style="margin: 0;">Factures</h2>
    <a class="btn btn-main" href="{{ route('invoices.create') }}">Nouvelle facture</a>
</div>

<div class="card desktop-table">
    <table>
        <thead>
        <tr>
            <th>Numero</th>
            <th>Date</th>
            <th>Client</th>
            <th>Total</th>
            <th>Paye</th>
            <th>Reste</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach($invoices as $invoice)
            <tr>
                <td>{{ $invoice->invoice_number }}</td>
                <td>{{ $invoice->invoice_date?->format('d/m/Y') }}</td>
                <td>{{ $invoice->customer_name }}</td>
                <td>{{ number_format($invoice->computedTotal(), 2) }}</td>
                <td>{{ number_format($invoice->normalizedPaidAmount(), 2) }} ({{ number_format($invoice->paidPercent(), 2) }}%)</td>
                <td>{{ number_format($invoice->amountDue(), 2) }}</td>
                <td>{{ $invoice->statusLabel() }}</td>
                <td class="actions compact">
                    <a class="btn btn-alt btn-sm" href="{{ route('invoices.show', $invoice) }}">Voir</a>
                    <a class="btn btn-warn btn-sm" href="{{ route('invoices.edit', $invoice) }}">Modifier</a>
                    <a class="btn btn-main btn-sm btn-icon" href="{{ route('invoices.send-whatsapp', $invoice) }}" target="_blank" title="Envoyer WhatsApp" aria-label="Envoyer WhatsApp">WA</a>
                    <form method="POST" action="{{ route('invoices.send-email', $invoice) }}">
                        @csrf
                        <button class="btn btn-main btn-sm btn-icon" type="submit" title="Envoyer Email" aria-label="Envoyer Email">@</button>
                    </form>
                    <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('Supprimer la facture ? Le stock sera restaure.')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm btn-icon" type="submit" title="Supprimer" aria-label="Supprimer">X</button>
                    </form>
                    <form method="POST" action="{{ route('invoices.status', $invoice) }}">
                        @csrf
                        <select class="control-sm" name="status" style="min-width: 120px;">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected($invoice->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-alt btn-sm" type="submit">Marquer</button>
                    </form>
                    <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}">
                        @csrf
                        <input class="control-sm" type="number" name="amount" step="0.01" min="0.01" placeholder="Montant" style="max-width: 108px;">
                        <button class="btn btn-main btn-sm" type="submit">Encaisser</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div style="margin-top: 12px;">
        {{ $invoices->links() }}
    </div>
</div>

<div class="card-list">
    @foreach($invoices as $invoice)
        <div class="card mobile-stack">
            <strong>{{ $invoice->invoice_number }}</strong>
            <div>Date: {{ $invoice->invoice_date?->format('d/m/Y') }}</div>
            <div>Client: {{ $invoice->customer_name }}</div>
            <div>Total: {{ number_format($invoice->computedTotal(), 2) }}</div>
            <div>Paye: {{ number_format($invoice->normalizedPaidAmount(), 2) }} ({{ number_format($invoice->paidPercent(), 2) }}%)</div>
            <div>Reste: {{ number_format($invoice->amountDue(), 2) }}</div>
            <div>Statut: {{ $invoice->statusLabel() }}</div>
            <div class="actions compact">
                <a class="btn btn-alt btn-sm" href="{{ route('invoices.show', $invoice) }}">Voir</a>
                <a class="btn btn-warn btn-sm" href="{{ route('invoices.edit', $invoice) }}">Modifier</a>
                <a class="btn btn-main btn-sm btn-icon" href="{{ route('invoices.send-whatsapp', $invoice) }}" target="_blank" title="Envoyer WhatsApp" aria-label="Envoyer WhatsApp">WA</a>
                <form method="POST" action="{{ route('invoices.send-email', $invoice) }}">
                    @csrf
                    <button class="btn btn-main btn-sm btn-icon" type="submit" title="Envoyer Email" aria-label="Envoyer Email">@</button>
                </form>
                <form method="POST" action="{{ route('invoices.status', $invoice) }}">
                    @csrf
                    <select class="control-sm" name="status" style="min-width: 120px;">
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($invoice->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-alt btn-sm" type="submit">Marquer</button>
                </form>
                <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}">
                    @csrf
                    <input class="control-sm" type="number" name="amount" step="0.01" min="0.01" placeholder="Montant" style="max-width: 108px;">
                    <button class="btn btn-main btn-sm" type="submit">Encaisser</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
