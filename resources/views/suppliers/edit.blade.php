@extends('layouts.app')

@section('content')
<div class="card">
    <h2 style="margin-top: 0;">Modifier fournisseur</h2>
    <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
        @csrf
        @method('PUT')

        <div class="grid">
            <div class="field">
                <label>Nom</label>
                <input name="name" value="{{ old('name', $supplier->name) }}" required>
            </div>
            <div class="field">
                <label>Telephone</label>
                <input name="phone" value="{{ old('phone', $supplier->phone) }}">
            </div>
            <div class="field">
                <label>Email</label>
                <input name="email" type="email" value="{{ old('email', $supplier->email) }}">
            </div>
            <div class="field">
                <label>Adresse</label>
                <input name="address" value="{{ old('address', $supplier->address) }}">
            </div>
        </div>

        <div class="field">
            <label>Notes</label>
            <textarea name="notes">{{ old('notes', $supplier->notes) }}</textarea>
        </div>

        <div class="actions">
            <button class="btn btn-main" type="submit">Enregistrer</button>
            <a class="btn btn-alt" href="{{ route('suppliers.index') }}">Retour</a>
        </div>
    </form>
</div>
@endsection
