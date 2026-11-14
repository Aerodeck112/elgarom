@extends('site.layout')
@section('content')
@php
    $byCode = $companies->keyBy('code');
    // The path of a part through the group: the tool, the part, the coating. A real sequence, so it is numbered.
    $steps = collect([['fepo', 'Scula'], ['argento', 'Piesa'], ['elgarom', 'Protecția']])
        ->filter(fn ($s) => isset($byCode[$s[0]]))->map(fn ($s) => ['key' => $s[0], 'title' => $s[1], 'e' => $byCode[$s[0]]])->values();
    $title = setting('site.title', 'GRUP SIMEX SRL');
@endphp
<section class="hero" data-hero>
    <div class="hero__plate" aria-hidden="true"></div>
    <div class="wrap hero__in">
        <h1 class="hero__title" data-title aria-label="{{ $title }}">@foreach (preg_split('//u', $title, -1, PREG_SPLIT_NO_EMPTY) as $ch)<span aria-hidden="true" @class(['sp' => $ch === ' '])>{{ $ch === ' ' ? ' ' : $ch }}</span>@endforeach</h1>
        <p class="hero__sub" data-in>{{ setting('site.subtitle', 'Soluții industriale integrate. Precizie, tehnologie și experiență în prelucrarea metalelor.') }}</p>
        <p class="hero__intro" data-in>{{ setting('site.intro', 'Grup Simex reunește trei activități industriale complementare: producția de piese și componente metalice, proiectarea și fabricația ștanțelor și matrițelor, și zincarea electrolitică. Împreună acoperă drumul unui reper de la sculă la piesa finită, protejată anticoroziv.') }}</p>
        <a class="hero__cue" href="#drum" data-in>Urmăriți drumul unui reper</a>
    </div>
</section>

@if ($steps->count() === 3)
<section class="path" id="drum" data-path>
    <div class="wrap path__in">
        <div class="path__stage" aria-hidden="true">
            <svg viewBox="0 0 600 440" role="img" data-scene>
                <defs><clipPath id="pc"><path d="M222 172 H378 V268 H222 V236 H238 V204 H222 Z"/></clipPath><pattern id="hatch" width="8" height="8" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><line x1="0" y1="0" x2="0" y2="8" stroke="#8a929b" stroke-width="1.4"/></pattern></defs>
                <line x1="40" y1="372" x2="560" y2="372" class="ln ln--soft"/>
                {{-- 1 · die set, side view --}}
                <g data-s="1">
                    <rect x="170" y="318" width="260" height="54" class="solid"/><rect x="170" y="318" width="260" height="54" fill="url(#hatch)" opacity=".5"/>
                    <rect x="268" y="318" width="64" height="20" class="void"/>
                    <rect x="96" y="304" width="408" height="12" class="sheet" data-strip/>
                    <g data-upper><rect x="170" y="120" width="260" height="64" class="solid"/><rect x="170" y="120" width="260" height="64" fill="url(#hatch)" opacity=".5"/>
                        <rect x="272" y="184" width="56" height="44" class="solid"/><rect x="196" y="184" width="14" height="56" class="solid"/><rect x="390" y="184" width="14" height="56" class="solid"/>
                        <rect x="282" y="64" width="36" height="56" class="solid"/></g>
                    <path d="M300 30 v24 m-9 -10 l9 10 l9 -10" class="ln ln--hot" data-force/>
                    <rect x="272" y="340" width="56" height="10" class="sheet" data-slug/>
                </g>
                {{-- 2 · part, top view: laser cut, then bend --}}
                <g data-s="2">
                    <rect x="120" y="110" width="360" height="220" class="sheet sheet--raw"/>
                    <path d="M144 134 H372 V306 H144 V252 H170 V188 H144 Z" class="part part--edge" data-body/>
                    <g data-flange><rect x="372" y="134" width="84" height="172" class="part part--edge" data-flangefill/></g>
                    <path d="M144 134 H456 V306 H144 V252 H170 V188 H144 Z" class="cut" data-cut/>
                    <circle cx="214" cy="170" r="13" class="cut" data-hole/><circle cx="214" cy="270" r="13" class="cut" data-hole/><rect x="282" y="196" width="56" height="48" rx="6" class="cut" data-hole/>
                    <line x1="372" y1="126" x2="372" y2="314" class="ln ln--dash" data-fold/>
                    <g data-laser><circle r="15" class="glow"/><circle r="4.5" class="hot"/><line x1="0" y1="-74" x2="0" y2="-8" class="ln ln--hot"/></g>
                </g>
                {{-- 3 · plating bath, side view --}}
                <g data-s="3">
                    <path d="M130 196 V372 H470 V196" class="ln ln--thick"/>
                    <rect x="134" y="232" width="332" height="136" class="bath"/><path d="M134 232 q20 -7 41 0 t41 0 t41 0 t41 0 t41 0 t41 0 t41 0 t45 0" class="ln ln--bath" data-wave/>
                    <line x1="80" y1="70" x2="520" y2="70" class="ln ln--thick"/>
                    <g data-rack><line x1="300" y1="70" x2="300" y2="150" class="ln"/><path d="M230 150 H370" class="ln"/>
                        <path d="M240 150 v22 M360 150 v22" class="ln"/>
                        <g data-plated><path d="M222 172 H378 V268 H222 V236 H238 V204 H222 Z" class="part" data-coat/><circle cx="262" cy="196" r="8" class="void"/><circle cx="262" cy="246" r="8" class="void"/><rect x="298" y="206" width="34" height="28" rx="4" class="void"/></g>
                        <g clip-path="url(#pc)"><rect x="196" y="150" width="30" height="140" class="sheen" data-sheen transform="skewX(-14)"/></g></g>
                </g>
            </svg>
            <ol class="rail" data-rail>@foreach ($steps as $i => $s)<li><span>{{ $i + 1 }}</span>{{ $s['title'] }}</li>@endforeach</ol>
        </div>
        <div class="path__text">
            <h2 class="path__h">De la sculă la piesa finită, în același grup</h2>
            @foreach ($steps as $i => $s)
            <article class="step" data-step>
                <p class="step__n">{{ $i + 1 }} din 3</p>
                <h3>{{ $s['title'] }}</h3>
                <p class="step__who">{{ $s['e']->name }}</p>
                <p>{{ $s['e']->description ?: $s['e']->activity }}</p>
            </article>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="firms" id="companii">
    <div class="wrap">
        <h2 class="firms__h">Companiile grupului</h2>
        <ul class="firms__list">
        @foreach ($companies as $c)
            <li class="firm" style="--accent: {{ $c->accent_color ?: '#ffc40e' }}">
                <a class="firm__a" href="{{ $c->website ? safe_url($c->website) : '#' }}" @if ($c->website) rel="noopener" @endif>
                    <span @class(['firm__logo', 'firm__logo--dark' => $c->logo_on_dark && $c->logo_path])>@if ($c->logoUrl())<img src="{{ $c->logoUrl() }}" alt="Sigla {{ $c->name }}" loading="lazy">@else<b>{{ \Illuminate\Support\Str::of($c->name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(3)->implode('') }}</b>@endif</span>
                    <span class="firm__name">{{ $c->name }}</span>
                    <span class="firm__act">{{ $c->activity }}</span>
                    @if ($c->website)<span class="firm__go">Vizitați {{ preg_replace('/^www\./', '', (string) parse_url($c->website, PHP_URL_HOST)) }}</span>@endif
                </a>
            </li>
        @endforeach
        </ul>
    </div>
</section>
@endsection
