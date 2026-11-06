{{-- Cookie consent. Shown only when statistics or marketing tags are configured; otherwise
     the site sets no optional cookies and there is nothing to consent to. --}}
@if (\App\Support\TagConfig::needsConsent())
<section class="consent" role="dialog" aria-modal="false" aria-labelledby="consent-title" data-consent hidden>
    <h2 id="consent-title">{{ __('Cookie-uri pe acest site') }}</h2>
    <p>{{ __('Folosim cookie-uri strict necesare pentru funcționarea site-ului. Cu acordul dumneavoastră, folosim și cookie-uri de statistică și marketing (Google). Detalii în') }} <a href="{{ page_url('politica-cookies') }}">{{ __('Politica de cookies') }}</a>.</p>
    <form data-consent-form>
        <div class="consent__opts" data-consent-opts hidden>
            <label class="check"><input type="checkbox" checked disabled> <span><strong>{{ __('Strict necesare') }}</strong> {{ __('– sesiune, securitatea formularelor, memorarea acestei alegeri.') }}</span></label>
            @if (\App\Support\TagConfig::hasAnalytics())
                <label class="check"><input type="checkbox" name="analytics"> <span><strong>{{ __('Statistică') }}</strong> {{ __('– Google Analytics: cum este folosit site-ul.') }}</span></label>
            @endif
            @if (\App\Support\TagConfig::hasMarketing())
                <label class="check"><input type="checkbox" name="marketing"> <span><strong>{{ __('Marketing') }}</strong> {{ __('– Google Ads: măsurarea campaniilor.') }}</span></label>
            @endif
        </div>
        <div class="btn-row">
            <button class="btn btn--sm" type="button" data-consent-accept>{{ __('Accept toate') }}</button>
            <button class="btn btn--sm btn--dark" type="button" data-consent-reject>{{ __('Doar necesare') }}</button>
            <button class="btn btn--sm btn--line" type="button" data-consent-customize>{{ __('Aleg eu') }}</button>
            <button class="btn btn--sm btn--line" type="submit" data-consent-save hidden>{{ __('Salvează alegerea') }}</button>
        </div>
    </form>
</section>
@endif
