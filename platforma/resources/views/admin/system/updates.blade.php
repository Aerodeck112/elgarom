@extends('admin.layout')
@section('title', 'Actualizări')
@section('content')
<div class="page-title"><div><h1>Actualizări</h1><p>Versiunea instalată: <strong>{{ config('elgarom.version') }}</strong>. Se acceptă doar pachete semnate digital de emitentul de încredere.</p></div></div>
<form class="panel" method="post" action="{{ route('admin.updates.upload') }}" enctype="multipart/form-data">@csrf
    <h2>Încarcă pachet de actualizare</h2>
    <p class="small muted">Pachetul este verificat (semnătură Ed25519, hash-uri, versiuni, cerințe, căi) înainte de orice modificare. Limita de upload a serverului: {{ $maxUpload }}. Pentru pachete mai mari, urcați fișierul prin cPanel → File Manager în <code>storage/app/private/updates/incoming/</code> și alegeți-l din listă.</p>
    <div class="filters">
        <div class="field"><label for="package">Fișier .zip</label><input class="input" type="file" id="package" name="package" accept=".zip"></div>
        @if ($incoming)<div class="field"><label for="incoming">sau din dosarul incoming</label><select class="select" id="incoming" name="incoming"><option value="">—</option>@foreach ($incoming as $f)<option>{{ $f }}</option>@endforeach</select></div>@endif
        <button class="btn" type="submit">Verifică pachetul</button>
    </div>
</form>
<div class="panel"><h2>Istoric</h2>
@if ($history->isEmpty())<p class="muted">Nicio actualizare instalată.</p>@else
<div class="table-wrap"><table class="t"><thead><tr><th>Data</th><th>Versiune</th><th>Stare</th><th>De către</th><th>Jurnal</th></tr></thead><tbody>
@foreach ($history as $h)
<tr><td class="small">{{ $h->started_at?->format('d.m.Y H:i') }}</td><td>{{ $h->version_from }} → {{ $h->version_to }}</td>
<td><span class="tag {{ $h->status === 'instalat' ? 'tag--ok' : ($h->status === 'in_curs' ? 'tag--warn' : 'tag--err') }}">{{ ['instalat' => 'instalat', 'esuat_restaurat' => 'eșuat, restaurat', 'esuat_critic' => 'eșuat, necesită intervenție', 'in_curs' => 'în curs'][$h->status] ?? $h->status }}</span></td>
<td class="small">{{ $h->user?->name }}</td><td><details><summary class="small">Vezi</summary><div class="log">{{ $h->log }}</div>@if ($h->changelog)<div class="small pre" style="margin-top:.5rem">{{ $h->changelog }}</div>@endif</details></td></tr>
@endforeach
</tbody></table></div>@endif
</div>
<div class="panel"><h2>Chei de semnare acceptate</h2><ul class="small">@foreach ($keys as $id => $fp)<li><code>{{ $id }}</code> – amprentă {{ $fp }}</li>@endforeach</ul>
<p class="small muted">Cheile publice fac parte din cod (config/elgarom.php) și pot fi schimbate doar printr-o actualizare semnată cu o cheie deja acceptată. Procedura de recuperare dacă panoul nu mai pornește: docs/ACTUALIZARI.md.</p></div>
@endsection
