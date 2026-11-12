@extends('admin.layout')
@section('title', $res['title'])
@section('content')
@php $canManage = can($res['manage']); $cols = array_filter($res['columns'], fn ($c) => ! isset($c['perm']) || can($c['perm'])); @endphp
<div class="page-head">
    <div><h1 class="page-title">{{ $res['title'] }}</h1>@isset($res['intro'])<p class="lead">{{ $res['intro'] }}</p>@endisset</div>
    @if ($canManage)<a class="btn btn--accent" href="{{ route('admin.res.create', $res['key']) }}">Adaugă {{ $res['singular'] }}</a>@endif
</div>
<form class="filters" method="get" data-auto>
    <input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="Caută…" aria-label="Caută">
    @foreach ($res['filters'] ?? [] as $col => $filter)
        <select class="select" name="{{ $col }}" aria-label="{{ $filter['label'] }}"><option value="">{{ $filter['label'] }}: toate</option>@foreach ($filter['options'] as $k => $l)<option value="{{ $k }}" @selected(request($col) === (string) $k)>{{ $l }}</option>@endforeach</select>
    @endforeach
    <button class="btn btn--line" type="submit">Filtrează</button>
</form>
<div class="table-wrap"><table class="t">
    <thead><tr>@foreach ($cols as $c)<th @class(['num' => isset($c['money']) || isset($c['number'])])>{{ $c['label'] }}</th>@endforeach<th></th></tr></thead>
    <tbody>
    @forelse ($rows as $row)
        <tr>
        @foreach ($cols as $key => $c)
            @php $v = data_get($row, $key); @endphp
            @if (isset($c['money']))<td class="num">@if ($v === null)<span class="pending">{{ $c['empty'] ?? '—' }}</span>@else{{ money($v) }} <span class="muted">{{ $row->{$c['money']} }}@isset($c['unit'])/{{ $row->{$c['unit']} }}@endisset</span>@endif</td>
            @elseif (isset($c['number']))<td class="num">{{ $v === null ? '—' : money($v, $c['number']) }}</td>
            @elseif (isset($c['bool']))<td>@if ($v)<span class="tag tag--ok">da</span>@else<span class="tag tag--mute">nu</span>@endif</td>
            @elseif (isset($c['date']))<td class="mono small">{{ $v?->format('d.m.Y') ?? '—' }}</td>
            @elseif (isset($c['vocab']))<td>{{ $c['vocab'][$v] ?? ($v ?: '—') }}</td>
            @elseif (isset($c['formula']))<td>@if ($v)<span class="tag tag--ok">validată din Excel</span>@else<span class="pending">Formulă în curs de configurare</span>@endif</td>
            @else<td @class(['mono' => ! empty($c['mono'])])>{{ $v ?: '—' }}</td>@endif
        @endforeach
        <td class="actions">@if ($canManage)<a class="link" href="{{ route('admin.res.edit', [$res['key'], $row->id]) }}">Modifică</a>@endif</td>
        </tr>
    @empty
        <tr><td colspan="{{ count($cols) + 1 }}"><div class="empty"><strong>Nicio înregistrare</strong>@if ($canManage)Adăugați primul element din butonul de sus.@endif</div></td></tr>
    @endforelse
    </tbody>
</table></div>
@include('admin.partials.pagination', ['paginator' => $rows])
@endsection
