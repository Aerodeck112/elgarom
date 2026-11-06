@php
    $items = \App\Models\MenuItem::where('menu', 'header')->where('locale', app()->getLocale())->where('is_visible', true)->orderBy('sort')->get();
    $current = '/'.ltrim(request()->path(), '/');
    $phones = setting('company.phones', []);
@endphp
<header class="header {{ ($solidHeader ?? false) ? 'is-solid' : '' }}" data-header>
    <div class="wrap header__in">
        <a class="header__logo" href="{{ url('/') }}" aria-label="{{ setting('company.name', 'ELGAROM') }} – pagina principală">
            @if ($logo)
                <img src="{{ $logo->url() }}" alt="{{ setting('company.name', 'ELGAROM') }}" width="{{ $logo->width }}" height="{{ $logo->height }}">
            @else
                <span class="cota" style="font-size:1.6rem;color:#fff">ELGAROM</span>
            @endif
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-principal" data-nav-toggle>
            <span class="sr-only">Meniu</span>
            <svg viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 8h20M4 14h20M4 20h13"/></svg>
        </button>
        <nav class="nav" id="nav-principal" aria-label="Navigare principală" data-nav>
            <ul>
                @foreach ($items as $item)
                    @php $active = $current === $item->url || ($item->url !== '/' && str_starts_with($current, rtrim($item->url, '/').'/')); @endphp
                    <li>
                        <a href="{{ safe_url($item->url) }}"
                           @class(['btn btn--sm' => $item->is_button])
                           @if ($active) aria-current="page" @endif
                           @if ($item->is_button) data-track="cta_click" data-track-label="header_oferta" @endif>{{ $item->label }}</a>
                    </li>
                @endforeach
            </ul>
            @if (! empty($phones))
                <p class="header__phone">{{ $phones[0]['label'] }}: <a href="{{ tel_href($phones[0]['number']) }}" data-track="contact_click" data-track-label="telefon">{{ $phones[0]['number'] }}</a></p>
            @endif
        </nav>
    </div>
</header>
