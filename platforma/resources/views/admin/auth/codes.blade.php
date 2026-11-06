@extends('admin.auth._layout')
@section('title', 'Coduri de recuperare')
@section('content')
<h1>Coduri de recuperare</h1>
<p>Salvați aceste coduri într-un loc sigur (manager de parole). Fiecare cod poate fi folosit <strong>o singură dată</strong>, dacă pierdeți telefonul. Nu vor mai fi afișate.</p>
<div class="codes">@foreach ($codes as $c)<span>{{ $c }}</span>@endforeach</div>
<div class="btn-row" style="margin-top:1.5rem"><a class="btn" href="{{ route('admin.dashboard') }}">Am salvat codurile</a></div>
@endsection
