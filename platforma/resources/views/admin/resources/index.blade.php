@extends('admin.layout')
@section('title', $r['label'])
@section('content')
<div class="page-title">
    <div><h1>{{ $r['label'] }}</h1>@if (! empty($r['intro']))<p>{{ $r['intro'] }}</p>@endif</div>
    <a class="btn" href="{{ route('admin.res.create', $r['key']) }}">Adaugă {{ $r['singular'] }}</a>
</div>
<form class="filters" method="get">
    <div class="field"><label for="q">Caută</label><input class="input" id="q" name="q" value="{{ request('q') }}"></div>
    @foreach ($r['filter'] ?? [] as $col => $opts)
        <div class="field"><label for="flt-{{ $col }}">Filtru</label><select class="select" id="flt-{{ $col }}" name="{{ $col }}"><option value="">Toate</option>@foreach ($opts as $k => $l)<option value="{{ $k }}" @selected(request($col) === $k)>{{ $l }}</option>@endforeach</select></div>
    @endforeach
    <button class="btn btn--line" type="submit">Aplică</button>
</form>
<div class="table-wrap">
<table class="t">
    <thead><tr>@foreach ($r['columns'] as $c => $l)<th scope="col">{{ $l }}</th>@endforeach<th scope="col"><span class="sr-only">Acțiuni</span></th></tr></thead>
    <tbody>
    @forelse ($items as $item)
        <tr>
            @foreach ($r['columns'] as $c => $l)
                @php $v = $item->{$c}; @endphp
                <td>
                    @if ($loop->first)<a href="{{ route('admin.res.edit', [$r['key'], $item->id]) }}"><strong>{{ \Illuminate\Support\Str::limit((string) $v, 80) ?: '(fără titlu)' }}</strong></a>
                    @elseif (is_bool($v))<span class="tag {{ $v ? 'tag--ok' : '' }}">{{ $v ? 'Da' : 'Nu' }}</span>
                    @elseif ($v instanceof \Carbon\CarbonInterface){{ $v->format('d.m.Y') }}
                    @elseif ($c === 'value' && ! $item->is_confirmed && $v === null)<span class="tag tag--warn">De completat</span>
                    @else{{ \Illuminate\Support\Str::limit((string) $v, 70) }}@endif
                </td>
            @endforeach
            <td style="white-space:nowrap">
                @if ($r['order'] === 'sort')
                    <form method="post" action="{{ route('admin.res.move', [$r['key'], $item->id]) }}">@csrf<input type="hidden" name="dir" value="up"><button class="link-btn small" type="submit" aria-label="Mută mai sus">Sus</button></form>
                    <form method="post" action="{{ route('admin.res.move', [$r['key'], $item->id]) }}">@csrf<input type="hidden" name="dir" value="down"><button class="link-btn small" type="submit" aria-label="Mută mai jos">Jos</button></form>
                @endif
                @if (! empty($r['public']) && ($item->is_published ?? false))<a class="small" href="{{ ($r['public'])($item) }}" target="_blank" rel="noopener">Vezi</a>@endif
            </td>
        </tr>
    @empty
        <tr><td colspan="{{ count($r['columns']) + 1 }}" class="muted">Nu există încă elemente. Folosiți „Adaugă {{ $r['singular'] }}”.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $items->links('admin.partials.pagination') }}
@endsection
