@extends('layouts.site')
@section('content')
    @include('partials.page-head', ['title' => $service->title, 'lead' => $service->summary, 'crumbs' => [[__('Servicii'), page_url('servicii')], [$service->title, null]]])
    <section class="sec sec--white">
        <div class="wrap service-detail">
            <div>
                @if ($service->image_id)
                    <figure class="frame" style="margin-bottom:2.5rem"><x-img :media="$service->image_id" sizes="(max-width: 900px) 100vw, 60vw" eager /></figure>
                @endif
                <div class="prose">{{ md($service->body) }}</div>
            </div>
            <aside class="service-aside">
                <h2>{{ __('Solicitați o ofertă') }}</h2>
                <p class="muted small">{{ __('Descrieți piesa și încărcați un desen sau o fotografie. Câmpurile necunoscute pot rămâne goale.') }}</p>
                <a class="btn" href="{{ page_url('solicita-oferta') }}" data-track="cta_click" data-track-label="serviciu_oferta">{{ __('Solicită ofertă') }}</a>
                <dl class="contact__list" style="margin-top:1.5rem">
                    @foreach (setting('company.phones', []) as $p)
                        <div><dt>{{ __($p['label']) }}</dt><dd><a href="{{ tel_href($p['number']) }}" data-track="contact_click" data-track-label="telefon">{{ $p['number'] }}</a></dd></div>
                    @endforeach
                </dl>
            </aside>
        </div>
    </section>
    @if ($others->isNotEmpty())
        @include('sections.services', ['d' => ['heading' => 'Alte servicii'], 'tone' => 'sec--zinc'])
    @endif
@endsection
