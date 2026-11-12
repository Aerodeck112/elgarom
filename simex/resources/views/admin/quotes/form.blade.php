@extends('admin.layout')
@section('title', $quote->exists ? $quote->label() : 'Ofertă nouă')
@push('head') @vite('resources/js/quote.js') @endpush
@section('content')
@php
    use App\Support\Vocab;
    $rows = old('items', $items->toArray());
    $line = fn ($i, $it = []) => view('admin.quotes._line', ['i' => $i, 'it' => $it])->render();
    $F = fn ($n, $l, $t = 'text', $x = []) => ['name' => $n, 'label' => $l, 'type' => $t] + $x;
    $cur = array_combine(config('simex.currencies'), config('simex.currencies'));
@endphp
<div class="page-head"><div><p class="eyebrow"><a href="{{ route('admin.quotes.index') }}">Oferte comerciale</a></p><h1 class="page-title">{{ $quote->exists ? 'Modifică '.$quote->label() : 'Ofertă nouă' }}</h1></div></div>
<ol class="steps"><li class="is-current">Client și emitent</li><li class="is-current">Poziții și prețuri</li><li class="is-current">Condiții comerciale</li><li>Aprobare internă</li><li>PDF și trimitere</li></ol>
<form class="stack" method="post" action="{{ $quote->exists ? route('admin.quotes.update', $quote) : route('admin.quotes.store') }}" data-quote-form>
    @csrf @if ($quote->exists) @method('PUT') @endif <input type="hidden" name="rfq_id" value="{{ old('rfq_id', $quote->rfq_id) }}">
    <div class="panel form-grid"><h2 style="grid-column:span 12">1 · Client și emitent</h2>
        @include('admin.partials.field', ['f' => $F('company_id', 'Client', 'select', ['options' => $companies->all(), 'rules' => 'required', 'span' => 4]), 'value' => $quote->company_id])
        <div class="field col-4"><label for="contact_id">Persoană de contact</label><select class="select" id="contact_id" name="contact_id" data-contacts-for="f-company_id" data-url="{{ route('admin.companies.contacts', '__ID__') }}" data-selected="{{ old('contact_id', $quote->contact_id) }}"></select></div>
        <div class="field col-4"><label for="entity_id">Societatea emitentă <span class="req">*</span></label>
            <select class="select" id="entity_id" name="entity_id" required @disabled($quote->exists)>@foreach ($entities as $e)<option value="{{ $e->id }}" @selected((int) old('entity_id', $quote->entity_id) === $e->id)>{{ $e->name }}@unless ($e->canIssue()) – date juridice neconfirmate @endunless</option>@endforeach</select>
            <p class="hint">Emitentul stabilește numărul și identitatea ofertei și nu se mai schimbă după creare.</p></div>
        @include('admin.partials.field', ['f' => $F('title', 'Titlu / proiect', 'text', ['span' => 6]), 'value' => $quote->title])
        @include('admin.partials.field', ['f' => $F('type', 'Tipul ofertei', 'select', ['options' => Vocab::QUOTE_TYPES, 'rules' => 'required', 'span' => 3]), 'value' => $quote->type])
        @include('admin.partials.field', ['f' => $F('language', 'Limba documentului', 'select', ['options' => config('simex.languages'), 'rules' => 'required', 'span' => 3]), 'value' => $quote->language])
    </div>
    <div class="panel panel--flush"><div class="panel__title">2 · Poziții și prețuri <button class="btn btn--sm btn--line" type="button" data-add-line>Adaugă poziție</button></div>
        <div class="table-wrap" style="border:0"><table class="t lines"><thead><tr><th>#</th><th>Tip</th><th>Cod</th><th>Denumire</th><th class="num">Cantitate</th><th>U.M.</th>@if (can('costs.view'))<th class="num">Cost intern</th>@endif<th class="num">Preț unitar</th><th class="num">Disc. %</th><th class="num">Total</th><th></th><th></th></tr></thead>
        <tbody data-lines>@foreach ($rows as $i => $it){!! $line($i, $it) !!}@endforeach</tbody></table></div>
        <div style="padding:14px 18px"><div class="totals">
            <div><span>Subtotal</span><span class="v" data-sum="subtotal">0,00</span></div>
            <div><label for="discount_percent">Discount general %</label><input class="input input--num" style="width:90px" id="discount_percent" name="discount_percent" value="{{ old('discount_percent', rtrim(rtrim(number_format((float) $quote->discount_percent, 2, ',', ''), '0'), ',')) }}" inputmode="decimal" @disabled(! can('quotes.discount'))></div>
            <div><span>Total fără TVA</span><span class="v" data-sum="net">0,00</span></div>
            <div><label for="vat_rate">TVA %</label><input class="input input--num" style="width:90px" id="vat_rate" name="vat_rate" value="{{ old('vat_rate', rtrim(rtrim(number_format((float) $quote->vat_rate, 2, ',', ''), '0'), ',')) }}" inputmode="decimal" required></div>
            <div><span>TVA</span><span class="v" data-sum="vat">0,00</span></div><div class="grand"><span>Total</span><span class="v" data-sum="gross">0,00</span></div>
        </div><p class="hint" style="text-align:right">Totalurile de aici sunt orientative; valorile oficiale se recalculează pe server la salvare. Pozițiile opționale nu intră în total.</p></div>
    </div>
    <template id="line-template">{!! $line('__I__') !!}</template>
    <div class="panel form-grid"><h2 style="grid-column:span 12">3 · Condiții comerciale</h2>
        @include('admin.partials.field', ['f' => $F('currency', 'Monedă', 'select', ['options' => $cur, 'rules' => 'required', 'span' => 3]), 'value' => $quote->currency])
        @include('admin.partials.field', ['f' => $F('exchange_rate', 'Curs RON/EUR de referință', 'decimal', ['span' => 3]), 'value' => $quote->exchange_rate])
        @include('admin.partials.field', ['f' => $F('exchange_rate_date', 'Data cursului', 'date', ['span' => 3]), 'value' => $quote->exchange_rate_date])
        @include('admin.partials.field', ['f' => $F('valid_until', 'Valabilă până la', 'date', ['span' => 3, 'hint' => 'Gol = data emiterii + valabilitatea implicită.']), 'value' => $quote->valid_until])
        @include('admin.partials.field', ['f' => $F('lead_time', 'Termen estimat de fabricație', 'text', ['span' => 4]), 'value' => $quote->lead_time])
        @include('admin.partials.field', ['f' => $F('payment_terms', 'Condiții de plată', 'text', ['span' => 4]), 'value' => $quote->payment_terms])
        @include('admin.partials.field', ['f' => $F('min_order', 'Cantitate minimă', 'text', ['span' => 4]), 'value' => $quote->min_order])
        @include('admin.partials.field', ['f' => $F('delivery_terms', 'Condiții de livrare', 'text', ['span' => 8]), 'value' => $quote->delivery_terms])
        @include('admin.partials.field', ['f' => $F('incoterm', 'Incoterms', 'select', ['options' => array_combine(Vocab::INCOTERMS, Vocab::INCOTERMS), 'span' => 4]), 'value' => $quote->incoterm])
        @include('admin.partials.field', ['f' => $F('intro', 'Text introductiv (apare în document)', 'textarea', ['span' => 6]), 'value' => $quote->intro])
        @include('admin.partials.field', ['f' => $F('notes', 'Note și condiții contractuale (apar în document)', 'textarea', ['span' => 6]), 'value' => $quote->notes])
        @include('admin.partials.field', ['f' => $F('internal_notes', 'Note interne (nu apar în document)', 'textarea'), 'value' => $quote->internal_notes])
    </div>
    <div class="btn-row"><button class="btn btn--accent" type="submit">Salvează oferta</button><a class="btn btn--line" href="{{ $quote->exists ? route('admin.quotes.show', $quote) : route('admin.quotes.index') }}">Renunță</a></div>
</form>
@endsection
