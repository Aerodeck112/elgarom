@php
    $siteName = setting('seo.site_name', 'ELGAROM');
    $metaTitle = $metaTitle ?? $siteName;
    $metaDescription = $metaDescription ?? setting('seo.default_description');
    $canonical = $canonical ?? url()->current();
    $ogImage = media_item($ogImageId ?? null) ?? media_item(setting('seo.default_og_image_id'));
    $logoDark = media_item(setting('company.logo_dark_id'));
    $gsc = setting('google.gsc_verification');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $metaTitle }}</title>
    @if ($metaDescription)<meta name="description" content="{{ $metaDescription }}">@endif
    <link rel="canonical" href="{{ $canonical }}">
    @if (! empty($noindex))<meta name="robots" content="noindex, nofollow">@endif
    @if ($gsc && preg_match('/^[A-Za-z0-9_\-]{10,100}$/', $gsc))<meta name="google-site-verification" content="{{ $gsc }}">@endif
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    @if ($metaDescription)<meta property="og:description" content="{{ $metaDescription }}">@endif
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:locale" content="ro_RO">
    @if ($ogImage)<meta property="og:image" content="{{ $ogImage->variantUrl(1200, 'webp') }}"><meta property="og:image:alt" content="{{ $ogImage->alt }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#20262b">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @foreach (config('elgarom.locales') as $code => $loc)
        @if ($loc['published'] && isset($alternates[$code]))<link rel="alternate" hreflang="{{ $code }}" href="{{ $alternates[$code] }}">@endif
    @endforeach
    <script nonce="{{ csp_nonce() }}">(function(d){d.classList.replace('no-js','js');if(!document.querySelector)return;var s=matchMedia('(prefers-reduced-motion: reduce)').matches||(navigator.connection&&navigator.connection.saveData);if(!s&&!/scene-capture/.test(location.hash)){try{var c=document.createElement('canvas');s=!(c.getContext('webgl2')||c.getContext('webgl'));}catch(e){s=true;}}if(s)d.classList.add('scene-static');})(document.documentElement);</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    <script type="application/ld+json" nonce="{{ csp_nonce() }}">{!! json_encode(\App\Support\StructuredData::organization(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @foreach (($structured ?? []) as $sd)
        <script type="application/ld+json" nonce="{{ csp_nonce() }}">{!! json_encode($sd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endforeach
    <script type="application/json" id="elg-config">{!! json_encode(\App\Support\TagConfig::public(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) !!}</script>
</head>
<body class="{{ $bodyClass ?? '' }}">
    <a class="skip-link" href="#continut">Sari la conținut</a>
    @include('partials.header', ['logo' => $logoDark])
    <main id="continut" tabindex="-1">
        @yield('content')
    </main>
    @include('partials.footer', ['logo' => $logoDark])
    @include('partials.consent')
    @include('partials.assistant')
    @stack('end')
</body>
</html>
