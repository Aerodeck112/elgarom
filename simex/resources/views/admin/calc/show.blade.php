@extends('admin.layout')
@section('title', $calc->exists ? $calc->reference : $def->title)
@push('head') @vite('resources/js/calc.js') @endpush
@section('content')
@php
    $order = ['input' => 'Date de intrare', 'reference' => 'Rezultate', 'params' => 'Parametri și tarife', 'doc' => 'Documentație'];
    $sections = [];
    foreach ($order as $role => $roleLabel) {
        foreach (array_filter($sheets, fn ($s) => $s['role'] === $role) as $name => $s) {
            $sections[] = ['name' => $name, 'label' => $s['label'], 'role' => $role, 'roleLabel' => $roleLabel];
        }
    }
    $first = collect($sections)->firstWhere('role', 'input')['name'] ?? ($sections[0]['name'] ?? null);
    $locked = $calc->isLocked();
    $n = 0;
@endphp
<div class="page-head">
    <div><p class="eyebrow"><a href="{{ route('admin.calc.index', $def->entity === 'argento' ? 'piese' : 'stante') }}">{{ $def->process }}</a>@if ($calc->exists) · <span class="mono">{{ $calc->reference }}</span>@endif</p>
        <h1 class="page-title">{{ $calc->exists && $calc->title ? $calc->title : $def->title }}</h1>
        <p class="small muted">Model: {{ $version?->original_name }} · {{ number_format($version?->formulas ?? 0, 0, ',', '.') }} formule validate @if ($calc->exists)· parametri v{{ $calc->results['parameters']['version'] ?? 0 }} · calculat la {{ $calc->calc_date->format('d.m.Y') }}@endif</p></div>
    <div class="btn-row">
        @if ($locked)<span class="tag tag--info">Blocat – inclus într-o ofertă emisă</span>@endif
        <details class="drop">
            <summary class="btn btn--line btn--sm">Drafturi salvate <span class="badge">{{ $drafts->count() }}</span></summary>
            <div class="drop__menu drop__menu--wide">
                @forelse ($drafts as $d)
                    <a href="{{ route('admin.calc.show', $d) }}"><span><strong class="mono">{{ $d->reference }}</strong> {{ \Illuminate\Support\Str::limit($d->title, 38) }}<br><span class="muted small">{{ $d->company?->name ?? 'fără client' }} · {{ $d->updated_at->format('d.m.Y H:i') }}</span></span>
                        <span class="num small">@if ($d->total !== null && can('costs.view')){{ \App\Calc\Display::number((float) $d->total, 2) }} {{ $d->currency }}@elseif ($d->hours !== null){{ \App\Calc\Display::number((float) $d->hours, 2) }} ore @endif</span></a>
                @empty
                    <p class="small muted" style="padding:10px 12px;margin:0">Niciun calcul salvat pentru acest model.</p>
                @endforelse
            </div>
        </details>
        <a class="btn btn--line btn--sm" href="{{ route('admin.calc.create', $def->key()) }}" data-confirm="Începeți un calcul nou cu valorile din modelul Excel? Modificările nesalvate se pierd.">Resetează la exemplul inițial</a>
        <div class="seg" role="group" aria-label="Mod de afișare"><button type="button" data-mode="form">Formular</button><button type="button" data-mode="grid">Foaia Excel</button></div>
    </div>
</div>

