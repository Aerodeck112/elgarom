@extends('admin.auth._layout')
@section('title', 'Autentificare')
@section('content')
<h1>Autentificare</h1>
<form method="post" action="{{ route('admin.login.post') }}">
    @csrf
    <div class="field"><label for="email">Email</label><input class="input" id="email" name="email" type="email" autocomplete="username" required value="{{ old('email') }}" autofocus></div>
    <div class="field"><label for="password">Parolă</label><input class="input" id="password" name="password" type="password" autocomplete="current-password" required></div>
    <button class="btn" type="submit">Continuă</button>
</form>
<p class="small muted" style="margin-top:1.5rem">După parolă vi se cere codul din aplicația de autentificare (2FA). Autentificarea în doi pași este obligatorie.</p>
@endsection
