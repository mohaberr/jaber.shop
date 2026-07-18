@extends('layouts.app')

@section('content')
<div style="max-width: 480px; margin: 30px auto;">
    <div class="card">
        <h2 style="margin-top: 0; text-align: center; color: var(--accent);">Créer un compte administrateur</h2>
        <p class="muted" style="text-align: center;">Protégez votre application en ligne en configurant vos accès.</p>

        <form method="POST" action="{{ route('register.post') }}">
            @csrf
            <div class="field">
                <label>Nom ou Société *</label>
                <input name="name" value="{{ old('name') }}" required autofocus placeholder="Mon Nom ou Ma Société">
            </div>

            <div class="field">
                <label>Email *</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="admin@entreprise.com">
            </div>

            <div class="field">
                <label>Mot de passe * (min. 8 caractères)</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <div class="field">
                <label>Confirmer le mot de passe *</label>
                <input type="password" name="password_confirmation" required placeholder="••••••••">
            </div>

            <button class="btn btn-main" type="submit" style="width: 100%; justify-content: center; margin-top: 10px;">S'inscrire</button>
        </form>

        <div style="text-align: center; margin-top: 16px; border-top: 1px solid #eee; padding-top: 12px;">
            <span class="muted">Vous avez déjà un compte ?</span>
            <a href="{{ route('login') }}" style="color: var(--accent); font-weight: bold; text-decoration: none;">Se connecter</a>
        </div>
    </div>
</div>
@endsection
