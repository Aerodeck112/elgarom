@if ($paginator->total() > 0)
<div class="pagination">
    <span>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} din {{ $paginator->total() }}</span>
    @if ($paginator->hasPages())
    <nav aria-label="Pagini">
        @if (! $paginator->onFirstPage())<a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Înapoi</a>@endif
        <span class="cur">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">Înainte ›</a>@endif
    </nav>
    @endif
</div>
@endif
