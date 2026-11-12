@extends('admin.layout')
@section('title', $quote->label())
@section('content')
@php
    use App\Support\Vocab;
    $see = can('costs.view'); $margin = $quote->marginPercent(); $manage = can('quotes.manage');
    $order = ['ciorna' => 1, 'aprobare' => 2, 'aprobata' => 3, 'trimisa' => 4, 'negociere' => 4, 'acceptata' => 5, 'respinsa' => 5, 'expirata' => 5, 'anulata' => 5];
    $at = $order[$quote->status] ?? 1;
    $next = ['aprobata' => ['trimisa' => 'Marchează ca trimisă', 'anulata' => 'Anulează'], 'trimisa' => ['negociere' => 'În negociere', 'acceptata' => 'Acceptată de client', 'respinsa' => 'Respinsă de client', 'expirata' => 'Expirată'],
        'negociere' => ['acceptata' => 'Acceptată de client', 'respinsa' => 'Respinsă de client', 'expirata' => 'Expirată']][$quote->status] ?? [];
@endphp
<div class="page-head">
    <div><p class="eyebrow"><a href="{{ route('admin.quotes.index') }}">Oferte comerciale</a> · {{ $quote->entity->name }}</p><h1 class="page-title"><span class="mono">{{ $quote->label() }}</span> @if ($quote->title)· {{ $quote->title }}@endif</h1>
        <div class="btn-row" style="margin-top:6px"><span class="tag tag--{{ Vocab::tone($quote->status) }}">{{ $quote->statusLabel() }}</span>@unless ($quote->is_current)<span class="tag tag--warn">revizie înlocuită</span>@endunless<span class="tag">{{ Vocab::QUOTE_TYPES[$quote->type] ?? '' }}</span><span class="tag">{{ strtoupper($quote->language) }}</span></div></div>
    <div class="btn-row">
        @if (can('export'))<a class="btn btn--line" href="{{ route('admin.quotes.pdf', $quote) }}" target="_blank" rel="noopener">PDF</a><a class="btn btn--line" href="{{ route('admin.quotes.excel', $quote) }}">Excel</a>@endif
        @if ($manage && $quote->isEditable())<a class="btn" href="{{ route('admin.quotes.edit', $quote) }}">Modifică</a>@endif
    </div>
