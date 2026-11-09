<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Facturation & Stock' }}</title>
    <style>
        :root {
            --bg: #f5f3ef;
            --panel: #ffffff;
            --ink: #172026;
            --accent: #006a67;
            --accent-2: #f18f01;
            --muted: #6b7280;
            --danger: #b42318;
            --radius: 14px;
            --shadow: 0 10px 26px rgba(23, 32, 38, 0.08);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            color: var(--ink);
            background: radial-gradient(circle at top right, #d8ece8 0%, var(--bg) 52%);
            min-height: 100vh;
        }

        .shell { max-width: 1100px; margin: 0 auto; padding: 16px; }

        .topbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .brand { font-size: 1.15rem; font-weight: 700; }

        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .nav a {
            text-decoration: none;
            color: var(--ink);
            background: var(--panel);
            padding: 8px 12px;
            border-radius: 999px;
            box-shadow: var(--shadow);
            font-weight: 600;
            font-size: 0.92rem;
        }

        .card {
            background: var(--panel);
            border-radius: var(--radius);
            padding: 16px;
            box-shadow: var(--shadow);
            margin-bottom: 16px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 12px;
        }

        .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 10px; }

        input, select, textarea {
            width: 100%;
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            padding: 10px;
            font-size: 0.95rem;
            background: #fff;
        }

        textarea { min-height: 88px; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.94rem;
        }

        th, td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #ececec;
            vertical-align: top;
        }

        .actions { display: flex; gap: 8px; flex-wrap: wrap; }

        .actions.compact {
            gap: 6px;
            align-items: center;
        }

        .btn-sm {
            padding: 7px 10px;
            font-size: 0.84rem;
            border-radius: 8px;
        }

        .btn-icon {
            min-width: 36px;
            height: 36px;
            justify-content: center;
            padding: 0 8px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .control-sm {
            padding: 7px 9px;
            font-size: 0.86rem;
            min-height: 36px;
        }

        .btn {
            border: none;
            padding: 10px 12px;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            font-size: 0.92rem;
        }

        .btn-main { background: var(--accent); color: white; }
        .btn-alt { background: #e8f6f5; color: #054b49; }
        .btn-warn { background: #fff2e3; color: #854600; }
        .btn-danger { background: #ffe7e7; color: var(--danger); }

        .msg {
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .ok { background: #def7ec; color: #0f5132; }
        .err { background: #fde8e8; color: #9b1c1c; }

        .kpi { font-size: 1.4rem; font-weight: 700; }
        .muted { color: var(--muted); }

        .pagination {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            list-style: none;
            padding: 0;
            margin: 12px 0 0;
            align-items: center;
        }

        .pagination .page-item {
            display: inline-flex;
            margin: 0;
        }

        .pagination .page-link,
        .pagination .page-item span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 38px;
            height: 38px;
            padding: 0 12px;
            border-radius: 10px;
            text-decoration: none;
            border: 1px solid #dfe5eb;
            background: #fff;
            color: var(--ink);
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .pagination .page-item.active .page-link,
        .pagination .page-item.active span {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .pagination .page-item.disabled .page-link,
        .pagination .page-item.disabled span {
            background: #f3f4f6;
            color: var(--muted);
            cursor: not-allowed;
            pointer-events: none;
        }

        .pagination .page-link:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .mobile-stack {
            display: grid;
            gap: 10px;
        }

        @media (max-width: 760px) {
            .desktop-table { display: none; }
            .card-list { display: block; }
        }

        @media (min-width: 761px) {
            .card-list { display: none; }
        }
    </style>
</head>
<body>
@php
    $company = \App\Models\CompanySetting::current();
@endphp
<div class="shell">
    <div class="topbar">
        <div class="brand" style="display: flex; align-items: center; gap: 10px;">
            @if($company->logo_path)
                <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo" style="max-height: 38px; max-width: 120px; object-fit: contain;">
            @endif
            <span>{{ $company->company_name ?? 'Facturation & Stock' }}</span>
        </div>
        <div class="nav">
            @auth
                <a href="{{ route('dashboard') }}">Accueil</a>
                <a href="{{ route('products.index') }}">Produits</a>
                <a href="{{ route('suppliers.index') }}">Fournisseurs</a>
                <a href="{{ route('clients.index') }}">Clients</a>
                <a href="{{ route('invoices.index') }}">Factures</a>
                <a href="{{ route('settings.edit') }}">Paramètres</a>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn" style="background: #ffe7e7; color: var(--danger); padding: 8px 12px; font-size: 0.92rem; border-radius: 999px;">Déconnexion</button>
                </form>
            @else
                <a href="{{ route('login') }}">Connexion</a>
            @endauth
        </div>
    </div>

    @if (session('success'))
        <div class="msg ok">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="msg err">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="msg err">
            {{ $errors->first() }}
        </div>
    @endif

    @yield('content')
</div>
</body>
</html>
