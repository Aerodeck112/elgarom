<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', setting('site.title', 'GRUP SIMEX SRL').' – soluții industriale integrate')</title>
    <meta name="description" content="@yield('description', setting('site.meta_description', 'Grup Simex: producție de piese metalice, proiectare și fabricație de ștanțe și matrițe, zincare electrolitică.'))">
    @unless (setting('site.indexable'))<meta name="robots" content="noindex, nofollow">@endunless
    <link rel="canonical" href="{{ url()->current() }}"><link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <meta property="og:type" content="website"><meta property="og:title" content="{{ setting('site.title', 'GRUP SIMEX SRL') }}"><meta property="og:url" content="{{ url('/') }}">
    @vite(['resources/css/site.css', 'resources/js/site.js'])
</head>
<body>
<a class="skip" href="#continut">Sari la conținut</a>
<header class="hdr" data-header><div class="wrap hdr__in">
    <a class="brandmark" href="{{ url('/') }}" aria-label="Grup Simex – acasă">@if ($group?->logoUrl())<img src="{{ $group->logoUrl() }}" alt="GRUP SIMEX">@else<span class="brandmark__sq">GS</span><span class="brandmark__tx">GRUP SIMEX</span>@endif</a>
    <nav aria-label="Principal"><a href="{{ url('/') }}#companii">Companii</a><a href="{{ url('/') }}#contact">Contact</a></nav>
</div></header>
<main id="continut">@yield('content')</main>
<footer class="ftr" id="contact"><div class="wrap ftr__grid">
    <div><div class="ftr__name">{{ $group?->legal_name ?: 'GRUP SIMEX SRL' }}</div>
        {{-- Legal data is printed only after an administrator has confirmed it. --}}
        @if ($group?->legal_confirmed_at)<p>@if ($group->tax_id)CUI {{ $group->tax_id }}@endif @if ($group->reg_no)· Nr. Reg. Com. {{ $group->reg_no }}@endif<br>{{ $group->fullAddress() }}</p>@endif</div>
    <div><h2>Companii</h2><ul>@foreach ($companies ?? [] as $c)@if ($c->website)<li><a href="{{ safe_url($c->website) }}" rel="noopener">{{ $c->name }}</a></li>@endif @endforeach</ul></div>
    <div><h2>Contact</h2><ul>@if (setting('site.contact_email'))<li><a href="mailto:{{ setting('site.contact_email') }}">{{ setting('site.contact_email') }}</a></li>@endif @if (setting('site.contact_phone'))<li>{{ setting('site.contact_phone') }}</li>@endif
        @if (filled(setting('site.privacy')))<li><a href="{{ route('privacy') }}">Politica de confidențialitate</a></li>@endif</ul></div>
</div><div class="wrap ftr__base">© {{ date('Y') }} {{ $group?->legal_name ?: 'GRUP SIMEX SRL' }}</div></footer>
</body></html>
