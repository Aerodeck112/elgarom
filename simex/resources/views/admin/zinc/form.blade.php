@extends('admin.layout')
@section('title', $job->exists ? $job->reference : 'Fișă zincare')
@section('content')
@php $i = old('inputs', $job->inputs ?? []); $surface = (float) ($i['surface_dm2'] ?? 0) * (float) ($i['lot_pieces'] ?? 0); $mass = (float) ($i['piece_weight_kg'] ?? 0) * (float) ($i['lot_pieces'] ?? 0); @endphp
<div class="page-head"><div><p class="eyebrow"><a href="{{ route('admin.zinc.index') }}">Calculator zincare</a>@if ($job->exists) · <span class="mono">{{ $job->reference }}</span>@endif</p><h1 class="page-title">Fișă tehnică de zincare</h1></div><span class="pending">Formulă în curs de configurare</span></div>
@foreach ($job->exists ? $job->warnings() : [] as $w)<div class="alert alert--warn">{{ $w }}</div>@endforeach
<div class="cols">
<form class="panel form-grid" method="post" action="{{ $job->exists ? route('admin.zinc.update', $job) : route('admin.zinc.store') }}">@csrf @if ($job->exists) @method('PUT') @endif
    <div class="field col-8"><label for="title">Titlu / reper <span class="req">*</span></label><input class="input" id="title" name="title" value="{{ old('title', $job->title) }}" required></div>
    <div class="field col-4"><label for="company_id">Client</label><select class="select" id="company_id" name="company_id"><option value="">—</option>@foreach ($companies as $id => $name)<option value="{{ $id }}" @selected((int) old('company_id', $job->company_id) === $id)>{{ $name }}</option>@endforeach</select></div>
    @foreach (\App\Http\Controllers\Admin\ZincController::FIELDS as $key => [$label, $type])
        <div class="field col-4"><label for="z-{{ $key }}">{{ $label }}</label>
        @if ($type === 'select')<select class="select" id="z-{{ $key }}" name="inputs[{{ $key }}]"><option value="dispozitiv" @selected(($i[$key] ?? '') === 'dispozitiv')>Dispozitiv (agățat)</option><option value="tambur" @selected(($i[$key] ?? '') === 'tambur')>Tambur</option></select>
        @else<input class="input {{ $type === 'num' ? 'input--num' : '' }}" id="z-{{ $key }}" name="inputs[{{ $key }}]" value="{{ $i[$key] ?? '' }}" @if ($type === 'num') inputmode="decimal" @endif>@endif
        @error('inputs.'.$key)<p class="error-text">{{ $message }}</p>@enderror</div>
    @endforeach
    <div class="field col-12"><label for="notes">Observații</label><textarea class="textarea" id="notes" name="notes">{{ old('notes', $job->notes) }}</textarea></div>
    <div class="col-12"><button class="btn" type="submit">Salvează fișa</button></div>
</form>
<div class="stack">
    <div class="panel"><h2>Mărimi derivate</h2><dl class="kv"><dt>Suprafață totală / lot</dt><dd class="mono">{{ $surface > 0 ? money($surface).' dm²' : '—' }}</dd><dt>Masă totală / lot</dt><dd class="mono">{{ $mass > 0 ? money($mass).' kg' : '—' }}</dd></dl><p class="hint">Produse simple (mărime pe piesă × piese pe lot), afișate pentru verificare.</p></div>
    <div class="panel panel--warn"><h2>Cost</h2><p class="pending">Formulă în curs de configurare</p><p class="small">Costurile pe piesă, suprafață, masă sau lot (energie, chimicale, manoperă, pregătire, pierderi) vor fi calculate după furnizarea și validarea datelor reale de producție.</p></div>
</div></div>
@endsection
