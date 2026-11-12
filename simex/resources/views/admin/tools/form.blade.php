@extends('admin.layout')
@section('title', $tool->exists ? $tool->reference : 'Proiect nou')
@section('content')
@php use App\Support\Vocab; $F = fn ($n, $l, $t = 'text', $x = []) => ['name' => $n, 'label' => $l, 'type' => $t] + $x; @endphp
<div class="page-head"><div><p class="eyebrow"><a href="{{ route('admin.tools.index') }}">Ștanțe și matrițe</a></p><h1 class="page-title">{{ $tool->exists ? 'Modifică '.$tool->reference : 'Proiect nou' }}</h1></div></div>
<form class="panel form-grid" method="post" action="{{ $tool->exists ? route('admin.tools.update', $tool) : route('admin.tools.store') }}" style="max-width:980px">@csrf @if ($tool->exists) @method('PUT') @endif
    @foreach ([$F('name', 'Denumire', 'text', ['rules' => 'required', 'span' => 8]), $F('type', 'Tip', 'select', ['options' => Vocab::TOOL_TYPES, 'rules' => 'required', 'span' => 4]),
        $F('company_id', 'Client', 'select', ['options' => $companies->all(), 'span' => 6]), $F('complexity', 'Complexitate', 'select', ['options' => Vocab::COMPLEXITIES, 'span' => 3]), $F('operations_count', 'Număr de operații', 'number', ['span' => 3]),
        $F('lead_time', 'Termen de execuție', 'text', ['span' => 4]), $F('amortization_quantity', 'Cantitate de amortizare (piese)', 'number', ['span' => 4, 'hint' => 'Costul sculei se împarte la această cantitate.']),
        $F('currency', 'Monedă', 'select', ['options' => array_combine(config('simex.currencies'), config('simex.currencies')), 'rules' => 'required', 'span' => 4]), $F('notes', 'Observații', 'textarea')] as $f)
        @include('admin.partials.field', ['f' => $f, 'value' => $tool->{$f['name']}])
    @endforeach
    <div class="col-12 btn-row"><button class="btn" type="submit">Salvează</button></div>
</form>
@endsection
