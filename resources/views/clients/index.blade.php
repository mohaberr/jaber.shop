@extends('layouts.app')

@section('content')
<div class="card">
    <h2 style="margin-top: 0;">Clients</h2>
    <form method="POST" action="{{ route('clients.store') }}">
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
        <button class="btn btn-main" type="submit">Ajouter client</button>
    </form>
</div>

<div class="card desktop-table">
    <table>
        <thead>
        <tr>
            <th>Nom</th>
            <th>Telephone</th>
            <th>Email</th>
            <th>Adresse</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach($clients as $client)
            <tr>
                <td>{{ $client->name }}</td>
                <td>{{ $client->phone ?? '-' }}</td>
                <td>{{ $client->email ?? '-' }}</td>
                <td>{{ $client->address ?? '-' }}</td>
                <td class="actions">
                    <a class="btn btn-alt" href="{{ route('clients.edit', $client) }}">Modifier</a>
                    <form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('Supprimer ce client ?')">
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
    @foreach($clients as $client)
        <div class="card mobile-stack">
            <strong>{{ $client->name }}</strong>
            <div>Telephone: {{ $client->phone ?? '-' }}</div>
            <div>Email: {{ $client->email ?? '-' }}</div>
            <div>Adresse: {{ $client->address ?? '-' }}</div>
            <div class="actions">
                <a class="btn btn-alt" href="{{ route('clients.edit', $client) }}">Modifier</a>
                <form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('Supprimer ce client ?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">Supprimer</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
