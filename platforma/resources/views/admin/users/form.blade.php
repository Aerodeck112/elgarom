@extends('admin.layout')
@section('title', $user->exists ? $user->name : 'Utilizator nou')
@section('content')
<div class="page-title"><div><p><a href="{{ route('admin.users.index') }}">Utilizatori</a></p><h1>{{ $user->exists ? $user->name : 'Utilizator nou' }}</h1></div></div>
@if (session('temp_password'))
    <div class="panel panel--warn"><h2>Parolă temporară</h2><p>Afișată o singură dată. Transmiteți-o pe un canal sigur; utilizatorul o poate schimba din „Contul meu”.</p><p><code style="font-size:1.1rem">{{ session('temp_password') }}</code> <button class="btn btn--line btn--sm" type="button" data-copy="{{ session('temp_password') }}">Copiază</button></p></div>
@endif
<form class="panel" method="post" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
    @csrf @if ($user->exists) @method('PUT') @endif
    <div class="grid-2">
        <div class="field"><label for="name">Nume</label><input class="input" id="name" name="name" required maxlength="120" value="{{ old('name', $user->name) }}"></div>
        <div class="field"><label for="email">Email</label><input class="input" id="email" name="email" type="email" required value="{{ old('email', $user->email) }}"></div>
    </div>
    <div class="field"><label for="role">Rol</label><select class="select" id="role" name="role">@foreach (\App\Models\User::ROLE_LABELS as $k => $l)<option value="{{ $k }}" @selected(old('role', $user->role) === $k)>{{ $l }}</option>@endforeach</select></div>
    @if ($user->exists)
        <input type="hidden" name="is_active" value="0"><label class="check" style="margin-bottom:1rem"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))> <span>Cont activ</span></label>
    @endif
    <button class="btn" type="submit">{{ $user->exists ? 'Salvează' : 'Creează contul' }}</button>
</form>
@if ($user->exists)
<div class="panel"><h2>Securitate cont</h2>
    <p>2FA: @if ($user->hasTwoFactor())<span class="tag tag--ok">activ din {{ $user->two_factor_confirmed_at->format('d.m.Y') }}</span>@else<span class="tag tag--warn">neconfigurat</span>@endif, ultima autentificare: {{ $user->last_login_at?->format('d.m.Y H:i') ?? '—' }} {{ $user->last_login_ip }}</p>
    <div class="btn-row">
        <form method="post" action="{{ route('admin.users.reset-password', $user) }}" data-confirm="Generați o parolă temporară nouă și închideți sesiunile?">@csrf<button class="btn btn--line btn--sm" type="submit">Resetează parola</button></form>
        <form method="post" action="{{ route('admin.users.reset-2fa', $user) }}" data-confirm="Resetați 2FA? Utilizatorul va trebui să îl configureze din nou.">@csrf<button class="btn btn--line btn--sm" type="submit">Resetează 2FA</button></form>
        <form method="post" action="{{ route('admin.users.revoke', $user) }}">@csrf<button class="btn btn--line btn--sm" type="submit">Închide toate sesiunile</button></form>
        <form method="post" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Ștergeți definitiv utilizatorul?">@csrf @method('DELETE')<button class="btn btn--danger btn--sm" type="submit">Șterge utilizatorul</button></form>
    </div>
    <h3 style="margin-top:1.25rem">Sesiuni active</h3>
    @include('admin.users.sessions', ['sessions' => $sessions])
</div>
@endif
@endsection