</div>
<ol class="steps">@foreach (['Ciornă', 'Aprobare internă', 'Aprobată și emisă', 'Trimisă / negociere', 'Decizia clientului'] as $i => $s)<li @class(['is-done' => $i + 1 < $at, 'is-current' => $i + 1 === $at])>{{ $s }}</li>@endforeach</ol>
@unless ($quote->entity->canIssue())<div class="alert alert--warn">Emitentul «{{ $quote->entity->name }}» nu are datele juridice confirmate. Oferta poate fi pregătită, dar nu poate fi aprobată și emisă până la confirmare (Administrare → Companiile grupului).</div>@endunless
@if ($reasons && in_array($quote->status, ['ciorna', 'aprobare'], true))<div class="alert alert--warn"><strong>Control marje și discounturi</strong><ul>@foreach ($reasons as $r)<li>{{ $r }}</li>@endforeach</ul></div>@endif
<div class="cols"><div class="stack">
    <div class="panel panel--flush"><div class="panel__title">Poziții</div>
        <table class="t"><thead><tr><th>#</th><th>Cod</th><th>Denumire</th><th class="num">Cantitate</th>@if ($see)<th class="num">Cost intern</th>@endif<th class="num">Preț unitar</th><th class="num">Disc.</th><th class="num">Total</th></tr></thead><tbody>
        @forelse ($quote->items as $it)
            <tr><td class="mono">{{ $it->position }}</td><td class="mono">{{ $it->code ?: '—' }}</td><td>{{ $it->name }} @if ($it->is_optional)<span class="tag">opțional</span>@endif
                @if ($it->description)<div class="small muted wrap" style="white-space:pre-line">{{ $it->description }}</div>@endif @if ($it->calculation)<a class="link small mono" href="{{ route('admin.calc.show', $it->calculation) }}">{{ $it->calculation->reference }}</a>@endif</td>
                <td class="num">{{ qty($it->quantity) }} {{ $it->unit }}</td>@if ($see)<td class="num">@if ($it->unit_cost === null)<span class="pending">lipsă</span>@else{{ money($it->unit_cost, 4) }}@endif</td>@endif
                <td class="num">@if ($it->unit_price <= 0)<span class="pending">de stabilit</span>@else{{ money($it->unit_price, 4) }}@endif</td><td class="num">{{ $it->discount_percent > 0 ? money($it->discount_percent, 1).' %' : '—' }}</td><td class="num">{{ money($it->line_total) }}</td></tr>
        @empty <tr><td colspan="8"><div class="empty">Oferta nu are poziții.</div></td></tr>
        @endforelse</tbody></table>
        <div style="padding:14px 18px"><div class="totals"><div><span>Subtotal</span><span class="v">{{ money($quote->subtotal) }}</span></div>@if ($quote->discount_percent > 0)<div><span>Discount {{ money($quote->discount_percent, 1) }} %</span><span class="v">−{{ money($quote->subtotal - $quote->total_net) }}</span></div>@endif
            <div><span>Total fără TVA</span><span class="v">{{ money($quote->total_net) }}</span></div><div><span>TVA {{ money($quote->vat_rate, 1) }} %</span><span class="v">{{ money($quote->vat_amount) }}</span></div><div class="grand"><span>Total {{ $quote->currency }}</span><span class="v">{{ money($quote->total_gross) }}</span></div></div></div>
    </div>
    @if ($manage && $quote->isEditable() && ($calculations->isNotEmpty() || $tools->isNotEmpty()))
    <form class="panel form-grid" method="post" action="{{ route('admin.quotes.import', $quote) }}">@csrf<h2 style="grid-column:span 12">Adaugă din calculatoare</h2>
        <div class="field col-6"><label>Calcul salvat</label><select class="select" name="calculation_id"><option value="">—</option>@foreach ($calculations as $c)<option value="{{ $c->id }}">{{ $c->reference }} · {{ \Illuminate\Support\Str::limit($c->title, 46) }}</option>@endforeach</select></div>
        <div class="field col-6"><label>Ștanță / matriță</label><select class="select" name="tool_project_id"><option value="">—</option>@foreach ($tools as $t)<option value="{{ $t->id }}">{{ $t->reference }} · {{ \Illuminate\Support\Str::limit($t->name, 46) }}</option>@endforeach</select></div>
        <div class="col-12" style="grid-column:span 12"><button class="btn btn--sm" type="submit">Adaugă pozițiile</button> <span class="hint">Piesele din calculatorul de tablă vin cu preț și cost; orele de mașină vin doar cu cost (dacă există tarif), iar prețul îl stabiliți dumneavoastră.</span></div></form>
    @endif
    <div class="panel"><h2>Condiții comerciale</h2><dl class="kv"><dt>Termen de fabricație</dt><dd>{{ $quote->lead_time ?: '—' }}</dd><dt>Plată</dt><dd>{{ $quote->payment_terms ?: '—' }}</dd><dt>Livrare</dt><dd>{{ $quote->delivery_terms ?: '—' }} {{ $quote->incoterm }}</dd><dt>Cantitate minimă</dt><dd>{{ $quote->min_order ?: '—' }}</dd>
        <dt>Curs de referință</dt><dd>{{ $quote->exchange_rate ? money($quote->exchange_rate, 4).' RON/EUR'.($quote->exchange_rate_date ? ' din '.$quote->exchange_rate_date->format('d.m.Y') : '') : '—' }}</dd></dl>
        @if ($quote->notes)<p class="small wrap" style="white-space:pre-line;margin-top:12px">{{ $quote->notes }}</p>@endif @if ($quote->internal_notes)<p class="small wrap panel--warn" style="white-space:pre-line;margin-top:12px;padding:10px;border-radius:4px"><strong>Intern:</strong> {{ $quote->internal_notes }}</p>@endif</div>
    <div class="panel"><h2>Istoric</h2><ul class="timeline">@foreach ($quote->events as $e)<li><strong>{{ str_replace('_', ' ', $e->type) }}</strong> @if ($e->body)– {{ $e->body }}@endif<div class="small muted">{{ $e->created_at?->format('d.m.Y H:i') }} · {{ $e->user?->name ?? 'sistem' }}</div></li>@endforeach</ul></div>
