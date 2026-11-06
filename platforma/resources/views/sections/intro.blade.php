<section class="sec {{ $tone ?? 'sec--white' }}">
    <div class="wrap">
        <div @class(['split', 'split--left' => ($d['image_side'] ?? 'right') === 'left'])>
            <div class="split__text">
                @if (! empty($d['heading']))<h2>{{ $d['heading'] }}</h2>@endif
                <div class="prose">{{ md($d['body'] ?? '') }}</div>
            </div>
            @if (! empty($d['image']))
                <figure class="frame split__media"><x-img :media="$d['image']" sizes="(max-width: 900px) 100vw, 50vw" /></figure>
            @endif
        </div>
    </div>
</section>
