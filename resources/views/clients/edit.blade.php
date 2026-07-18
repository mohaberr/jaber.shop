@extends('layouts.app')

@section('content')
<div class="card">
    <h2 style="margin-top: 0;">Modifier client</h2>
    <form method="POST" action="{{ route('clients.update', $client) }}">
        @csrf
        @method('PUT')

        <div class="grid">
            <div class="field">
                <label>Nom</label>
                <input name="name" value="{{ old('name', $client->name) }}" required>
            </div>
            <div class="field">
                <label>Telephone</label>
                <input name="phone" value="{{ old('phone', $client->phone) }}">
            </div>
            <div class="field">
                <label>Email</label>
                <input name="email" type="email" value="{{ old('email', $client->email) }}">
            </div>
            <div class="field">
                <label>Adresse</label>
                <input name="address" value="{{ old('address', $client->address) }}">
            </div>
        </div>

        <div class="actions">
            <button class="btn btn-main" type="submit">Enregistrer</button>
            <a class="btn btn-alt" href="{{ route('clients.index') }}">Retour</a>
        </div>
    </form>
</div>
@endsection
