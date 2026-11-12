@extends('site.layout')
@section('content')
<section class="hero"><div class="wrap">
    <p class="kicker" data-reveal>Grup industrial · prelucrarea metalelor</p>
    <h1 data-reveal>{{ setting('site.title', 'GRUP SIMEX SRL') }}</h1>
    <p class="hero__sub" data-reveal>{{ setting('site.subtitle', 'Soluții industriale integrate. Precizie, tehnologie și experiență în prelucrarea metalelor.') }}</p>
    <div class="rule" data-reveal><span></span></div>
    <p class="hero__intro" data-reveal>{{ setting('site.intro', 'Grup Simex reunește trei activități industriale complementare: producția de piese și componente metalice, proiectarea și fabricația ștanțelor și matrițelor, și zincarea electrolitică. Împreună acoperă drumul unui reper de la sculă la piesa finită, protejată anticoroziv.') }}</p>
</div></section>
<section class="cards" id="companii"><div class="wrap">
    <h2 class="sec" data-reveal>Companii și activități</h2>
    <div class="cards__grid">
    @foreach ($companies as $c)
        <article class="card" data-reveal style="--accent: {{ $c->accent_color ?: '#ffc40e' }}">
            <div class="card__logo">@if ($c->logoUrl())<img src="{{ $c->logoUrl() }}" alt="Sigla {{ $c->name }}" loading="lazy">@else<span>{{ $c->name }}</span>@endif</div>
            <h3>{{ $c->name }}</h3><p class="card__act">{{ $c->activity }}</p>@if ($c->description)<p>{{ $c->description }}</p>@endif
            @if ($c->website)<a class="card__link" href="{{ safe_url($c->website) }}" rel="noopener">{{ parse_url($c->website, PHP_URL_HOST) }} <span aria-hidden="true">→</span></a>@endif
        </article>
    @endforeach
    </div>
</div></section>
@endsection
