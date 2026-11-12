@extends('admin.auth._layout')
@section('title', 'Cod 2FA')
@section('content')
<h1>Cod de verificare</h1>
<p>Introduceți codul de 6 cifre din aplicația de autentificare.</p>
<form method="post" action="{{ route('admin.2fa.challenge.post') }}">
    @csrf
    <div class="field"><label for="code">Cod 2FA</label><input class="input" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]{6,7}" maxlength="7" autofocus></div>
    <details style="margin-bottom:1rem"><summary class="small">Nu am acces la aplicație – folosesc un cod de recuperare</summary>
        <div class="field" style="margin-top:.75rem"><label for="recovery_code">Cod de recuperare</label><input class="input" id="recovery_code" name="recovery_code" autocomplete="off" maxlength="20"></div>
    </details>
    <button class="btn" type="submit">Verifică</button>
</form>
<form method="post" action="{{ route('admin.logout') }}" style="margin-top:1rem">@csrf<button class="link-btn small" type="submit">Anulează și ieși</button></form>
@endsection
