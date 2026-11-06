@props(['media' => null, 'sizes' => '100vw', 'eager' => false, 'alt' => null, 'class' => null])
@php $m = media_item($media); @endphp
@if ($m)
    <picture>
        @if ($m->srcset())
            <source type="image/webp" srcset="{{ $m->srcset() }}" sizes="{{ $sizes }}">
        @endif
        <img src="{{ $m->url() }}" alt="{{ $alt ?? $m->altText() ?? '' }}" width="{{ $m->width }}" height="{{ $m->height }}"
             @if ($eager) fetchpriority="high" @else loading="lazy" decoding="async" @endif
             @if ($class) class="{{ $class }}" @endif
             style="object-position: {{ preg_match('/^\d{1,3}% \d{1,3}%$/', $m->focal) ? $m->focal : '50% 50%' }}">
    </picture>
@endif
