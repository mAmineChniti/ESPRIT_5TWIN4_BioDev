{{--
    One pagination control, built from theme tokens only.

    Replaces Laravel's stock view, which shipped a separate mobile block and a
    separate desktop block (two implementations of the same control) and styled
    both with hardcoded grays and hand-written dark: variants.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-muted-foreground">
            Showing
            <span class="font-medium text-foreground">{{ $paginator->firstItem() }}</span>
            to
            <span class="font-medium text-foreground">{{ $paginator->lastItem() }}</span>
            of
            <span class="font-medium text-foreground">{{ $paginator->total() }}</span>
            results
        </p>

        <div class="inline-flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span aria-hidden="true"
                      class="inline-flex size-9 items-center justify-center rounded-md border border-border bg-card text-muted-foreground opacity-50">
                    <x-lucide-chevron-left class="size-4" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page"
                   class="inline-flex size-9 items-center justify-center rounded-md border border-border bg-card text-muted-foreground transition hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <x-lucide-chevron-left class="size-4" />
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex size-9 items-center justify-center text-sm text-muted-foreground">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="inline-flex size-9 items-center justify-center rounded-md border border-primary bg-primary text-sm font-medium text-primary-foreground">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" aria-label="Go to page {{ $page }}"
                               class="inline-flex size-9 items-center justify-center rounded-md border border-border bg-card text-sm text-muted-foreground transition hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page"
                   class="inline-flex size-9 items-center justify-center rounded-md border border-border bg-card text-muted-foreground transition hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <x-lucide-chevron-right class="size-4" />
                </a>
            @else
                <span aria-hidden="true"
                      class="inline-flex size-9 items-center justify-center rounded-md border border-border bg-card text-muted-foreground opacity-50">
                    <x-lucide-chevron-right class="size-4" />
                </span>
            @endif
        </div>
    </nav>
@endif