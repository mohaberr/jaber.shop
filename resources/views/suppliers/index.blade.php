@extends('layouts.app')

@section('content')
<div class="card">
    <h2 style="margin-top: 0;">Fournisseurs</h2>
    <form method="POST" action="{{ route('suppliers.store') }}">
        @csrf
        <div class="grid">
            <div class="field">
                <label>Nom</label>
                <input name="name" required>
            </div>
            <div class="field">
                <label>Telephone</label>
                <input name="phone">
            </div>
            <div class="field">
                <label>Email</label>
                <input name="email" type="email">
            </div>
            <div class="field">
                <label>Adresse</label>
                <input name="address">
            </div>
        </div>
        <div class="field">
            <label>Notes</label>
            <textarea name="notes"></textarea>
        </div>
        <button class="btn btn-main" type="submit">Ajouter fournisseur</button>
    </form>
</div>

<div class="card desktop-table">
    <table>
        <thead>
        <tr>
            <th>Nom</th>
            <th>Telephone</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach($suppliers as $supplier)
            <tr>
                <td>{{ $supplier->name }}</td>
                <td>{{ $supplier->phone ?? '-' }}</td>
                <td>{{ $supplier->email ?? '-' }}</td>
                <td class="actions">
                    <a class="btn btn-alt" href="{{ route('suppliers.edit', $supplier) }}">Modifier</a>
                    <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Supprimer ce fournisseur ?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="card-list">
    @foreach($suppliers as $supplier)
        <div class="card mobile-stack">
            <strong>{{ $supplier->name }}</strong>
            <div>Telephone: {{ $supplier->phone ?? '-' }}</div>
            <div>Email: {{ $supplier->email ?? '-' }}</div>
            <div class="actions">
                <a class="btn btn-alt" href="{{ route('suppliers.edit', $supplier) }}">Modifier</a>
                <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Supprimer ce fournisseur ?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">Supprimer</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
