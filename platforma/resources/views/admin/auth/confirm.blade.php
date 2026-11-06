@extends('admin.auth._layout')
@section('title', 'Confirmare')
@section('content')
<h1>Confirmați identitatea</h1>
<p>Operațiunea cerută este sensibilă. Introduceți parola și un cod 2FA nou (nefolosit anterior).</p>
<form method="post" action="{{ route('admin.confirm.post') }}">
    @csrf
    <div class="field"><label for="password">Parolă</label><input class="input" id="password" name="password" type="password" autocomplete="current-password" required autofocus></div>
    <div class="field"><label for="code">Cod 2FA nou</label><input class="input" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required></div>
    <div class="btn-row"><button class="btn" type="submit">Confirmă</button><a class="btn btn--line" href="{{ route('admin.dashboard') }}">Renunță</a></div>
</form>
@endsection