<div class="calc2" data-calc>
    <div class="rfbar" data-tiles-bar>
        <span class="rfbar__label">Rezultat final</span>
        <div class="rfbar__tiles" data-tiles><span class="muted small">Se calculează…</span></div>
        <span class="tag" data-state>—</span>
    </div>
    <a class="warnbar" href="#registru" data-warnbar hidden><strong data-warncount>0</strong> avertizări active — <span>Vezi registrul</span></a>

    <div class="acc">
        @foreach ($sections as $s)
            @php $n++; @endphp
            <details class="sec sec--{{ $s['role'] }}" data-sheet="{{ $s['name'] }}" data-role="{{ $s['role'] }}" id="s-{{ \Illuminate\Support\Str::slug($s['name']) }}" @if ($s['name'] === $first) open @endif>
                <summary><span class="sec__n">{{ $n }}</span><span class="sec__t">{{ $s['label'] }}</span><span class="sec__role">{{ $s['roleLabel'] }}</span><span class="sec__b" data-badge></span></summary>
                <div class="sec__body sheet-wrap" data-host><div class="empty">Se calculează…</div></div>
            </details>
        @endforeach

        @php $n++; @endphp
        <details class="sec sec--reg" id="registru" open>
            <summary><span class="sec__n">{{ $n }}</span><span class="sec__t">Registru avertizări</span><span class="sec__role">Control</span><span class="sec__b" data-badge-reg></span></summary>
            <div class="sec__body" data-register><div class="empty">Se calculează…</div></div>
        </details>

        @php $n++; @endphp
        <details class="sec sec--final" id="audit" open>
            <summary><span class="sec__n">{{ $n }}</span><span class="sec__t">Audit final &amp; generare ofertă</span><span class="sec__role">Ofertă</span></summary>
            <div class="sec__body">
                <div class="final">
                    <div data-results></div>
                    <div class="stack">
                        @unless ($locked)
                        <form class="panel" method="post" action="{{ $calc->exists ? route('admin.calc.update', $calc) : route('admin.calc.store', $def->key()) }}" data-calc-save>
                            @csrf @if ($calc->exists) @method('PUT') @endif
                            <h2>{{ $calc->exists ? 'Salvează draftul' : 'Salvează ca draft' }}</h2>
                            <div class="field"><label for="title">Titlu / reper <span class="req">*</span></label><input class="input" id="title" name="title" value="{{ old('title', $calc->title) }}" required maxlength="255"></div>
                            <div class="field"><label for="company_id">Client</label><select class="select" id="company_id" name="company_id"><option value="">—</option>@foreach ($companies as $id => $name)<option value="{{ $id }}" @selected((int) old('company_id', $calc->company_id) === $id)>{{ $name }}</option>@endforeach</select></div>
                            <div class="field"><label for="rfq_id">Cerere de ofertă</label><select class="select" id="rfq_id" name="rfq_id"><option value="">—</option>@foreach ($rfqs as $r)<option value="{{ $r->id }}" @selected((int) old('rfq_id', $calc->rfq_id) === $r->id)>{{ $r->number }} · {{ \Illuminate\Support\Str::limit($r->title, 40) }}</option>@endforeach</select></div>
                            <button class="btn btn--accent" type="submit">Salvează draft</button>
                            <p class="hint" style="margin-top:8px">Se păstrează o copie a rezultatelor, a parametrilor și a tarifului utilajului din acest moment.</p>
                        </form>
                        @endunless
                        @if ($calc->exists && can('quotes.manage'))
                        <form class="panel panel--ok" method="post" action="{{ route('admin.quotes.from-calc', $calc) }}">@csrf
                            <h2>Creează ofertă</h2>
                            <p class="small">Oferta pentru {{ $calc->company?->name ?? 'clientul ales' }} se completează cu rezultatul acestui calcul; o puteți modifica înainte de aprobare.</p>
                            <button class="btn btn--accent" type="submit" @disabled($calc->state === 'block' || ! $calc->company_id)>Creează ofertă din acest calcul</button>
                            @if (! $calc->company_id)<p class="hint">Alegeți întâi clientul și salvați draftul.</p>@endif
                        </form>
                        @elseif (! $calc->exists && can('quotes.manage'))
                        <div class="panel"><h2>Creează ofertă</h2><p class="small muted">Salvați întâi draftul (cu clientul ales); apoi oferta se creează dintr-un clic.</p></div>
                        @endif
                        @if (can('rates.edit') && ! $calc->exists)
                        <form class="panel" method="post" action="{{ route('admin.calc.params', $def->key()) }}" data-calc-params>@csrf
                            <h2>Parametri și tarife</h2><p class="small muted">După ce modificați celule din secțiunile de parametri, salvați-le ca versiune nouă, valabilă pentru calculele viitoare.</p>
                            <div class="field"><label for="note">Motivul modificării</label><input class="input" id="note" name="note" maxlength="255"></div><button class="btn btn--sm btn--line" type="submit">Salvează parametrii</button></form>
                        @endif
                        @if ($calc->exists)
                        <div class="panel"><div class="btn-row"><form method="post" action="{{ route('admin.calc.duplicate', $calc) }}">@csrf<button class="btn btn--sm btn--line" type="submit">Duplică cu parametrii actuali</button></form>
                            @unless ($locked)<form method="post" action="{{ route('admin.calc.destroy', $calc) }}" data-confirm="Ștergeți calculul?">@csrf @method('DELETE')<button class="link-btn link-btn--danger small" type="submit">Șterge</button></form>@endunless</div></div>
                        @endif
                    </div>
                </div>
            </div>
        </details>
    </div>
</div>
@php $config = ['evaluateUrl' => route('admin.calc.evaluate', $def->key()), 'inputs' => (object) ($calc->inputs ?? []), 'variant' => $calc->variant, 'readonly' => $locked, 'calculation' => $calc->id]; @endphp
<script type="application/json" id="calc-config">{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection
