@php
    $footer = \App\Models\MenuItem::where('menu', 'footer')->where('locale', app()->getLocale())->where('is_visible', true)->orderBy('sort')->get();
    $main = \App\Models\MenuItem::where('menu', 'header')->where('locale', app()->getLocale())->where('is_visible', true)->where('is_button', false)->orderBy('sort')->get();
    $phones = setting('company.phones', []);
    $emails = setting('company.emails', []);
@endphp
<footer class="footer on-dark">
    <div class="wrap">
        <div class="footer__grid">
            <div>
                <a class="footer__logo" href="{{ url('/') }}">
                    @if ($logo)<img src="{{ $logo->url() }}" alt="{{ setting('company.name', 'ELGAROM') }}" width="{{ $logo->width }}" height="{{ $logo->height }}" loading="lazy">@endif
                </a>
                <p style="max-width:40ch">{{ setting('footer.text') }}</p>
                @if (setting('group.url'))
                    <p><a href="{{ safe_url(setting('group.url')) }}" rel="noopener" target="_blank">{{ setting('group.name', 'Argento Group') }}</a></p>
                @endif
            </div>
            <div>
                <h2>Site</h2>
                <ul>@foreach ($main as $i)<li><a href="{{ safe_url($i->url) }}">{{ $i->label }}</a></li>@endforeach</ul>
            </div>
            <div>
                <h2>Informații</h2>
                <ul>@foreach ($footer as $i)<li><a href="{{ safe_url($i->url) }}">{{ $i->label }}</a></li>@endforeach</ul>
            </div>
            <div>
                <h2>Contact</h2>
                <ul>
                    @foreach ($phones as $p)
                        <li>{{ $p['label'] }}: <a href="{{ tel_href($p['number']) }}" data-track="contact_click" data-track-label="telefon">{{ $p['number'] }}</a></li>
                    @endforeach
                    @foreach ($emails as $e)
                        <li><a href="mailto:{{ $e['email'] }}" data-track="contact_click" data-track-label="email">{{ $e['email'] }}</a></li>
                    @endforeach
                    <li>{{ company_address() }}</li>
                </ul>
            </div>
        </div>
        <div class="footer__base">
            <span>© {{ date('Y') }} {{ setting('company.legal_name') ?: setting('company.name', 'ELGAROM') }}@if (setting('company.cui')), CUI {{ setting('company.cui') }}@endif</span>
            @if (\App\Support\TagConfig::needsConsent())<button type="button" data-consent-open>Preferințe cookies</button>@endif
        </div>
    </div>
</footer>
