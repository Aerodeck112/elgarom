@extends('admin.layout')
@section('title', 'Modele Excel')
@section('content')
@php use App\Support\Vocab; @endphp
<div class="page-head"><div><h1 class="page-title">Modele Excel de calcul</h1><p class="lead">Fiecare calculator rulează formulele unui workbook. La încărcare, toate formulele sunt recalculate de motorul platformei și comparate cu rezultatele salvate de Excel. Un model poate fi activat numai cu zero diferențe. Macro-urile nu sunt citite și nu sunt executate.</p></div></div>
<form class="panel form-grid" method="post" action="{{ route('admin.models.upload') }}" enctype="multipart/form-data" style="max-width:900px;margin-bottom:20px">@csrf
    <div class="field col-4"><label for="model_key">Calculator</label><select class="select" id="model_key" name="model_key" required>@foreach ($defs as $d)<option value="{{ $d->key() }}">{{ $d->title }}</option>@endforeach</select></div>
    <div class="field col-4"><label for="file">Fișier .xlsx</label><input class="input" id="file" name="file" type="file" accept=".xlsx" required></div>
    <div class="field col-4"><label for="label">Etichetă versiune</label><input class="input" id="label" name="label" maxlength="60" placeholder="ex. v18"></div>
    <div class="col-12"><button class="btn" type="submit">Încarcă și validează</button></div>
</form>
@foreach ($defs as $d)
<div class="panel panel--flush" style="margin-bottom:16px"><div class="panel__title"><span>{{ $d->title }} <span class="muted small">· fișier așteptat: {{ $d->file_hint }}</span></span>
    @if (($versions[$d->key()] ?? collect())->firstWhere('status', 'activ'))<span class="tag tag--ok">activ</span>@else<span class="pending">Formulă în curs de configurare</span>@endif</div>
    <table class="t"><thead><tr><th>Fișier</th><th>Etichetă</th><th>Stare</th><th class="num">Formule</th><th class="num">Diferențe</th><th>Încărcat</th><th></th></tr></thead><tbody>
    @forelse ($versions[$d->key()] ?? [] as $v)
        <tr><td class="wrap">{{ $v->original_name }}<div class="small muted mono">{{ substr($v->sha256, 0, 16) }}…</div>
            @if ($v->status === 'respins')<ul class="small status--err" style="margin:6px 0 0;padding-left:16px">@foreach ($v->report['errors'] ?? [] as $e)<li>{{ $e }}</li>@endforeach @foreach (array_slice($v->report['samples'] ?? [], 0, 5) as $s)<li class="mono">{{ $s['cell'] }}: Excel «{{ $s['excel'] }}» ≠ motor «{{ $s['engine'] }}»</li>@endforeach</ul>@endif
            @foreach ($v->report['warnings'] ?? [] as $w)<div class="small status--warn">{{ $w }}</div>@endforeach</td>
            <td>{{ $v->label ?: '—' }}</td><td><span class="tag tag--{{ Vocab::tone($v->status) }}">{{ $v->status }}</span></td><td class="num">{{ number_format($v->formulas, 0, ',', '.') }}</td><td class="num">{{ $v->mismatches }}</td>
            <td class="small">{{ $v->created_at->format('d.m.Y H:i') }}<br>{{ $v->uploader?->name }}</td>
            <td class="actions">@if (in_array($v->status, ['validat', 'retras'], true))<form method="post" action="{{ route('admin.models.activate', $v) }}" data-confirm="Activați acest model? Calculele noi îl vor folosi.">@csrf<button class="btn btn--sm" type="submit">Activează</button></form>@endif</td></tr>
    @empty <tr><td colspan="7"><div class="empty">Niciun fișier încărcat.</div></td></tr>
    @endforelse</tbody></table></div>
@endforeach
@endsection
