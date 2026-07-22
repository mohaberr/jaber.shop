@extends('layouts.app')

@section('content')
<div class="card">
    <h2 style="margin-top: 0;">Nouvelle facture</h2>
    <form method="POST" action="{{ route('invoices.store') }}">
        @csrf
        @include('invoices._form', ['invoice' => $invoice, 'products' => $products, 'items' => old('items', [['product_id' => '', 'quantity' => 1]]), 'paymentMethodOptions' => $paymentMethodOptions])
        <button class="btn btn-main" type="submit">Creer facture</button>
        <a class="btn btn-alt" href="{{ route('invoices.index') }}">Retour</a>
    </form>
</div>
@endsection
