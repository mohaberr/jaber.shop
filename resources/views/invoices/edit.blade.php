@extends('layouts.app')

@section('content')
@php
    $existingItems = old('items', $invoice->items->map(fn($i) => ['product_id' => $i->product_id, 'quantity' => $i->quantity])->toArray());
@endphp
<div class="card">
    <h2 style="margin-top: 0;">Modifier facture {{ $invoice->invoice_number }}</h2>
    <form method="POST" action="{{ route('invoices.update', $invoice) }}">
        @csrf
        @method('PUT')
        @include('invoices._form', ['invoice' => $invoice, 'products' => $products, 'items' => $existingItems, 'paymentMethodOptions' => $paymentMethodOptions])
        <button class="btn btn-main" type="submit">Enregistrer</button>
        <a class="btn btn-alt" href="{{ route('invoices.show', $invoice) }}">Annuler</a>
    </form>
</div>
@endsection
