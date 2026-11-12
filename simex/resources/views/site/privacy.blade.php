@extends('site.layout')
@section('title', 'Politica de confidențialitate – GRUP SIMEX')
@section('content')
<section class="hero hero--page"><div class="wrap"><h1>Politica de confidențialitate</h1></div></section>
<section class="prose"><div class="wrap">{{ md(setting('site.privacy')) }}</div></section>
@endsection
