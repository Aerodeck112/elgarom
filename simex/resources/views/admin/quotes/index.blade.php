@extends('admin.layout')
@section('title', 'Oferte comerciale')
@section('content')
@php use App\Support\Vocab; @endphp
<div class="page-head"><div><h1 class="page-title">Oferte comerciale</h1></div>@if (can('quotes.manage'))<a class="btn btn--accent" href="{{ route('admin.quotes.create') }}">Ofertă nouă</a>@endif</div>
<div class="pipeline" style="margin-bottom:16px">@foreach (Vocab::QUOTE_STATUSES as $k => $label)<a href="{{ route('admin.quotes.index', ['status' => $k]) }}" @class(['is-active' => request('status') === $k])><b>{{ $counts[$k] ?? 0 }}</b><span>{{ $label }}</span></a>@endforeach</div>
<form class="filters" method="get" data-auto><input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="Număr, titlu, client…" aria-label="Caută"><input type="hidden" name="status" value="{{ request('status') }}">
    <select class="select" name="entity" aria-label="Emitent"><option value="">Emitent: toți</option>@foreach ($entities as $id => $n)<option value="{{ $id }}" @selected((int) request('entity') === $id)>{{ $n }}</option>@endforeach</select>
    <button class="btn btn--line" type="submit">Caută</button>@if (request()->hasAny(['q', 'status', 'entity']))<a class="link small" href="{{ route('admin.quotes.index') }}">Resetează</a>@endif</form>
<div class="table-wrap"><table class="t"><thead><tr><th>Număr</th><th>Client</th><th>Emitent</th><th>Titlu</th><th>Status</th><th class="num">Total net</th>@if (can('margins.view'))<th class="num">Marjă</th>@endif<th>Emisă</th><th>Responsabil</th></tr></thead><tbody>
@forelse ($rows as $q)
    <tr><td class="mono"><a class="link" href="{{ route('admin.quotes.show', $q) }}">{{ $q->label() }}</a></td><td>{{ $q->company?->name }}</td><td>{{ $q->entity?->name }}</td><td>{{ $q->title ?: '—' }}</td>
        <td><span class="tag tag--{{ Vocab::tone($q->status) }}">{{ $q->statusLabel() }}</span></td><td class="num">{{ money($q->total_net) }} {{ $q->currency }}</td>
        @if (can('margins.view'))<td class="num">{{ $q->marginPercent() === null ? '—' : money($q->marginPercent(), 1).' %' }}</td>@endif
        <td class="mono small">{{ $q->issued_at?->format('d.m.Y') ?? '—' }}</td><td>{{ $q->owner?->name ?? '—' }}</td></tr>
@empty <tr><td colspan="9"><div class="empty"><strong>Nicio ofertă</strong></div></td></tr>
@endforelse</tbody></table></div>
@include('admin.partials.pagination', ['paginator' => $rows])
@endsection
