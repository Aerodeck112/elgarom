@extends('admin.layout')
@section('title', 'Confirmare actualizare')
@section('content')
<h1>Actualizare {{ config('elgarom.version') }} → {{ $m['version'] }}</h1>
<div class="panel panel--ok"><p class="status status--ok">Semnătură validă (cheia {{ $m['_key_id'] }}). Toate fișierele corespund manifestului.</p>
<dl class="kv"><dt>Versiune minimă necesară</dt><dd>{{ $m['min_from'] }}</dd><dt>PHP necesar</dt><dd>{{ $m['php_min'] ?? '—' }} (server: {{ PHP_VERSION }})</dd>
<dt>Fișiere</dt><dd>{{ count($m['files']) }} în pachet: {{ $changed }} modificate, {{ $new }} noi, {{ count($m['delete'] ?? []) }} șterse</dd>
<dt>Data emiterii</dt><dd>{{ $m['released_at'] ?? '—' }}</dd><dt>SHA-256 pachet</dt><dd><code class="small">{{ $m['_sha256'] }}</code></dd>
<dt>Spațiu liber</dt><dd>{{ $free ? number_format($free / 1073741824, 1, ',', '').' GB' : 'necunoscut' }}</dd></dl></div>
<div class="panel"><h2>Modificări</h2><div class="pre">{{ $m['changelog'] ?? 'Fără descriere.' }}</div></div>
<form class="panel panel--warn" method="post" action="{{ route('admin.updates.install') }}">@csrf
    <h2>Instalare</h2>
    <p class="small">Se va crea și verifica un backup al bazei de date și al fișierelor afectate, site-ul intră în mentenanță câteva momente, apoi rulează migrațiile și verificările. La eroare, totul se restaurează automat. Conținutul, utilizatorii, cererile, conversațiile, setările, fișierele încărcate și .env nu sunt atinse.</p>
    <div class="grid-2"><div class="field"><label for="password">Parola dvs.</label><input class="input" id="password" name="password" type="password" autocomplete="current-password" required></div>
    <div class="field"><label for="code">Cod 2FA nou</label><input class="input" id="code" name="code" inputmode="numeric" maxlength="7" autocomplete="one-time-code" required></div></div>
    <div class="btn-row"><button class="btn" type="submit">Instalează actualizarea</button></div>
</form>
<form method="post" action="{{ route('admin.updates.cancel') }}">@csrf<button class="btn btn--line btn--sm" type="submit">Renunță și șterge pachetul</button></form>
@endsection
