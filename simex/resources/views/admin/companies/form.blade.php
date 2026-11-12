@extends('admin.layout')
@section('title', $company->exists ? $company->name : 'Companie nouă')
@section('content')
@php
    use App\Support\Vocab;
    $F = fn ($name, $label, $type = 'text', $extra = []) => ['name' => $name, 'label' => $label, 'type' => $type] + $extra;
    $fields = [
        $F('name', 'Denumire', 'text', ['rules' => 'required', 'span' => 6]), $F('legal_name', 'Denumire juridică', 'text', ['span' => 6]),
        $F('kind', 'Tip', 'select', ['options' => Vocab::COMPANY_KINDS, 'rules' => 'required', 'span' => 3]), $F('stage', 'Stadiu comercial', 'select', ['options' => Vocab::COMPANY_STAGES, 'rules' => 'required', 'span' => 3]),
        $F('owner_id', 'Responsabil', 'select', ['options' => $users->all(), 'span' => 3]), $F('next_follow_up_at', 'Următorul follow-up', 'date', ['span' => 3]),
        $F('country', 'Țară', 'select', ['options' => Vocab::countries(), 'span' => 3]), $F('county', 'Județ / regiune', 'text', ['span' => 3]), $F('city', 'Localitate', 'text', ['span' => 3]), $F('postal_code', 'Cod poștal', 'text', ['span' => 3]),
        $F('address', 'Adresă', 'text', ['span' => 6]), $F('tax_id', 'Identificator fiscal', 'text', ['span' => 3]), $F('reg_no', 'Nr. registru', 'text', ['span' => 3]),
        $F('industry', 'Domeniu de activitate', 'text', ['span' => 4]), $F('website', 'Website', 'url', ['span' => 4]), $F('language', 'Limba de comunicare', 'select', ['options' => config('simex.languages'), 'rules' => 'required', 'span' => 4]),
        $F('email', 'Email general', 'email', ['span' => 4]), $F('phone', 'Telefon', 'text', ['span' => 4]), $F('source', 'Sursa leadului', 'select', ['options' => Vocab::LEAD_SOURCES, 'span' => 4]),
        $F('lawful_basis', 'Baza legală pentru comunicări comerciale', 'select', ['options' => Vocab::LAWFUL_BASES, 'span' => 6, 'hint' => 'GDPR: fără o bază legală clară, compania nu primește comunicări comerciale.']),
        $F('notes', 'Observații', 'textarea'),
    ];
@endphp
<div class="page-head"><div><p class="eyebrow"><a href="{{ route('admin.companies.index') }}">Clienți și contacte</a></p><h1 class="page-title">{{ $company->exists ? 'Modifică: '.$company->name : 'Companie nouă' }}</h1></div></div>
<form class="panel form-grid" method="post" action="{{ $company->exists ? route('admin.companies.update', $company) : route('admin.companies.store') }}">
    @csrf @if ($company->exists) @method('PUT') @endif
    @foreach ($fields as $f) @include('admin.partials.field', ['f' => $f, 'value' => $company->{$f['name']}]) @endforeach
    <div class="field col-6"><span class="label">Interese industriale</span><div class="btn-row">@foreach (Vocab::SERVICES as $k => $l)<label class="check"><input type="checkbox" name="interests[]" value="{{ $k }}" @checked(in_array($k, old('interests', $company->interests ?? []), true))> {{ $l }}</label>@endforeach</div></div>
    <div class="field col-6"><span class="label">Excludere</span><label class="check"><input type="checkbox" name="do_not_contact" value="1" @checked(old('do_not_contact', $company->do_not_contact))> Nu contacta în scop comercial</label></div>
    <div class="col-12 btn-row"><button class="btn" type="submit">Salvează</button><a class="btn btn--line" href="{{ $company->exists ? route('admin.companies.show', $company) : route('admin.companies.index') }}">Renunță</a></div>
</form>
@endsection
