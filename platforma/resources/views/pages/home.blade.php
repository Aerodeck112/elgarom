@extends('layouts.site')
@php
    $scene = collect($data['sections'] ?? [])->firstWhere('type', 'scene');
    $steps = array_values(array_filter($scene['data']['items'] ?? [], fn ($i) => ! empty($i['title'])));
    $facts = \App\Models\Capability::confirmed()->where('show_on_home', true)->get();
@endphp
@push('head')
    <link rel="preload" as="image" href="{{ asset('img/scena-poster.webp') }}" type="image/webp" fetchpriority="high">
@endpush
@section('content')
    <section class="scene on-dark" data-scene aria-labelledby="hero-title">
        <div class="scene__stage" aria-hidden="true">
            <img class="scene__poster" src="{{ asset('img/scena-poster.webp') }}" alt="" width="1920" height="1080" fetchpriority="high">
            <canvas class="scene__canvas" data-scene-canvas></canvas>
            <div class="scene__shade"></div>
            @if ($steps)
                <div class="scene__progress" data-scene-progress>@foreach ($steps as $s)<span></span>@endforeach</div>
            @endif
        </div>
        <div class="scene__flow">
            <div class="wrap scene__intro">
                <h1 id="hero-title">{{ $data['title'] }}</h1>
                @if (! empty($data['lead']))<p class="lead">{{ $data['lead'] }}</p>@endif
                <div class="btn-row">
                    <a class="btn" href="{{ url('/solicita-oferta') }}" data-track="cta_click" data-track-label="hero_oferta">Solicită ofertă</a>
                    <a class="btn btn--line" href="{{ url('/procesul-de-zincare') }}" data-track="cta_click" data-track-label="hero_proces">Descoperă procesul</a>
                </div>
                @if ($facts->isNotEmpty())
                    <ul class="scene__facts">
                        @foreach ($facts as $f)
                            <li><span>{{ $f->label }}: <strong>{{ $f->display() }}</strong></span></li>
                        @endforeach
                    </ul>
                @endif
                @if ($steps)<p class="scene__skip"><a href="#dupa-scena">Sari peste ilustrație</a></p>@endif
            </div>
            @if ($steps)
                <ol class="wrap scene__steps" data-scene-steps>
                    @foreach ($steps as $s)
                        <li class="scene__step"><div class="scene__card"><h2>{{ $s['title'] }}</h2>@if (! empty($s['text']))<p>{{ $s['text'] }}</p>@endif</div></li>
                    @endforeach
                </ol>
                @if (! empty($scene['data']['note']))
                    <p class="wrap scene__note">{{ $scene['data']['note'] }} <a href="{{ url('/procesul-de-zincare') }}">Vezi etapele reale</a>.</p>
                @endif
            @endif
        </div>
    </section>
    <div id="dupa-scena" tabindex="-1"></div>
    @include('partials.sections', ['sections' => $data['sections'] ?? []])
@endsection
@push('end')
    @vite('resources/js/scene.js')
@endpush
