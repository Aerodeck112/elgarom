{{-- Language tabs for content managed per language. $url(code) builds the link; $loc is the active language. --}}
@php $enabled = \App\Support\Locales::enabled(); @endphp
<nav class="locale-tabs" aria-label="Limba conținutului">
    @foreach (\App\Support\Locales::all() as $code => $l)
        <a href="{{ $url($code) }}" @if ($code === $loc) aria-current="page" @endif>{{ $l['name'] }}@if (! in_array($code, $enabled, true)) <span class="tag">dezactivată</span>@endif</a>
    @endforeach
</nav>
