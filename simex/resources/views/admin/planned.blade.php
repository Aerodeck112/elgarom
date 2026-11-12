@extends('admin.layout')
@section('title', $title)
@section('content')
<div class="page-head"><div><p class="eyebrow">Planificat – {{ $phase }}</p><h1 class="page-title">{{ $title }}</h1></div><span class="pending">Modul neimplementat încă</span></div>
<div class="grid-2">
    <div class="panel"><h2>Ce va face modulul</h2><ul class="stack" style="padding-left:18px;margin:0">@foreach ($scope as $line)<li>{{ $line }}</li>@endforeach</ul></div>
    <div class="panel"><h2>Stare reală</h2><p>{{ $ready }}</p><p class="muted small">Pagina aceasta ține locul modulului până la implementare, ca meniul să nu conțină butoane fără funcție. Detalii în <code>docs/MODULES_ROADMAP.md</code>.</p></div>
</div>
@endsection
