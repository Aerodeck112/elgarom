@if ($paginator->hasPages())
<nav class="pagination" aria-label="Paginare">
    @if (! $paginator->onFirstPage())<a href="{{ $paginator->previousPageUrl() }}" rel="prev">Înapoi</a>@endif
    @foreach ($elements as $element)
        @if (is_string($element))<span>{{ $element }}</span>@endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())<span aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
            @endforeach
        @endif
    @endforeach
    @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">Înainte</a>@endif
</nav>
@endif
