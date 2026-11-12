<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') | GRUP SIMEX</title>
    @vite(['resources/css/app.css'])
</head>
<body>
<main class="page-head on-dark error-page" style="padding-top:6rem">
    <div class="wrap">
        <p class="cota" style="color:var(--teal-deschis);font-size:var(--t-lg);margin:0 0 1rem">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p class="lead">@yield('message')</p>
        <div class="btn-row" style="margin-top:2rem">
            <a class="btn" href="{{ url('/') }}">{{ __('Pagina principală') }}</a>
        </div>
    </div>
</main>
</body>
</html>
