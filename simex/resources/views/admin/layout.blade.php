@php
    $u = auth()->user();
    $failedMail = $u->can_('smtp') ? \App\Models\MailOutbox::where('status', 'failed')->count() : 0;
    $pending = $u->can_('quotes.approve') ? \App\Models\Quote::where('status', 'aprobare')->count() : 0;
    $issuer = \App\Models\Entity::where('is_issuer', true)->orderBy('sort')->orderBy('id')->first();
    $nextNo = $issuer && $u->can_('quotes.view') ? \App\Services\Numbering::peek('quote-'.$issuer->code, $issuer->quote_prefix ?: 'OF') : null;
    $dieWarn = $u->can_('calc.use') ? \App\Models\Calculation::where('model_key', 'stante-matrite-fepo')->whereIn('state', ['warn', 'block'])->whereNull('locked_at')->count() : 0;
    $fx = setting('quotes.exchange_rate');
    $fxDate = setting('quotes.exchange_rate_date');
    $fxStale = $fxDate && \Illuminate\Support\Carbon::parse($fxDate)->lt(now()->subDays(4));
    // Main tabs, in the order of the ARGENTO application: [route(:param), label, permission, badge, badge tone]
    $tabs = [
        ['admin.dashboard', 'Dashboard', 'dashboard', null],
        ['admin.calc.index:piese', 'Ofertă piesă', 'calc.use', null],
        ['admin.rates', 'Tarife', 'calc.use', null],
        ['admin.calc.create:stante-matrite-fepo', 'Ofertă ștanță', 'calc.use', $dieWarn ?: null, 'warn'],
        ['admin.tutorial', 'Tutorial', 'dashboard', null],
        ['admin.quotes.index', 'Oferte', 'quotes.view', $pending ?: null],
        ['admin.ai.index', 'Integrare AI', 'ai.use', null],
    ];
    $more = [
        'Comercial' => [
            ['admin.companies.index', 'Clienți și contacte', 'crm.view'],
            ['admin.rfqs.index', 'Cereri de ofertă (RFQ)', 'rfq.view'],
            ['admin.res.index:products', 'Produse și proiecte', 'crm.view'],
        ],
        'Calcul de costuri' => [
            ['admin.calc.index:stante', 'Calcule ștanțe salvate', 'calc.use'],
            ['admin.zinc.index', 'Calculator zincare', 'calc.use'],
            ['admin.models.index', 'Modele Excel', 'formulas.manage'],
        ],
        'Bibliotecă' => [
            ['admin.res.index:materials', 'Materiale și costuri', 'library.view'],
            ['admin.res.index:machines', 'Utilaje', 'library.view'],
            ['admin.res.index:operations', 'Operații tehnologice', 'library.view'],
            ['admin.res.index:suppliers', 'Furnizori', 'library.view'],
        ],
        'Dezvoltare comercială' => [
            ['admin.planned:prospectare', 'AI Prospectare Europa', 'prospecting', 'F4'],
            ['admin.planned:marketing', 'Email Marketing', 'marketing', 'F4'],
            ['admin.planned:sabloane', 'Șabloane și documente', 'templates', 'F3'],
            ['admin.planned:rapoarte', 'Rapoarte și analiză', 'reports', 'F4'],
        ],
    ];
    $admin = [
        ['admin.entities.index', 'Companiile grupului', 'site'],
        ['admin.settings.edit:site', 'Website public', 'site'],
        ['admin.users.index', 'Utilizatori și permisiuni', 'users'],
        ['admin.settings.edit:general', 'Configurări', 'settings'],
        ['admin.settings.edit:smtp', 'SMTP și email', 'smtp', $failedMail ?: null],
        ['admin.settings.edit:ai', 'API Claude', 'api.keys'],
        ['admin.security.index', 'Securitate', 'security'],
        ['admin.backups.index', 'Backup și restaurare', 'backups'],
        ['admin.updates.index', 'Actualizare aplicație', 'updates'],
        ['admin.audit.index', 'Audit și activitate', 'audit'],
    ];
    $href = function (string $route) { [$name, $param] = array_pad(explode(':', $route, 2), 2, null); return $param ? route($name, $param) : route($name); };
    $cur = request()->url();
    $isCur = fn (string $h, bool $exact = false) => $exact ? $cur === $h : ($cur === $h || str_starts_with($cur, rtrim($h, '/').'/'));
    $moreCur = collect($more)->flatten(1)->contains(fn ($i) => $u->can_($i[2]) && $isCur($href($i[0])));
    $adminItems = array_filter($admin, fn ($i) => $u->can_($i[2]));
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
<header class="hdr" data-hdr>
    <div class="hdr__top">
        <a class="brand" href="{{ route('admin.dashboard') }}">
            @if ($issuer?->logoUrl())<img src="{{ $issuer->logoUrl() }}" alt="">@else<span class="brand__mark">GS</span>@endif
            <span class="brand__name">{{ $issuer?->legal_name ?: setting('app.name', 'GRUP SIMEX') }}
                <span class="brand__meta">@if ($issuer){{ collect([$issuer->fullAddress(), $issuer->tax_id ? 'CUI '.$issuer->tax_id : null, $issuer->reg_no])->filter()->implode(' · ') ?: 'Date juridice neconfirmate' }}@else Industrial CRM @endif</span></span>
        </a>
        <div class="hdr__tools">
            @if ($nextNo)<span class="chip" title="Numărul pe care îl va primi următoarea ofertă">#&nbsp;<b>{{ $nextNo }}</b></span>@endif
            @if (can('quotes.manage'))<a class="btn btn--accent btn--sm" href="{{ route('admin.quotes.create') }}">+ Ofertă nouă</a>@endif
            <details class="drop">
                <summary class="chip chip--fx @if ($fxStale) chip--stale @endif" title="Curs BNR folosit implicit în ofertele noi">EUR <b>{{ $fx ? number_format((float) $fx, 4, ',', '.') : '—' }}</b> lei @if ($fxDate)<span>· {{ \Illuminate\Support\Carbon::parse($fxDate)->format('d.m') }}</span>@endif</summary>
                <div class="drop__menu">
                    <p class="small muted" style="margin:6px 10px">Curs de referință BNR @if ($fxDate)din {{ \Illuminate\Support\Carbon::parse($fxDate)->format('d.m.Y') }}@endif. Ofertele deja create își păstrează cursul.</p>
                    @if (can('settings'))
                        <form method="post" action="{{ route('admin.fx.refresh') }}">@csrf<button type="submit">Actualizează acum de la BNR</button></form>
                        <a href="{{ route('admin.settings.edit', 'general') }}">Setări curs și ofertare</a>
                    @endif
                </div>
            </details>
            <span class="chip">{{ now()->format('d.m.Y') }}</span>
            <a class="chip" href="{{ route('admin.account') }}">{{ $u->name }}</a>
            @if ($adminItems)
            <details class="drop">
                <summary class="btn btn--line btn--sm">Administrare @if ($failedMail)<span class="badge badge--err">{{ $failedMail }}</span>@endif</summary>
                <div class="drop__menu">
                    @foreach ($adminItems as $i)<a href="{{ $href($i[0]) }}">{{ $i[1] }} @if (! empty($i[3]))<span class="badge badge--err">{{ $i[3] }}</span>@endif</a>@endforeach
                    <button type="button" data-theme-toggle>Temă luminoasă / întunecată</button>
                    <a href="{{ url('/') }}" target="_blank" rel="noopener">Website public ↗</a>
                </div>
            </details>
            @endif
            <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="btn btn--line btn--sm" type="submit">Ieșire</button></form>
        </div>
    </div>
    <nav class="nav" aria-label="Meniu principal">
        @foreach ($tabs as $t)
            @continue (! $u->can_($t[2]))
            @php $h = $href($t[0]); @endphp
            <a href="{{ $h }}" @if ($isCur($h, $t[0] === 'admin.dashboard')) aria-current="page" @endif>{{ $t[1] }} @if ($t[3])<span class="badge {{ ($t[4] ?? '') === 'warn' ? 'badge--warn' : '' }}" title="{{ ($t[4] ?? '') === 'warn' ? 'Calcule salvate cu avertizări' : 'De aprobat' }}">{{ $t[3] }}</span>@endif</a>
        @endforeach
        <details class="drop @if ($moreCur) is-current @endif">
            <summary>Mai multe ▾</summary>
            <div class="drop__menu">
                @foreach ($more as $group => $items)
                    @php $items = array_filter($items, fn ($i) => $u->can_($i[2])); @endphp
                    @if ($items)<h4>{{ $group }}</h4>
                        @foreach ($items as $i)<a href="{{ $href($i[0]) }}">{{ $i[1] }} @if (! empty($i[3]))<span class="soon">{{ $i[3] }}</span>@endif</a>@endforeach
                    @endif
                @endforeach
            </div>
        </details>
    </nav>
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
<footer class="small muted" style="text-align:center;padding:0 0 24px">{{ setting('app.name', 'GRUP SIMEX') }} · v{{ config('simex.version') }}</footer>
</body>
</html>
