<div class="sticky top-0 z-30 flex items-center gap-3 border-b border-border bg-card/85 px-4 py-3 backdrop-blur sm:px-6">
    <april:sidebar-trigger />
    <h1 class="text-base font-semibold tracking-tight">@yield('title', __('Dashboard'))</h1>
    <div class="ms-auto flex items-center gap-3">
        <april:badge variant="secondary" class="hidden sm:inline-flex">{{ __('Back Office') }}</april:badge>
        <x-notification-dropdown />
        <x-language-switcher />
        <x-theme-toggle />
        @auth
            <april:avatar size="sm" title="{{ Auth::user()->name }}">
                @if (Auth::user()->avatarUrl())
                    <x-slot:image src="{{ Auth::user()->avatarUrl() }}" alt="{{ Auth::user()->name }}"></x-slot:image>
                @endif
                <x-slot:fallback class="bg-primary text-xs font-bold text-primary-foreground">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</x-slot:fallback>
            </april:avatar>
        @endauth
    </div>
</div>
