@extends('layouts.app')

@section('content')
<div style="max-width: 450px; margin: 40px auto;">
    <div class="card">
        <h2 style="margin-top: 0; text-align: center; color: var(--accent);">Connexion</h2>
        <p class="muted" style="text-align: center;">Accédez à votre espace de facturation et de stock.</p>

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="field">
                <label>Email *</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@entreprise.com">
            </div>

            <div class="field">
                <label>Mot de passe *</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <div style="margin: 12px 0;">
                <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="remember" style="width: auto;"> Se souvenir de moi
                </label>
            </div>

            <button class="btn btn-main" type="submit" style="width: 100%; justify-content: center;">Se connecter</button>
        </form>

        <div style="text-align: center; margin-top: 16px; border-top: 1px solid #eee; padding-top: 12px;">
            <span class="muted">Pas encore de compte ?</span>
            <a href="{{ route('register') }}" style="color: var(--accent); font-weight: bold; text-decoration: none;">Créer un compte</a>
        </div>
    </div>
</div>
@endsection
