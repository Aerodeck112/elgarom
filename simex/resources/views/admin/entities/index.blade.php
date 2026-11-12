@extends('admin.layout')
@section('title', 'Companiile grupului')
@section('content')
<div class="page-head"><div><h1 class="page-title">Companiile grupului</h1><p class="lead">Fiecare companie are propria identitate vizuală și propriile date juridice. O companie poate emite oferte numai după ce un administrator îi confirmă datele juridice.</p></div></div>
<div class="grid-2">@foreach ($rows as $e)
    <div class="panel"><div class="btn-row" style="justify-content:space-between"><div><p class="eyebrow">{{ $e->code }}</p><h2 style="font-size:16px;margin:0">{{ $e->name }}</h2></div>@if ($e->logoUrl())<img src="{{ $e->logoUrl() }}" alt="Sigla {{ $e->name }}" style="max-height:38px;max-width:150px;background:#15181b;padding:6px;border-radius:3px">@else<span class="pending">siglă neîncărcată</span>@endif</div>
        <p class="small muted">{{ $e->activity }}</p>
        <div class="btn-row">@if ($e->show_on_site)<span class="tag tag--info">pe website</span>@endif @if ($e->is_issuer)@if ($e->canIssue())<span class="tag tag--ok">emitent confirmat</span>@else<span class="tag tag--warn">emitent – date neconfirmate</span>@endif @endif</div>
        <p style="margin-top:12px"><a class="btn btn--sm btn--line" href="{{ route('admin.entities.edit', $e) }}">Administrează</a></p></div>
@endforeach</div>
@endsection
