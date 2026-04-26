@if ($paginator->hasPages())
<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">

    {{-- Mobile: prev / next only --}}
    <div class="pagination-mobile" style="display:none;width:100%;justify-content:space-between;gap:8px">
        @if ($paginator->onFirstPage())
            <span class="btn btn-sm btn-outline" style="opacity:.4;cursor:default">{!! __('pagination.previous') !!}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-sm btn-outline">{!! __('pagination.previous') !!}</a>
        @endif
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-sm btn-outline">{!! __('pagination.next') !!}</a>
        @else
            <span class="btn btn-sm btn-outline" style="opacity:.4;cursor:default">{!! __('pagination.next') !!}</span>
        @endif
    </div>

    {{-- Desktop: info + page numbers --}}
    <div class="pagination-desktop" style="display:flex;align-items:center;justify-content:space-between;width:100%;gap:12px">
        <p style="font-size:.85rem;color:var(--text-dim)">
            @if ($paginator->firstItem())
                {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
            @else
                0
            @endif
            / {{ $paginator->total() }}
        </p>

        <div style="display:flex;gap:4px;align-items:center">
            {{-- Prev --}}
            @if ($paginator->onFirstPage())
                <span class="pg-btn pg-disabled">
                    <svg style="width:16px;height:16px" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pg-btn" aria-label="{{ __('pagination.previous') }}">
                    <svg style="width:16px;height:16px" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </a>
            @endif

            {{-- Pages --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pg-btn pg-disabled">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pg-btn pg-active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pg-btn">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pg-btn" aria-label="{{ __('pagination.next') }}">
                    <svg style="width:16px;height:16px" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                </a>
            @else
                <span class="pg-btn pg-disabled">
                    <svg style="width:16px;height:16px" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                </span>
            @endif
        </div>
    </div>
</nav>

<style>
.pg-btn{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:4px 10px;font-size:.85rem;font-weight:600;border-radius:var(--radius);background:var(--surface);color:var(--text-dim);border:1px solid var(--border);cursor:pointer;transition:background .15s,color .15s,border-color .15s;text-decoration:none}
.pg-btn:hover{background:var(--surface2);color:var(--text);border-color:var(--gold)}
.pg-active{background:var(--gold) !important;color:#0a0a0a !important;border-color:var(--gold) !important;cursor:default}
.pg-disabled{opacity:.4;cursor:default;pointer-events:none}
@media(max-width:640px){
  .pagination-mobile{display:flex !important}
  .pagination-desktop{display:none !important}
}
</style>
@endif
