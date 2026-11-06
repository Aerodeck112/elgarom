@extends('admin.layout')
@section('title', 'Cereri de ofertă')
@section('content')
<div class="page-title"><div><h1>Cereri de ofertă și mesaje</h1></div>
@if (auth()->user()->can_('quotes.export'))<a class="btn btn--line" href="{{ route('admin.quotes.export', request()->query()) }}">Export CSV (filtrele curente)</a>@endif</div>
<form class="filters" method="get">
    <div class="field"><label for="q">Caută</label><input class="input" id="q" name="q" value="{{ request('q') }}" placeholder="număr, nume, firmă, email"></div>
    <div class="field"><label for="st">Stare</label><select class="select" id="st" name="status"><option value="">Toate</option>@foreach (\App\Models\QuoteRequest::STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="field"><label for="kd">Tip</label><select class="select" id="kd" name="kind"><option value="">Toate</option><option value="oferta" @selected(request('kind') === 'oferta')>Cerere de ofertă</option><option value="contact" @selected(request('kind') === 'contact')>Mesaj</option></select></div>
    <div class="field"><label for="as">Responsabil</label><select class="select" id="as" name="assignee"><option value="">Oricine</option><option value="none" @selected(request('assignee') === 'none')>Nealocat</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected(request('assignee') == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
    <div class="field"><label for="from">De la</label><input class="input" type="date" id="from" name="from" value="{{ request('from') }}"></div>
    <div class="field"><label for="to">Până la</label><input class="input" type="date" id="to" name="to" value="{{ request('to') }}"></div>
    <button class="btn btn--line" type="submit">Filtrează</button>
</form>
<div class="table-wrap"><table class="t">
<thead><tr><th>Număr</th><th>Data</th><th>Client</th><th>Piese</th><th>Fișiere</th><th>Stare</th><th>Responsabil</th></tr></thead>
<tbody>
@forelse ($items as $q)
<tr>
    <td><a href="{{ route('admin.quotes.show', $q) }}"><strong>{{ $q->reference }}</strong></a><br><span class="small muted">{{ $q->kind === 'contact' ? 'Mesaj' : 'Ofertă' }}@if ($q->source === 'asistent_ai'), prin asistent AI @endif</span></td>
    <td class="small">{{ $q->created_at->format('d.m.Y H:i') }}</td>
    <td>{{ $q->company ?: '—' }}<br><span class="small muted">{{ $q->name }}</span></td>
    <td class="small">{{ \Illuminate\Support\Str::limit(\App\Models\QuoteRequest::PART_TYPES[$q->part_type] ?? $q->description, 60) }}</td>
    <td class="num">{{ $q->files_count }}</td>
    <td><span class="tag {{ $q->status === 'nou' ? 'tag--info' : ($q->status === 'castigata' ? 'tag--ok' : '') }}">{{ $q->statusLabel() }}</span></td>
    <td class="small">{{ $q->assignee?->name ?? '—' }}</td>
</tr>
@empty
<tr><td colspan="7" class="muted">Nicio cerere pentru filtrele alese.</td></tr>
@endforelse
</tbody></table></div>
{{ $items->links('admin.partials.pagination') }}
@endsection
