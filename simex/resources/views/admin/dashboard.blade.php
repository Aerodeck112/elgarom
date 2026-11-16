@extends('admin.layout')
@section('title', 'Panou principal')
@push('head') @vite('resources/js/charts.js') @endpush
@section('content')
@php use App\Support\Vocab; @endphp
<div class="page-head"><div><h1 class="page-title">Panou principal</h1><p class="small muted">Ultimele {{ $days }} de zile · valori în {{ $currency }}, fără TVA</p></div>
    <div class="tabs" style="margin:0;border:0">@foreach ([30 => '30 zile', 90 => '90 zile', 365 => '12 luni'] as $d => $l)<a href="{{ route('admin.dashboard', ['zile' => $d]) }}" @class(['is-active' => $days === $d])>{{ $l }}</a>@endforeach</div></div>
<div class="grid-4" style="margin-bottom:14px">
    <div class="stat"><div class="stat__label">Cereri de ofertă</div><div class="stat__value">{{ $kpi['rfq_total'] }}</div><div class="stat__hint">{{ $kpi['rfq_open'] }} deschise acum</div></div>
    <div class="stat"><div class="stat__label">Oferte în lucru</div><div class="stat__value">{{ $kpi['quotes_draft'] }}</div><div class="stat__hint">ciorne și în aprobare</div></div>
    <div class="stat"><div class="stat__label">Oferte trimise</div><div class="stat__value">{{ $kpi['quotes_sent'] }}</div><div class="stat__hint">{{ $kpi['accepted'] }} acceptate · {{ $kpi['lost'] }} pierdute</div></div>
    <div class="stat"><div class="stat__label">Rată de conversie</div><div class="stat__value">{{ $kpi['conversion'] === null ? '—' : money($kpi['conversion'], 1).' %' }}</div><div class="stat__hint">acceptate din ofertele cu decizie</div></div>
</div>
<div class="grid-4" style="margin-bottom:20px">
    <div class="stat stat--accent"><div class="stat__label">Valoare ofertată</div><div class="stat__value">{{ money($kpi['offered'], 0) }}</div><div class="stat__hint">oferte emise în perioadă</div></div>
    <div class="stat"><div class="stat__label">Valoare acceptată</div><div class="stat__value">{{ money($kpi['accepted_value'], 0) }}</div><div class="stat__hint">oferte acceptate de clienți</div></div>
    <div class="stat"><div class="stat__label">Venituri confirmate</div><div class="stat__value">{{ money($kpi['confirmed'], 0) }}</div><div class="stat__hint">introduse manual, la comandă fermă</div></div>
    <div class="stat"><div class="stat__label">Vânzări potențiale</div><div class="stat__value">{{ money($kpi['potential'], 0) }}</div><div class="stat__hint">oferte emise, fără decizie încă</div></div>
    @if (array_key_exists('margin', $kpi))<div class="stat"><div class="stat__label">Marjă estimată</div><div class="stat__value">{{ $kpi['margin'] === null ? '—' : money($kpi['margin'], 1).' %' }}</div><div class="stat__hint">doar ofertele cu cost complet</div></div>@endif
</div>
<div class="grid-2x" style="margin-bottom:20px">
    @foreach (['entity' => 'Valoare ofertată pe companii', 'type' => 'Oferte pe tipuri de servicii', 'status' => 'Oferte pe status', 'country' => 'Companii pe țări'] as $key => $title)
        <div class="panel"><h2>{{ $title }}</h2>@if (array_sum($charts[$key]['datasets'][0]['data']) > 0)<div class="chart"><canvas data-chart="chart-{{ $key }}" role="img" aria-label="{{ $title }}"></canvas></div><script type="application/json" id="chart-{{ $key }}">@json($charts[$key])</script>@else<div class="empty">Nu există încă date pentru acest grafic.</div>@endif</div>
    @endforeach
</div>
<div class="grid-2x">
    <div class="panel panel--flush"><div class="panel__title">Follow-up în următoarele 7 zile @if ($overdue)<span class="tag tag--err">{{ $overdue }} întârziate</span>@endif</div><table class="t"><tbody>
        @forelse ($followUps as $c)<tr><td><a class="link" href="{{ route('admin.companies.show', $c) }}">{{ $c->name }}</a></td><td><span class="tag tag--{{ Vocab::tone($c->stage) }}">{{ $c->stageLabel() }}</span></td><td class="mono small"><span @class(['status--err' => $c->next_follow_up_at->isPast()])>{{ $c->next_follow_up_at->format('d.m.Y') }}</span></td></tr>
        @empty<tr><td><div class="empty">Niciun follow-up programat.</div></td></tr>@endforelse</tbody></table></div>
    <div class="panel panel--flush"><div class="panel__title">Sarcinile mele</div><table class="t"><tbody>
        @forelse ($tasks as $t)<tr><td>{{ $t->title }}</td><td>@if ($t->company)<a class="link" href="{{ route('admin.companies.show', $t->company) }}">{{ $t->company->name }}</a>@endif</td><td class="mono small">{{ $t->due_at?->format('d.m.Y') ?? '—' }}</td></tr>
        @empty<tr><td><div class="empty">Nicio sarcină deschisă.</div></td></tr>@endforelse</tbody></table></div>
    @if ($rfqs->isNotEmpty())<div class="panel panel--flush"><div class="panel__title">Cereri deschise, după termen</div><table class="t"><tbody>
        @foreach ($rfqs as $r)<tr><td class="mono"><a class="link" href="{{ route('admin.rfqs.show', $r) }}">{{ $r->number }}</a></td><td>{{ $r->company?->name }}</td><td><span class="tag tag--{{ Vocab::tone($r->status) }}">{{ $r->statusLabel() }}</span></td><td class="mono small"><span @class(['status--err' => $r->isOverdue()])>{{ $r->due_at?->format('d.m.Y') ?? '—' }}</span></td></tr>@endforeach</tbody></table></div>@endif
    <div class="panel panel--flush"><div class="panel__title">Top clienți după valoarea ofertată</div><table class="t"><tbody>
        @forelse ($topClients as $t)<tr><td>{{ $t->company?->name }}</td><td class="num">{{ $t->n }} oferte</td><td class="num">{{ money($t->v, 0) }}</td></tr>@empty<tr><td><div class="empty">Nicio ofertă emisă în perioadă.</div></td></tr>@endforelse</tbody></table></div>
    <div class="panel panel--flush"><div class="panel__title">Activitatea ofertanților</div><table class="t"><tbody>
        @forelse ($byOwner as $o)<tr><td>{{ $o->owner?->name ?? '—' }}</td><td class="num">{{ $o->n }} oferte</td><td class="num">{{ money($o->v, 0) }}</td></tr>@empty<tr><td><div class="empty">Nicio ofertă în perioadă.</div></td></tr>@endforelse</tbody></table></div>
</div>
@endsection
