@php
    $u = auth()->user();
    $newQuotes = \App\Models\QuoteRequest::where('status', 'nou')->count();
    $failedMail = \App\Models\MailOutbox::whereIn('status', ['failed'])->count() + \App\Models\MailOutbox::where('status', 'pending')->where('attempts', '>', 0)->count();
    $nav = [
        'General' => [
            ['admin.dashboard', 'Panou principal', 'dashboard', null],
            ['admin.quotes.index', 'Cereri de ofertă', 'quotes.view', $newQuotes ?: null],
        ],
        'Conținut' => [
            ['admin.pages.index', 'Pagini și secțiuni', 'content', null],
            ['admin.res.index:services', 'Servicii', 'content', null],
            ['admin.res.index:capabilities', 'Capacități tehnice', 'content', null],
            ['admin.res.index:industries', 'Aplicații și industrii', 'content', null],
            ['admin.res.index:projects', 'Proiecte', 'content', null],
            ['admin.res.index:documents', 'Documente', 'content', null],
            ['admin.res.index:faqs', 'Întrebări frecvente', 'content', null],
            ['admin.res.index:menu_items', 'Meniuri', 'content', null],
            ['admin.media.index', 'Bibliotecă media', 'media', null],
            ['admin.settings.edit:company', 'Companie, contact, subsol', 'content', null],
            ['admin.settings.edit:languages', 'Limbi', 'content', null],
        ],
        'Marketing' => [
            ['admin.settings.edit:seo', 'SEO', 'seo', null],
            ['admin.res.index:redirects', 'Redirect-uri', 'seo', null],
            ['admin.google.index', 'Google și marketing', 'google', null],
            ['admin.settings.edit:cookies', 'Cookies și consimțământ', 'google', null],
        ],
        'Asistent AI' => [
            ['admin.ai.index', 'Setări și statistici', 'ai', null],
            ['admin.res.index:knowledge', 'Bază de cunoștințe', 'ai.knowledge', null],
        ],
        'Sistem' => [
            ['admin.settings.edit:smtp', 'SMTP și notificări', 'smtp', $failedMail ?: null],
            ['admin.users.index', 'Utilizatori și roluri', 'users', null],
            ['admin.security.index', 'Securitate', 'security', null],
            ['admin.audit.index', 'Jurnal de audit', 'audit', null],
            ['admin.backups.index', 'Backup și restaurare', 'backups', null],
            ['admin.updates.index', 'Actualizări', 'updates', null],
        ],
    ];
    $cur = request()->url();
    $logo = media_item(setting('company.logo_dark_id'));
@endphp
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Administrare') | {{ setting('company.name', 'ELGAROM') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body>
<div class="shell">
    <aside class="side" id="admin-side" data-side>
        <a class="side__logo" href="{{ route('admin.dashboard') }}">@if ($logo)<img src="{{ $logo->url() }}" alt="">@endif <span>Administrare</span></a>
        <nav aria-label="Administrare">
            @foreach ($nav as $group => $items)
                @php $items = array_filter($items, fn ($i) => $u->can_($i[2])); @endphp
                @if ($items)
                    <h2>{{ $group }}</h2>
                    @foreach ($items as [$route, $label, $perm, $badge])
                        @php
                            [$name, $param] = array_pad(explode(':', $route), 2, null);
                            $href = $param ? route($name, $param) : route($name);
                            $active = $cur === $href || str_starts_with($cur, rtrim($href, '/').'/');
                            if ($route === 'admin.dashboard') { $active = $cur === $href; }
                        @endphp
                        <a href="{{ $href }}" @if ($active) aria-current="page" @endif><span>{{ $label }}</span>@if ($badge)<span class="badge {{ $route === 'admin.settings.edit:smtp' ? 'badge--err' : '' }}">{{ $badge }}</span>@endif</a>
                    @endforeach
                @endif
            @endforeach
        </nav>
    </aside>
    <div class="main">
        <header class="topbar">
            <button class="btn btn--line btn--sm menu-btn" type="button" data-side-toggle aria-controls="admin-side" aria-expanded="false">Meniu</button>
            <a href="{{ url('/') }}" target="_blank" rel="noopener" class="small">Vezi site-ul</a>
            <div class="btn-row">
                <a class="small" href="{{ route('admin.account') }}">{{ $u->name }} <span class="muted">({{ $u->roleLabel() }})</span></a>
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
