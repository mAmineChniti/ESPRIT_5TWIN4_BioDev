<div class="sticky top-0 z-30 flex items-center gap-3 border-b border-border bg-card/85 px-4 py-3 backdrop-blur sm:px-6">
    <april:sidebar-trigger />
    <h1 class="text-base font-semibold tracking-tight">@yield('title', 'Dashboard')</h1>
    <div class="ml-auto flex items-center gap-3">
        <april:badge variant="secondary" class="hidden sm:inline-flex">Back Office</april:badge>
        <x-theme-toggle />
        @auth
            <april:avatar size="sm" title="{{ Auth::user()->name }}">
                <x-slot:fallback class="bg-primary text-xs font-bold text-primary-foreground">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</x-slot:fallback>
            </april:avatar>
        @endauth
    </div>
</div>
