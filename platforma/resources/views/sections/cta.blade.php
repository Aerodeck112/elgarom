<section class="sec sec--dark on-dark">
    <div class="wrap cta">
        <div>
            @if (! empty($d['heading']))<h2>{{ $d['heading'] }}</h2>@endif
            @if (! empty($d['text']))<p>{{ $d['text'] }}</p>@endif
        </div>
        <div class="btn-row">
            @if (! empty($d['button_label']))<a class="btn" href="{{ safe_url($d['button_url'] ?? '/solicita-oferta') }}" data-track="cta_click" data-track-label="cta_sectiune">{{ $d['button_label'] }}</a>@endif
            @if (! empty($d['secondary_label']))<a class="btn btn--line" href="{{ safe_url($d['secondary_url'] ?? '#') }}" @if (str_starts_with($d['secondary_url'] ?? '', 'tel:')) data-track="contact_click" data-track-label="telefon" @endif>{{ $d['secondary_label'] }}</a>@endif
        </div>
    </div>
</section>
