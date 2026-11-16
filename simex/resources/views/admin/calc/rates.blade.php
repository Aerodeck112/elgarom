@extends('admin.layout')
@section('title', 'Tarife')
@section('content')
<div class="page-head"><div><h1 class="page-title">Tarife și parametri de calcul</h1>
    <p class="small muted">Tarifele vin din foile de parametri ale fiecărui model Excel validat. O modificare se salvează ca versiune nouă și se aplică doar calculelor viitoare; calculele salvate își păstrează tarifele din momentul salvării.</p></div></div>
@unless (can('rates.view'))
    <div class="alert alert--info">Contul dumneavoastră nu are acces la tarifele interne. Calculatoarele le folosesc totuși, pe server.</div>
@endunless
<div class="grid-2x" style="margin-bottom:20px">
@foreach ($models as $m)
    <div class="panel">
        <h2>{{ $m['def']->title }} <span class="tag {{ $m['version'] ? 'tag--ok' : 'tag--warn' }}">{{ $m['version'] ? 'model activ' : 'model neîncărcat' }}</span></h2>
        <p class="small muted" style="margin-top:-6px">{{ $m['def']->process }} · {{ $m['version']?->original_name ?? $m['def']->file_hint }} · parametri v{{ $m['params']?->version ?? 0 }}@if ($m['params']?->note) — {{ $m['params']->note }}@endif</p>
        @if ($m['version'] && $m['sheets'])
            <div class="btn-row">@foreach ($m['sheets'] as $name => $s)<a class="btn btn--sm btn--line" href="{{ route('admin.calc.create', $m['def']->key()) }}#s-{{ \Illuminate\Support\Str::slug($name) }}">{{ $s['label'] }}</a>@endforeach</div>
            <p class="hint" style="margin-top:8px">@if (can('rates.edit'))Deschideți foaia, modificați celulele și apăsați «Salvează parametrii» din secțiunea finală.@else Doar vizualizare; modificarea cere permisiunea «Modificarea tarifelor».@endif</p>
        @elseif (! $m['version'])
            <p class="small">Încărcați fișierul Excel la Mai multe → Modele Excel.</p>
        @endif
    </div>
@endforeach
</div>
@if ($machines->isNotEmpty())
<div class="panel panel--flush"><div class="panel__title">Tarife orare utilaje @if (can('library.manage'))<a class="link small" href="{{ route('admin.res.index', 'machines') }}">Modifică →</a>@endif</div>
    <div class="table-wrap" style="border:0"><table class="t"><thead><tr><th>Utilaj</th><th>Companie</th>@if (can('costs.view'))<th class="num">Tarif orar</th><th>Valabil din</th>@endif<th>Activ</th></tr></thead><tbody>
    @foreach ($machines as $mc)<tr><td>{{ $mc->name }}</td><td>{{ $mc->entity?->name }}</td>
        @if (can('costs.view'))<td class="num">{{ $mc->hourly_rate === null ? 'neconfigurat' : money((float) $mc->hourly_rate).' '.$mc->currency }}</td><td class="mono small">{{ $mc->rate_date?->format('d.m.Y') ?? '—' }}</td>@endif
        <td>{{ $mc->is_active ? 'da' : 'nu' }}</td></tr>@endforeach
    </tbody></table></div></div>
@endif
@endsection
