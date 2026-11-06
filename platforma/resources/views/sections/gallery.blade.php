@php $imgs = \App\Models\Media::whereIn('id', $d['images'] ?? [])->get()->sortBy(fn ($m) => array_search($m->id, $d['images'] ?? []))->values(); @endphp
@if ($imgs->isNotEmpty())
<section class="sec {{ $tone ?? 'sec--white' }}">
    <div class="wrap">
        @include('sections._head')
        <div class="gallery" data-gallery>
            @foreach ($imgs as $m)
                <figure class="gallery__item">
                    <button type="button" data-full="{{ $m->variantUrl(2400) }}" data-caption="{{ $m->caption ?: $m->alt }}" aria-label="Mărește: {{ $m->alt }}">
                        <x-img :media="$m" sizes="(max-width: 760px) 100vw, {{ $loop->index % 6 === 0 ? '66vw' : '33vw' }}" />
                    </button>
                </figure>
            @endforeach
        </div>
    </div>
</section>
@endif
