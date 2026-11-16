@extends('admin.layout')
@section('title', $calc->exists ? $calc->reference : $def->title)
@push('head') @vite('resources/js/calc.js') @endpush
@section('content')
@php
    $first = array_key_first(array_filter($sheets, fn ($s) => $s['role'] === 'input')) ?? array_key_first($sheets);
    $groups = ['input' => 'Date de intrare', 'reference' => 'Rezultate', 'params' => 'Parametri și tarife', 'doc' => 'Documentație'];
    $locked = $calc->isLocked();
@endphp
<div class="page-head">
    <div><p class="eyebrow"><a href="{{ route('admin.calc.index', $def->entity === 'argento' ? 'piese' : 'stante') }}">{{ $def->process }}</a>@if ($calc->exists) · <span class="mono">{{ $calc->reference }}</span>@endif</p>
        <h1 class="page-title">{{ $def->title }}</h1><p class="small muted">Model: {{ $version?->original_name }} · {{ number_format($version?->formulas ?? 0, 0, ',', '.') }} formule validate @if ($calc->exists)· parametri v{{ $calc->results['parameters']['version'] ?? 0 }} · calculat la {{ $calc->calc_date->format('d.m.Y') }}@endif</p></div>
    @if ($locked)<span class="tag tag--info">Blocat – inclus într-o ofertă emisă</span>@endif
</div>
<div class="calc" data-calc>
    <div>
        <div class="tabs" data-sheets>
            @foreach ($groups as $role => $label)
                @foreach (array_filter($sheets, fn ($s) => $s['role'] === $role) as $name => $s)
                    <button type="button" data-sheet="{{ $name }}" @class(['is-active' => $name === $first]) title="{{ $label }}">{{ $s['label'] }}</button>
                @endforeach
            @endforeach
        </div>
        <div class="calc__bar">
            <div class="seg" role="group" aria-label="Mod de afișare"><button type="button" data-mode="form">Formular</button><button type="button" data-mode="grid">Foaia Excel</button></div>
            <span class="small muted">Rezultatele se recalculează pe server la fiecare modificare.</span>
        </div>
        <div class="sheet-wrap" data-grid><div class="empty">Se calculează…</div></div>
        <p class="small muted" style="margin-top:8px">În «Foaia Excel» celulele galbene sunt datele de intrare, cu aceleași adrese ca în fișier. Parametrii și tarifele se schimbă doar cu permisiunea dedicată și se salvează ca versiune nouă.</p>
    </div>
    <aside class="calc__side">
        <div data-results></div>
        @unless ($locked)
        <form class="panel" method="post" action="{{ $calc->exists ? route('admin.calc.update', $calc) : route('admin.calc.store', $def->key()) }}" data-calc-save>
            @csrf @if ($calc->exists) @method('PUT') @endif
            <h2>{{ $calc->exists ? 'Salvează modificările' : 'Salvează calculul' }}</h2>
            <div class="field"><label for="title">Titlu / reper <span class="req">*</span></label><input class="input" id="title" name="title" value="{{ old('title', $calc->title) }}" required maxlength="255"></div>
            <div class="field"><label for="company_id">Client</label><select class="select" id="company_id" name="company_id"><option value="">—</option>@foreach ($companies as $id => $name)<option value="{{ $id }}" @selected((int) old('company_id', $calc->company_id) === $id)>{{ $name }}</option>@endforeach</select></div>
            <div class="field"><label for="rfq_id">Cerere de ofertă</label><select class="select" id="rfq_id" name="rfq_id"><option value="">—</option>@foreach ($rfqs as $r)<option value="{{ $r->id }}" @selected((int) old('rfq_id', $calc->rfq_id) === $r->id)>{{ $r->number }} · {{ \Illuminate\Support\Str::limit($r->title, 40) }}</option>@endforeach</select></div>
            <button class="btn btn--accent" type="submit">Salvează</button>
            <p class="hint" style="margin-top:8px">Se păstrează o copie a rezultatelor, a parametrilor și a tarifului utilajului din acest moment.</p>
        </form>
        @endunless
        @if (can('rates.edit') && ! $calc->exists)
        <form class="panel" method="post" action="{{ route('admin.calc.params', $def->key()) }}" data-calc-params>@csrf
            <h2>Parametri și tarife</h2><p class="small muted">După ce modificați celule din foile de parametri, salvați-le ca versiune nouă, valabilă pentru toate calculele viitoare.</p>
            <div class="field"><label for="note">Motivul modificării</label><input class="input" id="note" name="note" maxlength="255"></div><button class="btn btn--sm btn--line" type="submit">Salvează parametrii</button></form>
        @endif
        @if ($calc->exists && can('quotes.manage'))
        <form class="panel panel--ok" method="post" action="{{ route('admin.quotes.from-calc', $calc) }}">@csrf
            <h2>Ofertă</h2>
            <p class="small">Creează o ofertă pentru {{ $calc->company?->name ?? 'clientul ales' }} cu rezultatul acestui calcul. O puteți modifica înainte de aprobare.</p>
            <button class="btn btn--accent" type="submit" @disabled($calc->state === 'block' || ! $calc->company_id)>Creează ofertă din acest calcul</button>
            @if (! $calc->company_id)<p class="hint">Alegeți întâi clientul și salvați calculul.</p>@endif
        </form>
        @endif
        @if ($calc->exists)
        <div class="panel"><div class="btn-row"><form method="post" action="{{ route('admin.calc.duplicate', $calc) }}">@csrf<button class="btn btn--sm btn--line" type="submit">Duplică cu parametrii actuali</button></form>
            @unless ($locked)<form method="post" action="{{ route('admin.calc.destroy', $calc) }}" data-confirm="Ștergeți calculul?">@csrf @method('DELETE')<button class="link-btn link-btn--danger small" type="submit">Șterge</button></form>@endunless</div></div>
        @endif
    </aside>
</div>
@php $config = ['evaluateUrl' => route('admin.calc.evaluate', $def->key()), 'sheet' => $first, 'inputs' => (object) ($calc->inputs ?? []), 'variant' => $calc->variant, 'readonly' => $locked, 'calculation' => $calc->id]; @endphp
<script type="application/json" id="calc-config">{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection
