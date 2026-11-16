@extends('admin.layout')
@section('title', 'Dashboard')
@push('head') @vite('resources/js/charts.js') @endpush
@section('content')
@php use App\Support\Vocab; @endphp
<div class="page-head"><div><h1 class="page-title">Dashboard</h1><p class="small muted">Bun venit, {{ auth()->user()->name }} · ultimele {{ $days }} de zile · valori în {{ $currency }}, fără TVA</p></div>
    <div class="tabs" style="margin:0;border:0">@foreach ([30 => '30 zile', 90 => '90 zile', 365 => '12 luni'] as $d => $l)<a href="{{ route('admin.dashboard', ['zile' => $d]) }}" @class(['is-active' => $days === $d])>{{ $l }}</a>@endforeach</div></div>

@foreach ($news as $n)
<div class="panel news" style="margin-bottom:14px">
    <div class="news__head"><span class="tag tag--accent">Noutăți v{{ $n['version'] }}</span><span class="small muted">{{ $n['date'] }}</span>
        @if ($loop->first)<form method="post" action="{{ route('admin.news.seen') }}" style="margin-left:auto">@csrf<button class="btn btn--sm btn--line" type="submit">Am văzut</button></form>@endif</div>
    <ul class="news__list">@foreach ($n['items'] as $item)<li>{{ $item }}</li>@endforeach</ul>
</div>
@endforeach

<div class="dash-top">
    <div class="panel">
        <h2>Acțiuni rapide</h2>
        <div class="quick">
            @if (can('calc.use'))<a href="{{ route('admin.calc.create', 'stante-matrite-fepo') }}"><b>Ofertă ștanță</b><span>calcul cost și preț ștanță / matriță</span></a>
            <a href="{{ route('admin.calc.index', 'piese') }}"><b>Ofertă piesă</b><span>ore utilaj, laser, abkant, finisaje</span></a>@endif
            @if (can('quotes.manage'))<a href="{{ route('admin.quotes.create') }}"><b>Ofertă nouă</b><span>ofertă comercială liberă</span></a>@endif
            @if (can('rfq.manage'))<a href="{{ route('admin.rfqs.create') }}"><b>Cerere de ofertă</b><span>înregistrează un RFQ primit</span></a>@endif
            @if (can('crm.manage'))<a href="{{ route('admin.companies.create') }}"><b>Client nou</b><span>companie și persoane de contact</span></a>@endif
            <a href="{{ route('admin.tutorial') }}"><b>Tutorial</b><span>pașii unei oferte, pe scurt</span></a>
        </div>
    </div>
    <div class="panel panel--flush">
        <div class="panel__title">De verificat</div>
        <table class="t"><tbody>
            @foreach ($review['approve'] ?? [] as $q)<tr><td><span class="tag tag--warn">De aprobat</span></td><td class="mono"><a class="link" href="{{ route('admin.quotes.show', $q) }}">{{ $q->label() }}</a></td><td>{{ $q->company?->name }}</td></tr>@endforeach
            @foreach ($review['expiring'] as $q)<tr><td><span class="tag tag--err">Expiră {{ $q->valid_until->format('d.m') }}</span></td><td class="mono"><a class="link" href="{{ route('admin.quotes.show', $q) }}">{{ $q->label() }}</a></td><td>{{ $q->company?->name }}</td></tr>@endforeach
            @foreach ($review['calcs'] ?? [] as $c)<tr><td><span class="tag {{ $c->state === 'block' ? 'tag--err' : 'tag--warn' }}">{{ $c->state === 'block' ? 'Calcul incomplet' : 'Calcul cu avertizări' }}</span></td><td class="mono"><a class="link" href="{{ route('admin.calc.show', $c) }}#registru">{{ $c->reference }}</a></td><td>{{ $c->title }}@if ($c->company) · {{ $c->company->name }}@endif</td></tr>@endforeach
            @if (collect($review)->every(fn ($list) => $list->isEmpty()))<tr><td><div class="empty"><strong>Nimic de verificat</strong>Toate calculele și ofertele sunt în regulă.</div></td></tr>@endif
        </tbody></table>
    </div>
</div>

<div class="flow" style="margin-bottom:16px" aria-label="Fluxul ofertelor">
    @foreach (['ciorna', 'aprobare', 'aprobata', 'trimisa', 'negociere', 'acceptata'] as $st)
        <a href="{{ route('admin.quotes.index', ['status' => $st]) }}"><b>{{ $flow[$st] ?? 0 }}</b><span>{{ Vocab::QUOTE_STATUSES[$st] }}</span></a>
    @endforeach
</div>

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
    <div class="panel"><h2>Valoarea ofertelor pe luni</h2>@if (array_sum($charts['months']['datasets'][0]['data']) > 0)<div class="chart"><canvas data-chart="chart-months" role="img" aria-label="Valoarea ofertelor pe luni"></canvas></div><script type="application/json" id="chart-months">@json($charts['months'])</script>@else<div class="empty">Nicio ofertă emisă în ultimele 6 luni.</div>@endif</div>
    @if (can('costs.view'))<div class="panel"><h2>Structura costului @if ($structure)<a class="link small" href="{{ route('admin.calc.show', $structure['calc']) }}">{{ $structure['calc']->reference }}</a>@endif</h2>@if ($structure)<div class="chart"><canvas data-chart="chart-structure" role="img" aria-label="Structura costului ultimei ștanțe calculate"></canvas></div><script type="application/json" id="chart-structure">@json($structure['chart'])</script>@else<div class="empty">Salvați un calcul de ștanță pentru a vedea structura costului.</div>@endif</div>@endif
    @foreach (['entity' => 'Valoare ofertată pe companii', 'type' => 'Oferte pe tipuri de servicii', 'status' => 'Oferte pe status', 'country' => 'Companii pe țări'] as $key => $title)
        <div class="panel"><h2>{{ $title }}</h2>@if (array_sum($charts[$key]['datasets'][0]['data']) > 0)<div class="chart"><canvas data-chart="chart-{{ $key }}" role="img" aria-label="{{ $title }}"></canvas></div><script type="application/json" id="chart-{{ $key }}">@json($charts[$key])</script>@else<div class="empty">Nu există încă date pentru acest grafic.</div>@endif</div>
    @endforeach
</div>
@if ($latest->isNotEmpty())
<div class="panel panel--flush" style="margin-bottom:20px"><div class="panel__title">Ultimele oferte <a class="link small" href="{{ route('admin.quotes.index') }}">Toate ofertele →</a></div>
    <div class="table-wrap" style="border:0"><table class="t"><thead><tr><th>Număr</th><th>Client</th><th>Titlu</th><th>Status</th><th class="num">Total net</th><th>Data</th></tr></thead><tbody>
    @foreach ($latest as $q)<tr><td class="mono"><a class="link" href="{{ route('admin.quotes.show', $q) }}">{{ $q->label() }}</a></td><td>{{ $q->company?->name }}</td><td>{{ $q->title ?: '—' }}</td><td><span class="tag tag--{{ Vocab::tone($q->status) }}">{{ $q->statusLabel() }}</span></td><td class="num">{{ money($q->total_net) }} {{ $q->currency }}</td><td class="mono small">{{ $q->created_at->format('d.m.Y') }}</td></tr>@endforeach
    </tbody></table></div></div>
@endif
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
