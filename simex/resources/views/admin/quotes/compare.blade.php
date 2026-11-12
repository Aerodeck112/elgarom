@extends('admin.layout')
@section('title', 'Comparare revizii')
@section('content')
<div class="page-head"><div><p class="eyebrow"><a href="{{ route('admin.quotes.show', $new) }}">{{ $new->number }}</a></p><h1 class="page-title">Revizia {{ $old->revision }} → revizia {{ $new->revision }}</h1></div></div>
<div class="grid-2">
    <div class="panel panel--flush"><div class="panel__title">Condiții și totaluri</div><table class="t"><thead><tr><th>Câmp</th><th>Rev. {{ $old->revision }}</th><th>Rev. {{ $new->revision }}</th></tr></thead><tbody>
        @forelse ($diff['header'] as $h)<tr><td>{{ $h['label'] }}</td><td class="diff-del">{{ $h['old'] ?? '—' }}</td><td class="diff-add">{{ $h['new'] ?? '—' }}</td></tr>@empty<tr><td colspan="3"><div class="empty">Fără diferențe.</div></td></tr>@endforelse</tbody></table></div>
    <div class="panel panel--flush"><div class="panel__title">Poziții</div><table class="t"><thead><tr><th>Poziție</th><th>Schimbare</th><th class="num">Cantitate</th><th class="num">Preț unitar</th></tr></thead><tbody>
        @forelse ($diff['lines'] as $l) @php $i = $l['new'] ?? $l['old']; @endphp
            <tr @class(['diff-add' => $l['change'] === 'adaugata', 'diff-del' => $l['change'] === 'eliminata'])><td>{{ $i->code }} {{ $i->name }}</td><td>{{ $l['change'] }}</td>
            <td class="num">@if ($l['change'] === 'modificata'){{ qty($l['old']->quantity) }} → @endif{{ qty($i->quantity) }}</td><td class="num">@if ($l['change'] === 'modificata'){{ money($l['old']->unit_price, 4) }} → @endif{{ money($i->unit_price, 4) }}</td></tr>
        @empty<tr><td colspan="4"><div class="empty">Fără diferențe.</div></td></tr>@endforelse</tbody></table></div>
</div>
@endsection
