@extends('layouts.app')

@section('content')
@php
    $collectionRate = $totalInvoiced > 0 ? round(($totalPaid / $totalInvoiced) * 100, 2) : 0;
    $dueRate = max(0, 100 - $collectionRate);
    $statusColorMap = [
        'draft' => '#6b7280',
        'sent' => '#0b7fab',
        'pending' => '#d97706',
        'paid' => '#15803d',
        'cancelled' => '#b42318',
    ];
@endphp
<div class="card">
    <h2 style="margin-top: 0;">Gestion de factures et stock</h2>
    <p class="muted">Utilise le menu pour ajouter tes produits, fournisseurs, puis creer des factures qui decremente le stock automatiquement.</p>
</div>

<div class="grid">
    <div class="card">
        <div class="muted">Produits</div>
        <div class="kpi">{{ $productsCount }}</div>
    </div>
    <div class="card">
        <div class="muted">Fournisseurs</div>
        <div class="kpi">{{ $suppliersCount }}</div>
    </div>
    <div class="card">
        <div class="muted">Clients</div>
        <div class="kpi">{{ $clientsCount }}</div>
    </div>
    <div class="card">
        <div class="muted">Factures</div>
        <div class="kpi">{{ $invoicesCount }}</div>
    </div>
    <div class="card">
        <div class="muted">Factures payees</div>
        <div class="kpi" style="color: #0f5132;">{{ $paidInvoicesCount }}</div>
    </div>
    <div class="card">
        <div class="muted">Factures en attente</div>
        <div class="kpi" style="color: #854600;">{{ $pendingInvoicesCount }}</div>
    </div>
</div>

<div class="grid">
    <div class="card">
        <div class="muted">Total facture</div>
        <div class="kpi">{{ number_format($totalInvoiced, 2) }}</div>
    </div>
    <div class="card">
        <div class="muted">Total encaisse</div>
        <div class="kpi" style="color: #0f5132;">{{ number_format($totalPaid, 2) }}</div>
    </div>
    <div class="card">
        <div class="muted">Reste a encaisser</div>
        <div class="kpi" style="color: #9b1c1c;">{{ number_format($totalDue, 2) }}</div>
    </div>
</div>

<div class="card" style="background: linear-gradient(160deg, #f9fffd 0%, #f3f9ff 100%); border: 1px solid #e2edf7;">
    <h3 style="margin-top: 0;">Suivi encaissement</h3>
    <div style="display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 8px;">
        <div><strong>Encaisse:</strong> {{ number_format($totalPaid, 2) }} ({{ number_format($collectionRate, 2) }}%)</div>
        <div><strong>Reste:</strong> {{ number_format($totalDue, 2) }} ({{ number_format($dueRate, 2) }}%)</div>
    </div>

    <div style="height: 18px; border-radius: 999px; overflow: hidden; background: #ecf3fb; border: 1px solid #dce7f2; display: flex;">
        <div style="width: {{ $collectionRate }}%; background: linear-gradient(90deg, #0f9d7a 0%, #0f7a61 100%);"></div>
        <div style="width: {{ $dueRate }}%; background: linear-gradient(90deg, #f59f43 0%, #e98313 100%);"></div>
    </div>

    <p class="muted" style="margin: 8px 0 0;">Barre verte = montant encaissé, barre orange = montant restant à encaisser.</p>
</div>

<div class="card" style="background: linear-gradient(160deg, #fffaf3 0%, #fffdf9 100%); border: 1px solid #f2e7d7;">
    <h3 style="margin-top: 0;">Répartition des statuts</h3>

    @if($invoicesCount === 0)
        <p class="muted" style="margin-bottom: 0;">Aucune facture pour afficher les statuts.</p>
    @else
        <div style="display: grid; gap: 10px;">
            @foreach($statusBreakdown as $statusItem)
                @php
                    $count = $statusItem['count'];
                    $rate = $invoicesCount > 0 ? round(($count / $invoicesCount) * 100, 2) : 0;
                    $color = $statusColorMap[$statusItem['status']] ?? '#6b7280';
                @endphp
                <div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px; gap: 12px;">
                        <strong>{{ $statusItem['label'] }}</strong>
                        <span class="muted">{{ $count }} ({{ number_format($rate, 2) }}%)</span>
                    </div>
                    <div style="height: 10px; border-radius: 999px; background: #f1f1f1; overflow: hidden;">
                        <div style="height: 100%; width: {{ $rate }}%; background: {{ $color }};"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<div class="grid">
    <div class="card">
        <h3 style="margin-top: 0;">Stock faible</h3>
        @if($lowStockProducts->isEmpty())
            <p class="muted" style="margin-bottom: 0;">Aucun produit en alerte de stock.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th>Produit</th>
                    <th>Stock</th>
                    <th>Seuil</th>
                </tr>
                </thead>
                <tbody>
                @foreach($lowStockProducts as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->quantity }}</td>
                        <td>{{ $product->low_stock_threshold }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h3 style="margin-top: 0;">Dernieres factures</h3>
        @if($recentInvoices->isEmpty())
            <p class="muted" style="margin-bottom: 0;">Aucune facture pour le moment.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th>Numero</th>
                    <th>Client</th>
                    <th>Total</th>
                    <th>Statut</th>
                </tr>
                </thead>
                <tbody>
                @foreach($recentInvoices as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->customer_name }}</td>
                        <td>{{ number_format($invoice->computedTotal(), 2) }}</td>
                        <td>{{ $invoice->statusLabel() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
