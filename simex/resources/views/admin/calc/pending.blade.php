@extends('admin.layout')
@section('title', $def->title)
@section('content')
<div class="page-head"><div><p class="eyebrow">{{ $def->process }}</p><h1 class="page-title">{{ $def->title }}</h1></div><span class="pending">Formulă în curs de configurare</span></div>
<div class="panel panel--warn" style="max-width:760px"><h2>Modelul Excel nu este încă activ</h2>
    <p>Acest calculator rulează formulele din fișierul <code>{{ $def->file_hint }}</code>. Fișierul nu face parte din pachetul aplicației, pentru că el conține parametri comerciali interni.</p>
    <p>O persoană cu permisiunea «Modificarea formulelor» îl încarcă o singură dată din <strong>Calcul de costuri → Modele Excel</strong>. La încărcare, platforma recalculează toate formulele și le compară cu rezultatele salvate de Excel; modelul poate fi activat numai dacă nu există nicio diferență.</p>
    @if (can('formulas.manage'))<a class="btn" href="{{ route('admin.models.index') }}">Mergi la Modele Excel</a>@endif</div>
@endsection
