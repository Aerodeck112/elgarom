<section class="sec {{ $tone ?? 'sec--white' }}" id="mesaj">
    <div class="wrap split" style="align-items:start">
        <div>
            @if (! empty($d['heading']))<h2>{{ $d['heading'] }}</h2>@endif
            @if (! empty($d['intro']))<p class="lead">{{ $d['intro'] }}</p>@endif
            <div class="btn-row" style="margin-top:1.5rem">
                <a class="btn btn--line" href="{{ url('/solicita-oferta') }}" data-track="cta_click" data-track-label="formular_complet">Formularul complet de ofertă</a>
            </div>
            <dl class="contact__list" style="margin-top:2rem">
                @foreach (setting('company.phones', []) as $p)
                    <div><dt>{{ $p['label'] }}</dt><dd><a href="{{ tel_href($p['number']) }}" data-track="contact_click" data-track-label="telefon">{{ $p['number'] }}</a></dd></div>
                @endforeach
            </dl>
        </div>
        @include('partials.short-form')
    </div>
</section>
