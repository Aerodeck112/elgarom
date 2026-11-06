<section class="sec {{ $tone ?? 'sec--white' }}">
    <div class="wrap contact">
        <div>
            @if (! empty($d['heading']))<h2>{{ $d['heading'] }}</h2>@endif
            <dl class="contact__list">
                @foreach (setting('company.phones', []) as $p)
                    <div><dt>{{ __($p['label']) }}</dt><dd><a href="{{ tel_href($p['number']) }}" data-track="contact_click" data-track-label="telefon">{{ $p['number'] }}</a></dd></div>
                @endforeach
                @foreach (setting('company.emails', []) as $e)
                    <div><dt>{{ app()->getLocale() === \App\Support\Locales::default() ? 'Email '.mb_strtolower($e['label']) : __('Email').' – '.__($e['label']) }}</dt><dd><a href="mailto:{{ $e['email'] }}" data-track="contact_click" data-track-label="email">{{ $e['email'] }}</a></dd></div>
                @endforeach
                <div><dt>{{ __('Adresă') }}</dt><dd>{{ company_address() }}</dd></div>
                @if (lsetting('company.hours'))<div><dt>{{ __('Program') }}</dt><dd>{{ lsetting('company.hours') }}</dd></div>@endif
            </dl>
        </div>
        @php $mq = setting('company.map_query', company_address()); @endphp
        <div class="map on-dark" data-map data-src="https://www.google.com/maps?q={{ urlencode($mq) }}&output=embed">
            <div class="map__grid" aria-hidden="true"></div>
            <div class="map__pin" aria-hidden="true"></div>
            <div class="map__body">
                <p style="max-width:36ch">{{ __('Harta Google se încarcă doar la cerere, pentru că Google poate seta cookie-uri.') }}</p>
                <div class="btn-row">
                    <button class="btn btn--sm" type="button" data-map-load>{{ __('Afișează harta') }}</button>
                    <a class="btn btn--sm btn--line" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($mq) }}" target="_blank" rel="noopener">{{ __('Deschide în Google Maps') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
