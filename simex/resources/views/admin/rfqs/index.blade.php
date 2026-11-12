@extends('admin.layout')
@section('title', 'Cereri de ofertă')
@section('content')
@php use App\Support\Vocab; @endphp
<div class="page-head"><div><h1 class="page-title">Cereri de ofertă (RFQ)</h1></div>@if (can('rfq.manage'))<a class="btn btn--accent" href="{{ route('admin.rfqs.create') }}">Înregistrează cerere</a>@endif</div>
<div class="pipeline" style="margin-bottom:16px">@foreach (Vocab::RFQ_STATUSES as $k => $label)<a href="{{ route('admin.rfqs.index', ['status' => $k]) }}" @class(['is-active' => request('status') === $k])><b>{{ $counts[$k] ?? 0 }}</b><span>{{ $label }}</span></a>@endforeach</div>
<form class="filters" method="get"><input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="Număr, titlu, client…" aria-label="Caută"><input type="hidden" name="status" value="{{ request('status') }}"><button class="btn btn--line" type="submit">Caută</button>@if (request()->hasAny(['q', 'status']))<a class="link small" href="{{ route('admin.rfqs.index') }}">Resetează</a>@endif</form>
<div class="table-wrap"><table class="t"><thead><tr><th>Număr</th><th>Client</th><th>Titlu</th><th>Servicii</th><th>Status</th><th>Prioritate</th><th>Termen răspuns</th><th>Inginer</th></tr></thead><tbody>
@forelse ($rows as $r)
    <tr><td class="mono"><a class="link" href="{{ route('admin.rfqs.show', $r) }}">{{ $r->number }}</a></td><td>{{ $r->company?->name }}</td><td>{{ $r->title }}</td>
        <td class="small">{{ collect($r->services)->map(fn ($s) => Vocab::SERVICES[$s] ?? $s)->implode(', ') ?: '—' }}</td><td><span class="tag tag--{{ Vocab::tone($r->status) }}">{{ $r->statusLabel() }}</span></td>
        <td><span class="tag tag--{{ Vocab::tone($r->priority) }}">{{ Vocab::PRIORITIES[$r->priority] ?? $r->priority }}</span></td>
        <td class="mono small"><span @class(['status--err' => $r->isOverdue()])>{{ $r->due_at?->format('d.m.Y') ?? '—' }}</span></td><td>{{ $r->engineer?->name ?? '—' }}</td></tr>
@empty <tr><td colspan="8"><div class="empty"><strong>Nicio cerere de ofertă</strong></div></td></tr>
@endforelse</tbody></table></div>
@include('admin.partials.pagination', ['paginator' => $rows])
@endsection
