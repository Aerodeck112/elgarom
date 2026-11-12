@php
    $u = auth()->user();
    $failedMail = \App\Models\MailOutbox::where('status', 'failed')->count();
    $pending = $u->can_('quotes.approve') ? \App\Models\Quote::where('status', 'aprobare')->count() : 0;
    // [route(:param), label, permission, badge, planned?]
    $nav = [
        'Comercial' => [
            ['admin.dashboard', 'Panou principal', 'dashboard', null],
            ['admin.companies.index', 'Clienți și contacte', 'crm.view', null],
            ['admin.rfqs.index', 'Cereri de ofertă (RFQ)', 'rfq.view', null],
            ['admin.quotes.index', 'Oferte comerciale', 'quotes.view', $pending ?: null],
            ['admin.res.index:products', 'Produse și proiecte', 'crm.view', null],
        ],
        'Calcul de costuri' => [
            ['admin.calc.index:piese', 'Calculator piese metalice', 'calc.use', null],
            ['admin.calc.index:stante', 'Calculator ștanțe și matrițe', 'calc.use', null],
            ['admin.zinc.index', 'Calculator zincare', 'calc.use', null],
            ['admin.models.index', 'Modele Excel', 'formulas.manage', null],
        ],
        'Bibliotecă' => [
            ['admin.res.index:materials', 'Materiale și costuri', 'library.view', null],
            ['admin.res.index:machines', 'Utilaje', 'library.view', null],
            ['admin.res.index:operations', 'Operații tehnologice', 'library.view', null],
            ['admin.res.index:suppliers', 'Furnizori', 'library.view', null],
        ],
        'Dezvoltare comercială' => [
            ['admin.planned:prospectare', 'AI Prospectare Europa', 'prospecting', null, 'F4'],
            ['admin.planned:marketing', 'Email Marketing', 'marketing', null, 'F4'],
            ['admin.planned:sabloane', 'Șabloane și documente', 'templates', null, 'F3'],
            ['admin.planned:rapoarte', 'Rapoarte și analiză', 'reports', null, 'F4'],
        ],
        'Administrare' => [
            ['admin.entities.index', 'Companiile grupului', 'site', null],
            ['admin.settings.edit:site', 'Website public', 'site', null],
            ['admin.users.index', 'Utilizatori și permisiuni', 'users', null],
            ['admin.settings.edit:general', 'Configurări', 'settings', null],
            ['admin.settings.edit:smtp', 'SMTP și email', 'smtp', $failedMail ?: null],
            ['admin.settings.edit:ai', 'API Claude', 'api.keys', null],
            ['admin.security.index', 'Securitate', 'security', null],
            ['admin.backups.index', 'Backup și restaurare', 'backups', null],
            ['admin.updates.index', 'Actualizare aplicație', 'updates', null],
            ['admin.audit.index', 'Audit și activitate', 'audit', null],
        ],
    ];
    $cur = request()->url();
@endphp
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panou') · {{ setting('app.name', 'GRUP SIMEX') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script nonce="{{ csp_nonce() }}">try { document.documentElement.dataset.theme = localStorage.getItem('simex.theme') || 'light'; } catch (e) {}</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
<div class="shell">
    <aside class="side" id="app-side" data-side>
        <a class="side__logo" href="{{ route('admin.dashboard') }}"><span class="side__mark">GS</span><span class="side__name">{{ setting('app.name', 'GRUP SIMEX') }}<small>Industrial CRM</small></span></a>
        <nav aria-label="Meniu principal">
            @foreach ($nav as $group => $items)
                @php $items = array_filter($items, fn ($i) => $u->can_($i[2])); @endphp
                @if ($items)
                    <h2>{{ $group }}</h2>
                    @foreach ($items as $item)
                        @php
                            [$route, $label, $perm, $badge] = $item;
                            [$name, $param] = array_pad(explode(':', $route), 2, null);
                            $href = $param ? route($name, $param) : route($name);
                            $active = $route === 'admin.dashboard' ? $cur === $href : ($cur === $href || str_starts_with($cur, rtrim($href, '/').'/'));
                        @endphp
                        <a href="{{ $href }}" @if ($active) aria-current="page" @endif><span>{{ $label }}</span>@if ($badge)<span class="badge">{{ $badge }}</span>@elseif (! empty($item[4]))<span class="soon" title="Planificat – {{ $item[4] === 'F3' ? 'Faza 3' : 'Faza 4' }}">{{ $item[4] }}</span>@endif</a>
                    @endforeach
                @endif
            @endforeach
        </nav>
        <div class="small" style="padding:12px 18px;color:#6c757f;border-top:1px solid #262b30">v{{ config('simex.version') }}</div>
    </aside>
    <div class="main">
        <header class="topbar">
            <div class="btn-row">
                <button class="btn btn--line btn--sm menu-btn" type="button" data-side-toggle aria-controls="app-side" aria-expanded="false">Meniu</button>
                <a href="{{ url('/') }}" target="_blank" rel="noopener" class="small muted">Website public ↗</a>
            </div>
            <div class="btn-row">
                <button class="btn btn--line btn--sm" type="button" data-theme-toggle title="Schimbă tema luminoasă / întunecată">Temă</button>
                <a class="small" href="{{ route('admin.account') }}">{{ $u->name }} <span class="muted">· {{ $u->roleLabel() }}</span></a>
                <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="btn btn--line btn--sm" type="submit">Ieșire</button></form>
            </div>
        </header>
        <main class="content" id="continut">
            @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
            @if (session('warning'))<div class="alert alert--warn" role="status">{{ session('warning') }}</div>@endif
            @if (session('error'))<div class="alert alert--err" role="alert">{{ session('error') }}</div>@endif
            @if ($errors->any())
                <div class="alert alert--err" role="alert"><strong>Modificările nu au fost salvate.</strong><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
