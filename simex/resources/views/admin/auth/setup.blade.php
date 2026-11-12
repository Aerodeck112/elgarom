@extends('admin.auth._layout')
@section('title', 'Configurare 2FA')
@section('content')
<h1>{{ $reset ? 'Reconfigurare 2FA' : 'Activați autentificarea în doi pași' }}</h1>
<p>Accesul în administrare necesită 2FA. Scanați codul QR cu o aplicație de autentificare (Google Authenticator, Microsoft Authenticator, 1Password, Aegis etc.), apoi introduceți codul afișat.</p>
<div class="qr" aria-hidden="true">{!! $qr !!}</div>
<details style="margin:1rem 0"><summary class="small">Nu pot scana – introduc cheia manual</summary><p class="small" style="word-break:break-all;font-family:monospace;margin-top:.5rem">{{ trim(chunk_split($secret, 4, ' ')) }}</p></details>
<form method="post" action="{{ route('admin.2fa.setup.post') }}">
    @csrf
    <div class="field"><label for="code">Cod din aplicație</label><input class="input" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required autofocus></div>
    <button class="btn" type="submit">Activează 2FA</button>
</form>
<form method="post" action="{{ route('admin.logout') }}" style="margin-top:1rem">@csrf<button class="link-btn small" type="submit">Ieși</button></form>
@endsection
