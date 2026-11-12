@extends('admin.layout')
@section('title', $rfq->number)
@section('content')
@php use App\Support\Vocab; $manage = can('rfq.manage'); @endphp
<div class="page-head">
    <div><p class="eyebrow"><a href="{{ route('admin.rfqs.index') }}">Cereri de ofertă</a> · <span class="mono">{{ $rfq->number }}</span></p><h1 class="page-title">{{ $rfq->title }}</h1>
        <div class="btn-row" style="margin-top:6px"><span class="tag tag--{{ Vocab::tone($rfq->status) }}">{{ $rfq->statusLabel() }}</span><span class="tag tag--{{ Vocab::tone($rfq->priority) }}">{{ Vocab::PRIORITIES[$rfq->priority] ?? '' }}</span>@if ($rfq->isOverdue())<span class="tag tag--err">termen depășit</span>@endif</div></div>
    <div class="btn-row">@if (can('quotes.manage'))<a class="btn btn--accent" href="{{ route('admin.quotes.create', ['rfq' => $rfq->id]) }}">Creează ofertă</a>@endif @if ($manage)<a class="btn" href="{{ route('admin.rfqs.edit', $rfq) }}">Modifică</a>@endif</div>
</div>
<div class="cols"><div class="stack">
    <div class="panel panel--flush"><div class="panel__title">Repere solicitate</div><table class="t"><thead><tr><th>#</th><th>Serviciu</th><th>Cod</th><th>Denumire</th><th>Desen / rev.</th><th>Material</th><th class="num">Cantitate</th><th class="num">Anual</th><th class="num">Lot</th></tr></thead><tbody>
        @forelse ($rfq->items as $it)<tr><td class="mono">{{ $it->position }}</td><td class="small">{{ Vocab::SERVICES[$it->service] ?? $it->service }}</td><td class="mono">{{ $it->part_code ?: '—' }}</td><td>{{ $it->part_name }}</td><td class="mono small">{{ $it->drawing_no ?: '—' }} {{ $it->revision }}</td><td>{{ $it->material ?: '—' }}</td><td class="num">{{ qty($it->quantity) }}</td><td class="num">{{ qty($it->annual_quantity) }}</td><td class="num">{{ qty($it->lot_size) }}</td></tr>
        @empty <tr><td colspan="9"><div class="empty">Niciun reper introdus.</div></td></tr>@endforelse</tbody></table></div>
    @if ($rfq->description)<div class="panel"><h2>Descriere</h2><p class="wrap" style="white-space:pre-line">{{ $rfq->description }}</p></div>@endif
    @if (can('calc.use'))
    <div class="panel panel--flush"><div class="panel__title">Calcule tehnice <span class="btn-row"><a class="btn btn--sm btn--line" href="{{ route('admin.calc.index', 'piese') }}">Piese metalice</a><a class="btn btn--sm btn--line" href="{{ route('admin.calc.index', 'stante') }}">Ștanțe și matrițe</a></span></div><table class="t"><tbody>
        @forelse ($rfq->calculations as $c)<tr><td class="mono"><a class="link" href="{{ route('admin.calc.show', $c) }}">{{ $c->reference }}</a></td><td>{{ $c->title }}</td><td><span class="tag tag--{{ Vocab::tone($c->state) }}">{{ $c->status_text }}</span></td><td class="num">@if ($c->hours !== null){{ money($c->hours) }} ore @elseif ($c->total !== null && can('costs.view')){{ money($c->total) }} {{ $c->currency }}@endif</td></tr>
        @empty <tr><td><div class="empty">Niciun calcul legat de această cerere.</div></td></tr>@endforelse</tbody></table></div>
    @endif
    <div class="panel panel--flush"><div class="panel__title">Oferte</div><table class="t"><tbody>
        @forelse ($rfq->quotes as $q)<tr><td class="mono"><a class="link" href="{{ route('admin.quotes.show', $q) }}">{{ $q->label() }}</a></td><td><span class="tag tag--{{ Vocab::tone($q->status) }}">{{ $q->statusLabel() }}</span></td><td class="num">{{ money($q->total_net) }} {{ $q->currency }}</td></tr>
        @empty <tr><td><div class="empty">Nicio ofertă emisă pentru această cerere.</div></td></tr>@endforelse</tbody></table></div>
</div><div class="stack">
    <div class="panel"><h2>Detalii</h2><dl class="kv"><dt>Client</dt><dd><a class="link" href="{{ route('admin.companies.show', $rfq->company_id) }}">{{ $rfq->company?->name }}</a></dd><dt>Contact</dt><dd>{{ $rfq->contact?->fullName() ?? '—' }}</dd>
        <dt>Companie vizată</dt><dd>{{ $rfq->entity?->name ?? '—' }}</dd><dt>Servicii</dt><dd>{{ collect($rfq->services)->map(fn ($s) => Vocab::SERVICES[$s] ?? $s)->implode(', ') ?: '—' }}</dd>
        <dt>Primită</dt><dd>{{ $rfq->received_at?->format('d.m.Y') ?? '—' }}</dd><dt>Termen răspuns</dt><dd>{{ $rfq->due_at?->format('d.m.Y') ?? '—' }}</dd><dt>Responsabil</dt><dd>{{ $rfq->owner?->name ?? '—' }}</dd><dt>Inginer</dt><dd>{{ $rfq->engineer?->name ?? '—' }}</dd></dl>
        @if ($rfq->notes)<p class="small wrap" style="white-space:pre-line;margin-top:12px">{{ $rfq->notes }}</p>@endif</div>
    @if ($manage)<form class="panel" method="post" action="{{ route('admin.rfqs.status', $rfq) }}">@csrf<h2>Schimbă statusul</h2>
        <div class="field"><select class="select" name="status" aria-label="Status">@foreach (Vocab::RFQ_STATUSES as $k => $l)<option value="{{ $k }}" @selected($rfq->status === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="field"><label for="lost">Motiv (dacă este pierdută)</label><input class="input" id="lost" name="lost_reason" value="{{ $rfq->lost_reason }}"></div><button class="btn btn--sm" type="submit">Actualizează</button></form>@endif
</div></div>
@endsection
