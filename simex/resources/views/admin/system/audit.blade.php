@extends('admin.layout')
@section('title', 'Jurnal de audit')
@section('content')
<div class="page-title"><div><h1>Jurnal de audit</h1><p>Autentificări, modificări, exporturi, backup-uri și actualizări. Parolele și cheile nu sunt înregistrate.</p></div></div>
<form class="filters" method="get">
    <div class="field"><label for="a">Acțiune</label><select class="select" id="a" name="action"><option value="">Toate</option>@foreach ($prefixes as $p)<option @selected(request('action') === $p)>{{ $p }}</option>@endforeach</select></div>
    <div class="field"><label for="u">Utilizator</label><select class="select" id="u" name="user"><option value="">Toți</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected(request('user') == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
    <div class="field"><label for="from">De la</label><input class="input" type="date" id="from" name="from" value="{{ request('from') }}"></div>
    <div class="field"><label for="to">Până la</label><input class="input" type="date" id="to" name="to" value="{{ request('to') }}"></div>
    <button class="btn btn--line" type="submit">Filtrează</button>
</form>
<div class="table-wrap"><table class="t"><thead><tr><th>Data</th><th>Utilizator</th><th>Acțiune</th><th>Obiect</th><th>IP</th><th>Detalii</th></tr></thead><tbody>
@foreach ($items as $l)
<tr><td class="small" style="white-space:nowrap">{{ $l->created_at->format('d.m.Y H:i:s') }}</td><td class="small">{{ $l->user?->name ?? '—' }}</td><td><code class="small">{{ $l->action }}</code></td><td class="small">{{ $l->subject_type }} {{ $l->subject_id }}</td><td class="small">{{ $l->ip }}</td><td class="small">{{ \Illuminate\Support\Str::limit(json_encode($l->data, JSON_UNESCAPED_UNICODE), 140) }}</td></tr>
@endforeach
</tbody></table></div>
{{ $items->links('admin.partials.pagination') }}
@endsection
