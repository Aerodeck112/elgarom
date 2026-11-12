@extends('admin.layout')
@section('title', $rfq->exists ? $rfq->number : 'Cerere nouă')
@push('head') @vite('resources/js/quote.js') @endpush
@section('content')
@php
    use App\Support\Vocab;
    $rows = old('items', $rfq->exists ? $rfq->items->toArray() : []);
    $row = function ($i, $it = []) { return view('admin.rfqs._item', ['i' => $i, 'it' => $it])->render(); };
@endphp
<div class="page-head"><div><p class="eyebrow"><a href="{{ route('admin.rfqs.index') }}">Cereri de ofertă</a></p><h1 class="page-title">{{ $rfq->exists ? 'Modifică '.$rfq->number : 'Înregistrează cerere de ofertă' }}</h1></div></div>
<form class="stack" method="post" action="{{ $rfq->exists ? route('admin.rfqs.update', $rfq) : route('admin.rfqs.store') }}" data-rows-form>
    @csrf @if ($rfq->exists) @method('PUT') @endif
    <div class="panel form-grid">
        @include('admin.partials.field', ['f' => ['name' => 'company_id', 'label' => 'Client', 'type' => 'select', 'options' => $companies->all(), 'rules' => 'required', 'span' => 4], 'value' => $rfq->company_id])
        <div class="field col-4"><label for="contact_id">Persoană de contact</label><select class="select" id="contact_id" name="contact_id" data-contacts-for="f-company_id" data-url="{{ route('admin.companies.contacts', '__ID__') }}" data-selected="{{ old('contact_id', $rfq->contact_id) }}"></select></div>
        @include('admin.partials.field', ['f' => ['name' => 'entity_id', 'label' => 'Compania din grup vizată', 'type' => 'select', 'options' => $entities->all(), 'span' => 4], 'value' => $rfq->entity_id])
        @include('admin.partials.field', ['f' => ['name' => 'title', 'label' => 'Titlu', 'type' => 'text', 'rules' => 'required', 'span' => 8], 'value' => $rfq->title])
        @include('admin.partials.field', ['f' => ['name' => 'source', 'label' => 'Sursă', 'type' => 'select', 'options' => Vocab::LEAD_SOURCES, 'span' => 4], 'value' => $rfq->source])
        @include('admin.partials.field', ['f' => ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => Vocab::RFQ_STATUSES, 'rules' => 'required', 'span' => 3], 'value' => $rfq->status])
        @include('admin.partials.field', ['f' => ['name' => 'priority', 'label' => 'Prioritate', 'type' => 'select', 'options' => Vocab::PRIORITIES, 'rules' => 'required', 'span' => 3], 'value' => $rfq->priority])
        @include('admin.partials.field', ['f' => ['name' => 'received_at', 'label' => 'Primită la', 'type' => 'date', 'span' => 3], 'value' => $rfq->received_at])
        @include('admin.partials.field', ['f' => ['name' => 'due_at', 'label' => 'Termen de răspuns', 'type' => 'date', 'span' => 3], 'value' => $rfq->due_at])
        @include('admin.partials.field', ['f' => ['name' => 'owner_id', 'label' => 'Responsabil comercial', 'type' => 'select', 'options' => $users->all(), 'span' => 6], 'value' => $rfq->owner_id])
        @include('admin.partials.field', ['f' => ['name' => 'engineer_id', 'label' => 'Inginer ofertare', 'type' => 'select', 'options' => $users->all(), 'span' => 6], 'value' => $rfq->engineer_id])
        <div class="field col-12"><span class="label">Servicii solicitate</span><div class="btn-row">@foreach (Vocab::SERVICES as $k => $l)<label class="check"><input type="checkbox" name="services[]" value="{{ $k }}" @checked(in_array($k, old('services', $rfq->services ?? []), true))> {{ $l }}</label>@endforeach</div></div>
        @include('admin.partials.field', ['f' => ['name' => 'description', 'label' => 'Descrierea cererii', 'type' => 'textarea'], 'value' => $rfq->description])
        @include('admin.partials.field', ['f' => ['name' => 'notes', 'label' => 'Note interne', 'type' => 'textarea'], 'value' => $rfq->notes])
    </div>
    <div class="panel panel--flush"><div class="panel__title">Repere solicitate <button class="btn btn--sm btn--line" type="button" data-add-row>Adaugă reper</button></div>
        <div class="table-wrap" style="border:0"><table class="t lines"><thead><tr><th>Serviciu</th><th>Cod</th><th>Denumire</th><th>Desen</th><th>Rev.</th><th>Material</th><th class="num">Cantitate</th><th class="num">Cant. anuală</th><th class="num">Lot</th><th></th></tr></thead>
        <tbody data-rows>@foreach ($rows as $i => $it){!! $row($i, $it) !!}@endforeach</tbody></table></div>
    </div>
    <template id="row-template">{!! $row('__I__') !!}</template>
    <div class="btn-row"><button class="btn" type="submit">Salvează cererea</button><a class="btn btn--line" href="{{ $rfq->exists ? route('admin.rfqs.show', $rfq) : route('admin.rfqs.index') }}">Renunță</a></div>
</form>
@endsection
