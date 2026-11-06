{{-- Cookie consent. Shown only when statistics or marketing tags are configured; otherwise
     the site sets no optional cookies and there is nothing to consent to. --}}
@if (\App\Support\TagConfig::needsConsent())
<section class="consent" role="dialog" aria-modal="false" aria-labelledby="consent-title" data-consent hidden>
    <h2 id="consent-title">Cookie-uri pe acest site</h2>
    <p>Folosim cookie-uri strict necesare pentru funcționarea site-ului. Cu acordul dumneavoastră, folosim și cookie-uri de statistică și marketing (Google). Detalii în <a href="{{ url('/politica-cookies') }}">Politica de cookies</a>.</p>
    <form data-consent-form>
        <div class="consent__opts" data-consent-opts hidden>
            <label class="check"><input type="checkbox" checked disabled> <span><strong>Strict necesare</strong> – sesiune, securitatea formularelor, memorarea acestei alegeri.</span></label>
            @if (\App\Support\TagConfig::hasAnalytics())
                <label class="check"><input type="checkbox" name="analytics"> <span><strong>Statistică</strong> – Google Analytics: cum este folosit site-ul.</span></label>
            @endif
            @if (\App\Support\TagConfig::hasMarketing())
                <label class="check"><input type="checkbox" name="marketing"> <span><strong>Marketing</strong> – Google Ads: măsurarea campaniilor.</span></label>
            @endif
        </div>
        <div class="btn-row">
            <button class="btn btn--sm" type="button" data-consent-accept>Accept toate</button>
            <button class="btn btn--sm btn--dark" type="button" data-consent-reject>Doar necesare</button>
            <button class="btn btn--sm btn--line" type="button" data-consent-customize>Aleg eu</button>
            <button class="btn btn--sm btn--line" type="submit" data-consent-save hidden>Salvează alegerea</button>
        </div>
    </form>
</section>
@endif
