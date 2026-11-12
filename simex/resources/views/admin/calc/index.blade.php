@extends('admin.layout')
@section('title', $area === 'piese' ? 'Calculator piese metalice' : 'Calculator ștanțe și matrițe')
@section('content')
@php use App\Support\Vocab; @endphp
<div class="page-head"><div><p class="eyebrow">{{ $area === 'piese' ? 'ARGENTO · producție piese' : 'FEPO · ștanțe, matrițe, scule' }}</p><h1 class="page-title">{{ $area === 'piese' ? 'Calculator piese metalice' : 'Calculator ștanțe și matrițe' }}</h1>
    <p class="lead">Calculele rulează pe server, pe modelele Excel validate formulă cu formulă. Rezultatul este același cu al fișierului original.</p></div>
    @if ($area === 'stante')<a class="btn btn--accent" href="{{ route('admin.tools.index') }}">Proiecte de ștanțe și matrițe</a>@endif</div>
<div class="grid-3" style="margin-bottom:22px">
@foreach ($models as $def)
    @php $v = $active[$def->key()] ?? null; @endphp
    <div class="panel"><p class="eyebrow">{{ $def->process }}</p><h2 style="font-size:15px">{{ $def->title }}</h2><p class="small muted">{{ $def->subtitle }}</p>
        @if ($v)<p><span class="tag tag--ok">Formule validate</span> <span class="small muted">{{ number_format($v->formulas, 0, ',', '.') }} formule · 0 diferențe</span></p><a class="btn btn--sm" href="{{ route('admin.calc.create', $def->key()) }}">Calcul nou</a>
        @else<p><span class="pending">Formulă în curs de configurare</span></p><p class="small muted">Modelul Excel nu este încă încărcat și activat.</p>@if (can('formulas.manage'))<a class="btn btn--sm btn--line" href="{{ route('admin.models.index') }}">Încarcă modelul</a>@endif @endif</div>
@endforeach
@if ($area === 'piese')
    @foreach (['Ștanțare', 'Găurire', 'Frezare în serie', 'Strunjire în serie', 'Montaj', 'Transport'] as $op)
    <div class="panel"><p class="eyebrow">{{ $op }}</p><h2 style="font-size:15px">{{ $op }} – piese de serie</h2><p><span class="pending">Formulă în curs de configurare</span></p><p class="small muted">Nu există încă un fișier Excel validat pentru această operație. Costul poate fi introdus manual în ofertă.</p></div>
    @endforeach
@endif
</div>
<div class="panel panel--flush"><div class="panel__title">Calcule salvate</div><table class="t"><thead><tr><th>Referință</th><th>Titlu</th><th>Calculator</th><th>Client</th><th>Stare</th><th class="num">Rezultat</th><th>Autor</th><th>Data</th></tr></thead><tbody>
@forelse ($calculations as $c)
    <tr><td class="mono"><a class="link" href="{{ route('admin.calc.show', $c) }}">{{ $c->reference }}</a>@if ($c->isLocked()) <span class="tag">blocat</span>@endif</td><td>{{ $c->title }}</td><td class="small">{{ $models[$c->model_key]->process ?? $c->model_key }}</td><td>{{ $c->company?->name ?? '—' }}</td>
        <td><span class="tag tag--{{ Vocab::tone($c->state) }}">{{ ['ok' => 'calculat', 'warn' => 'cu avertizări', 'block' => 'incomplet'][$c->state] ?? $c->state }}</span></td>
        <td class="num">@if ($c->hours !== null){{ money($c->hours) }} ore @elseif ($c->total !== null && can('costs.view')){{ money($c->total) }} {{ $c->currency }}@else — @endif</td><td>{{ $c->author?->name ?? '—' }}</td><td class="mono small">{{ $c->updated_at->format('d.m.Y') }}</td></tr>
@empty <tr><td colspan="8"><div class="empty">Niciun calcul salvat.</div></td></tr>
@endforelse</tbody></table></div>
@include('admin.partials.pagination', ['paginator' => $calculations])
@endsection
