@extends('admin.layout')
@section('title', 'Contul meu')
@section('content')
<h1>Contul meu</h1>
<div class="grid">
<form class="panel" method="post" action="{{ route('admin.account.password') }}">@csrf
    <h2>Schimbă parola</h2>
    <div class="field"><label for="cp">Parola actuală</label><input class="input" id="cp" name="current_password" type="password" autocomplete="current-password" required></div>
    <div class="field"><label for="np">Parola nouă</label><input class="input" id="np" name="password" type="password" autocomplete="new-password" required minlength="12"><p class="hint">Minimum 12 caractere, litere mari și mici, cifre.</p></div>
    <div class="field"><label for="np2">Confirmă parola nouă</label><input class="input" id="np2" name="password_confirmation" type="password" autocomplete="new-password" required></div>
    <button class="btn" type="submit">Schimbă parola</button>
</form>
<div class="panel"><h2>Autentificare în doi pași</h2>
    <p>Activă din {{ $user->two_factor_confirmed_at?->format('d.m.Y') }}. Coduri de recuperare rămase: <strong>{{ $codesLeft }}</strong>.</p>
    <div class="btn-row">
        <form method="post" action="{{ route('admin.account.codes') }}" data-confirm="Codurile vechi nu vor mai funcționa. Continuați?">@csrf<button class="btn btn--line btn--sm" type="submit">Generează coduri noi</button></form>
        <form method="post" action="{{ route('admin.account.2fa') }}">@csrf<button class="btn btn--line btn--sm" type="submit">Mută 2FA pe alt telefon</button></form>
    </div>
</div>
</div>
<div class="panel"><div class="page-title"><h2>Sesiunile mele</h2><form method="post" action="{{ route('admin.account.revoke') }}">@csrf<button class="btn btn--line btn--sm" type="submit">Închide celelalte sesiuni</button></form></div>
@include('admin.users.sessions', ['sessions' => $sessions])</div>
@endsection