</div><div class="stack">
    <div class="panel"><h2>Părți</h2><dl class="kv"><dt>Client</dt><dd><a class="link" href="{{ route('admin.companies.show', $quote->company_id) }}">{{ $quote->company?->name }}</a></dd><dt>Contact</dt><dd>{{ $quote->contact?->fullName() ?? '—' }}</dd><dt>Emitent</dt><dd>{{ $quote->entity->legal_name ?: $quote->entity->name }}</dd>
        <dt>Responsabil</dt><dd>{{ $quote->owner?->name ?? '—' }}</dd><dt>Emisă</dt><dd>{{ $quote->issued_at?->format('d.m.Y') ?? 'neemisă' }}</dd><dt>Valabilă până la</dt><dd>{{ $quote->valid_until?->format('d.m.Y') ?? '—' }}</dd>
        @if ($quote->rfq)<dt>Cerere</dt><dd><a class="link mono" href="{{ route('admin.rfqs.show', $quote->rfq) }}">{{ $quote->rfq->number }}</a></dd>@endif @if ($quote->approver)<dt>Aprobată de</dt><dd>{{ $quote->approver->name }}, {{ $quote->approved_at?->format('d.m.Y H:i') }}</dd>@endif</dl></div>
    @if (can('margins.view'))<div class="stat stat--accent"><div class="stat__label">Marjă brută estimată</div><div class="stat__value">@if ($margin === null)<span class="pending">cost incomplet</span>@else{{ money($margin, 1) }} %@endif</div>
        <div class="stat__hint">@if ($quote->cost_total !== null)Cost {{ money($quote->cost_total) }} · profit {{ money($quote->total_net - $quote->cost_total) }} {{ $quote->currency }}@else Unele poziții nu au cost intern.@endif</div></div>@endif
    <div class="panel"><h2>Acțiuni</h2><div class="stack">
        @if ($manage && $quote->status === 'ciorna')<form method="post" action="{{ route('admin.quotes.submit', $quote) }}">@csrf<button class="btn btn--accent" type="submit">Trimite la aprobare internă</button></form>@endif
        @if ($quote->status === 'aprobare')
            @if (can('quotes.approve'))<form method="post" action="{{ route('admin.quotes.approve', $quote) }}" data-confirm="Aprobați și emiteți oferta? După emitere conținutul nu mai poate fi modificat.">@csrf<button class="btn btn--accent" type="submit">Aprobă și emite</button></form>
                <form method="post" action="{{ route('admin.quotes.back', $quote) }}">@csrf<div class="field"><input class="input" name="note" placeholder="Motivul returnării" aria-label="Motivul returnării"></div><button class="btn btn--sm btn--line" type="submit">Returnează ofertantului</button></form>
            @else<p class="small muted">Așteaptă aprobarea unui rol cu permisiunea «Aprobarea ofertelor».</p>@endif
        @endif
        @if ($manage && $next)<form method="post" action="{{ route('admin.quotes.status', $quote) }}">@csrf
            <div class="field"><label for="st">Rezultat comercial</label><select class="select" id="st" name="status">@foreach ($next as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            <div class="field"><label for="rev">Venit confirmat ({{ $quote->currency }}) – opțional</label><input class="input input--num" id="rev" name="confirmed_revenue" inputmode="decimal"><p class="hint">Se completează doar când există o comandă fermă sau o factură. Nu se deduce din ofertă.</p></div>
            <div class="field"><input class="input" name="note" placeholder="Notă" aria-label="Notă"></div><button class="btn btn--sm" type="submit">Actualizează</button></form>
            <p class="hint">Trimiterea prin email din platformă și linkul securizat pentru client sunt planificate în Faza 3. Până atunci, descărcați PDF-ul și marcați aici oferta ca trimisă.</p>@endif
        @if ($manage && $quote->status !== 'ciorna' && $quote->is_current)<form method="post" action="{{ route('admin.quotes.revise', $quote) }}">@csrf<button class="btn btn--sm btn--line" type="submit">Creează revizie nouă</button></form>@endif
        @if ($manage)<form method="post" action="{{ route('admin.quotes.duplicate', $quote) }}">@csrf<button class="btn btn--sm btn--line" type="submit">Duplică într-o ofertă nouă</button></form>@endif
    </div></div>
    @if ($revisions->count() > 1)<div class="panel"><h2>Revizii</h2><table class="t"><tbody>@foreach ($revisions as $r)<tr><td class="mono"><a class="link" href="{{ route('admin.quotes.show', $r) }}">rev. {{ $r->revision }}</a></td><td><span class="tag tag--{{ Vocab::tone($r->status) }}">{{ $r->statusLabel() }}</span></td><td class="num">{{ money($r->total_net) }}</td>
        <td class="actions">@if ($r->id !== $quote->id)<a class="link small" href="{{ route('admin.quotes.compare', [$quote, $r]) }}">Compară</a>@endif</td></tr>@endforeach</tbody></table></div>@endif
</div></div>
@endsection
