@if (! empty($d['image']) && media_item($d['image']))
<section class="band" aria-label="{{ $d['caption'] ?? 'Fotografie' }}">
    <figure>
        <x-img :media="$d['image']" sizes="100vw" />
        @if (! empty($d['caption']))<figcaption>{{ $d['caption'] }}</figcaption>@endif
    </figure>
</section>
@endif
