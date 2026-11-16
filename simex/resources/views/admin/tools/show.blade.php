@extends('admin.layout')
@section('title', $tool->reference)
@section('content')
@php use App\Support\Vocab; $see = can('costs.view'); $amort = $tool->amortisationPerPiece(); @endphp
<div class="page-head"><div><p class="eyebrow"><a href="{{ route('admin.tools.index') }}">Ștanțe și matrițe</a> · <span class="mono">{{ $tool->reference }}</span></p><h1 class="page-title">{{ $tool->name }}</h1>
    <p class="small muted">{{ $tool->typeLabel() }} · {{ $tool->company?->name ?? 'fără client' }} · complexitate {{ Vocab::COMPLEXITIES[$tool->complexity] ?? '—' }} · termen {{ $tool->lead_time ?: '—' }}</p></div><a class="btn" href="{{ route('admin.tools.edit', $tool) }}">Modifică</a></div>
<div class="alert alert--info">Orele de prelucrare provin din calculatoarele validate (frezare, strunjire, electroeroziune, rectificare). Celelalte costuri sunt introduse de inginer. Formula de preț FEPO pentru ștanțe nu a fost furnizată într-un Excel, deci aici se adună doar costurile declarate, fără adaosuri.</div>
@if ($see)
<div class="grid-4" style="margin-bottom:18px">
    @foreach (Vocab::TOOL_PHASES as $k => $label)<div class="stat"><div class="stat__label">{{ $label }}</div><div class="stat__value">@if ($totals['phases'][$k]['total'] === null)<span class="pending">incomplet</span>@else{{ money($totals['phases'][$k]['total']) }}@endif</div><div class="stat__hint">{{ $totals['phases'][$k]['lines'] }} linii</div></div>@endforeach
    <div class="stat stat--accent"><div class="stat__label">Cost total sculă</div><div class="stat__value">@if ($totals['total'] === null)<span class="pending">{{ $totals['missing'] }} linii fără tarif</span>@else{{ money($totals['total']) }} <small class="small muted">{{ $tool->currency }}</small>@endif</div>
        <div class="stat__hint">@if ($amort !== null)Amortizare: {{ money($amort, 4) }} {{ $tool->currency }}/piesă la {{ qty($tool->amortization_quantity) }} buc @else Amortizare: setați cantitatea și completați tarifele @endif</div></div>
</div>
@endif
<div class="panel panel--flush" style="margin-bottom:18px"><div class="panel__title">Linii de cost</div>
<table class="t"><thead><tr><th>Fază</th><th>Categorie</th><th>Descriere</th><th class="num">Cantitate</th><th>U.M.</th>@if ($see)<th class="num">Cost unitar</th><th class="num">Total</th>@endif<th></th></tr></thead><tbody>
@forelse ($tool->lines as $l)
    <tr><td>{{ Vocab::TOOL_PHASES[$l->phase] ?? $l->phase }}</td><td>{{ Vocab::TOOL_CATEGORIES[$l->category] ?? $l->category }}</td><td>{{ $l->description ?: '—' }} @if ($l->calculation)<a class="link small mono" href="{{ route('admin.calc.show', $l->calculation) }}">{{ $l->calculation->reference }}</a>@endif</td>
        <td class="num">{{ money($l->quantity) }}</td><td>{{ $l->unit }}</td>@if ($see)<td class="num">@if ($l->unit_cost === null)<span class="pending">tarif neconfigurat</span>@else{{ money($l->unit_cost) }}@endif</td><td class="num">{{ money($l->total()) }}</td>@endif
        <td class="actions"><form method="post" action="{{ route('admin.tools.lines.destroy', [$tool, $l]) }}" data-confirm="Ștergeți linia?">@csrf @method('DELETE')<button class="link-btn link-btn--danger small" type="submit">Șterge</button></form></td></tr>
@empty <tr><td colspan="8"><div class="empty">Nicio linie de cost. Adăugați ore de proiectare, prelucrări, materiale, componente.</div></td></tr>
@endforelse</tbody></table></div>
<form class="panel form-grid" method="post" action="{{ route('admin.tools.lines.store', $tool) }}">@csrf
    <h2 class="span-12" style="grid-column:span 12">Adaugă linie de cost</h2>
    <div class="field span-3"><label>Fază</label><select class="select" name="phase">@foreach (Vocab::TOOL_PHASES as $k => $l)<option value="{{ $k }}" @selected($k === 'realizare')>{{ $l }}</option>@endforeach</select></div>
    <div class="field span-3"><label>Categorie</label><select class="select" name="category">@foreach (Vocab::TOOL_CATEGORIES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
    <div class="field span-6"><label>Descriere</label><input class="input" name="description" maxlength="255"></div>
    <div class="field span-6"><label>Din calcul salvat (ore validate)</label><select class="select" name="calculation_id"><option value="">— introducere manuală —</option>@foreach ($calculations as $c)<option value="{{ $c->id }}">{{ $c->reference }} · {{ \Illuminate\Support\Str::limit($c->title, 40) }} · {{ money($c->hours) }} h</option>@endforeach</select><p class="hint">Orele și tariful utilajului se preiau din calcul.</p></div>
    <div class="field span-2"><label>Cantitate</label><input class="input input--num" name="quantity" inputmode="decimal"></div>
    <div class="field span-2" style="grid-column:span 2"><label>U.M.</label><select class="select" name="unit">@foreach (['h', 'buc', 'kg', 'set', 'lot'] as $u)<option>{{ $u }}</option>@endforeach</select></div>
    <div class="field span-2" style="grid-column:span 2"><label>Cost unitar</label><input class="input input--num" name="unit_cost" inputmode="decimal" @disabled(! $see)></div>
    <div class="field span-6"><label>Utilaj (pentru tariful orar)</label><select class="select" name="machine_id"><option value="">—</option>@foreach ($machines as $m)<option value="{{ $m->id }}">{{ $m->name }}@if ($m->hourly_rate === null) · tarif neconfigurat @endif</option>@endforeach</select></div>
    <div class="span-12" style="grid-column:span 12"><button class="btn btn--sm" type="submit">Adaugă linia</button></div>
</form>
<form class="panel" method="post" action="{{ route('admin.tools.update', $tool) }}" style="margin-top:18px">@csrf @method('PUT')
    @foreach (['name', 'type', 'company_id', 'complexity', 'operations_count', 'lead_time', 'amortization_quantity', 'currency', 'notes'] as $k)<input type="hidden" name="{{ $k }}" value="{{ $tool->{$k} }}">@endforeach
    <h2>Piese produse cu această sculă</h2><input type="hidden" name="products[]" value="">
    <div class="btn-row">@forelse ($products as $p)<label class="check"><input type="checkbox" name="products[]" value="{{ $p->id }}" @checked($tool->products->contains($p->id))> <span class="mono">{{ $p->code }}</span> {{ $p->name }}</label>@empty<span class="muted small">Nu există piese înregistrate pentru acest client (Produse și proiecte).</span>@endforelse</div>
    @if ($products->isNotEmpty())<button class="btn btn--sm btn--line" type="submit" style="margin-top:12px">Salvează asocierea</button>@endif
</form>
@endsection
