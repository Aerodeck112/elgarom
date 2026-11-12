@extends('admin.layout')
@section('title', 'Clienți și contacte')
@section('content')
@php use App\Support\Vocab; @endphp
<div class="page-head"><div><h1 class="page-title">Clienți și contacte</h1><p class="lead">Lead → Calificare → Cerere de ofertă → Calcul tehnic → Aprobare internă → Ofertă trimisă → Negociere → Acceptată / Respinsă.</p></div>
    @if (can('crm.manage'))<a class="btn btn--accent" href="{{ route('admin.companies.create') }}">Adaugă companie</a>@endif</div>
<div class="pipeline" style="margin-bottom:16px">
    @foreach (Vocab::COMPANY_STAGES as $k => $label)
        <a href="{{ route('admin.companies.index', ['stage' => $k]) }}" @class(['is-active' => request('stage') === $k])><b>{{ $stages[$k] ?? 0 }}</b><span>{{ $label }}</span></a>
    @endforeach
</div>
<form class="filters" method="get" data-auto>
    <input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="Denumire, localitate, cod fiscal, industrie…" aria-label="Caută">
    <select class="select" name="stage" aria-label="Stadiu"><option value="">Stadiu: toate</option>@foreach (Vocab::COMPANY_STAGES as $k => $l)<option value="{{ $k }}" @selected(request('stage') === $k)>{{ $l }}</option>@endforeach</select>
    <select class="select" name="country" aria-label="Țară"><option value="">Țară: toate</option>@foreach (Vocab::countries() as $k => $l)<option value="{{ $k }}" @selected(request('country') === $k)>{{ $l }}</option>@endforeach</select>
    <button class="btn btn--line" type="submit">Filtrează</button>
    @if (request()->hasAny(['q', 'stage', 'country']))<a class="link small" href="{{ route('admin.companies.index') }}">Resetează</a>@endif
</form>
<div class="table-wrap"><table class="t">
    <thead><tr><th>Companie</th><th>Țară / localitate</th><th>Industrie</th><th>Stadiu</th><th>Responsabil</th><th>Următorul follow-up</th><th class="num">Contacte</th></tr></thead>
    <tbody>
    @forelse ($rows as $c)
        <tr>
            <td><a class="link" href="{{ route('admin.companies.show', $c) }}">{{ $c->name }}</a>@if ($c->do_not_contact) <span class="tag tag--err">nu contacta</span>@endif</td>
            <td>{{ $c->countryName() }}@if ($c->city), {{ $c->city }}@endif</td><td>{{ $c->industry ?: '—' }}</td>
            <td><span class="tag tag--{{ Vocab::tone($c->stage) }}">{{ $c->stageLabel() }}</span></td><td>{{ $c->owner?->name ?? '—' }}</td>
            <td class="mono small">@if ($c->next_follow_up_at)<span @class(['status--err' => $c->next_follow_up_at->isPast()])>{{ $c->next_follow_up_at->format('d.m.Y') }}</span>@else — @endif</td>
            <td class="num">{{ $c->contacts_count }}</td>
        </tr>
    @empty
        <tr><td colspan="7"><div class="empty"><strong>Nicio companie</strong>Adăugați primul client sau lead.</div></td></tr>
    @endforelse
    </tbody>
</table></div>
@include('admin.partials.pagination', ['paginator' => $rows])
@endsection
