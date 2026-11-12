<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
    <title>@yield('title') | Administrare</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="auth">
    <div class="auth__box">
        <div class="auth__brand">{{ setting('app.name', 'GRUP SIMEX') }}<small>Industrial CRM</small></div>
        @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="alert alert--err" role="alert">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @yield('content')
    </div>
</main>
</body>
</html>
