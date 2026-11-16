@extends('admin.layout')
@section('title', 'Integrare AI')
@section('content')
<div class="page-head"><div><h1 class="page-title">Integrare AI</h1>
    <p class="small muted">Instrumente de redactare cu Claude, rulate pe server. AI-ul scrie doar text: nu calculează și nu modifică prețuri, costuri sau cantități — cifrele vin numai din calculatoarele validate pe Excel.</p></div>
    <span class="tag {{ $configured ? 'tag--ok' : 'tag--warn' }}">{{ $configured ? 'Cheie API configurată' : 'Cheie API neconfigurată' }}</span></div>
@unless ($configured)
    <div class="alert alert--warn">Cheia Anthropic se introduce la Administrare → API Claude @if (can('api.keys'))(<a class="link" href="{{ route('admin.settings.edit', 'ai') }}">deschide</a>)@endif. Este păstrată criptat și nu ajunge niciodată în browser.</div>
@endunless
@if ($error)<div class="alert alert--err" role="alert">{{ $error }}</div>@endif
<div class="cols">
    <div class="stack">
        @foreach ($tools as $key => [$title, $desc])
        <form class="panel" method="post" action="{{ route('admin.ai.run') }}">@csrf<input type="hidden" name="tool" value="{{ $key }}">
            <h2>{{ $title }}</h2><p class="small muted" style="margin-top:-6px">{{ $desc }}</p>
            <div class="form-grid">
                @if ($key === 'email')
                    <div class="field span-8"><label for="q-{{ $key }}">Oferta</label><select class="select" id="q-{{ $key }}" name="quote_id" required><option value="">—</option>@foreach ($quotes as $q)<option value="{{ $q->id }}" @selected((int) old('quote_id') === $q->id)>{{ $q->label() }} · {{ $q->company?->name }} · {{ \Illuminate\Support\Str::limit($q->title, 40) }}</option>@endforeach</select></div>
                @endif
                <div class="field {{ $key === 'email' ? 'span-4' : 'span-4' }}"><label for="l-{{ $key }}">Limba rezultatului</label><select class="select" id="l-{{ $key }}" name="lang">@foreach ($langs as $c => $n)<option value="{{ $c }}" @selected(old('tool') === $key && old('lang') === $c)>{{ ucfirst($n) }}</option>@endforeach</select></div>
                <div class="field span-12"><label for="t-{{ $key }}">{{ $key === 'email' ? 'Indicații suplimentare (opțional)' : 'Text' }}</label><textarea class="textarea" id="t-{{ $key }}" name="text" rows="{{ $key === 'email' ? 2 : 6 }}" maxlength="20000" @if ($key !== 'email') required @endif>{{ old('tool') === $key ? old('text') : '' }}</textarea></div>
            </div>
            <button class="btn btn--accent" type="submit" @disabled(! $configured)>Generează</button>
        </form>
        @endforeach
    </div>
    <aside class="stack">
        @if ($result)
        <div class="panel panel--ok"><h2>Rezultat <button class="btn btn--sm btn--line" type="button" data-copy-from="#ai-out">Copiază</button></h2>
            <pre id="ai-out" style="white-space:pre-wrap;font:inherit;font-size:13px;margin:0">{{ $result['text'] }}</pre>
            <p class="hint" style="margin-top:10px">Verificați textul înainte de a-l folosi. {{ number_format($result['input'] + $result['output'], 0, ',', '.') }} tokeni.</p></div>
        @endif
        <div class="panel"><h2>Ce se trimite</h2>
            <ul class="small" style="margin:0;padding-left:18px;display:grid;gap:4px">
                <li>Doar textul introdus aici sau datele publice ale ofertei (client, număr, termene).</li>
                <li>Niciodată costuri interne, marje, tarife sau fișiere tehnice.</li>
                <li>Denumirile reperelor se trimit doar dacă setarea dedicată este activă.</li>
                <li>Jurnalul de audit păstrează doar instrumentul și numărul de tokeni, nu textul.</li>
            </ul></div>
        <div class="panel"><h2>Consum în luna curentă</h2>
            <p class="small" style="margin:0">{{ $usage['runs'] ?? 0 }} rulări · {{ number_format(($usage['input'] ?? 0) + ($usage['output'] ?? 0), 0, ',', '.') }} tokeni (intrare {{ number_format($usage['input'] ?? 0, 0, ',', '.') }}, ieșire {{ number_format($usage['output'] ?? 0, 0, ',', '.') }})</p></div>
    </aside>
</div>
@endsection
