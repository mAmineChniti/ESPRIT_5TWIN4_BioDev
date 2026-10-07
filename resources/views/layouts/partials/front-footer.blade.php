@php($signedInUser = auth()->user())
<footer class="border-t border-border bg-card">
    <div class="mx-auto flex w-full max-w-5xl flex-col items-center justify-between gap-3 px-4 py-6 sm:flex-row">
        <div>
            <x-logo />
            <p class="mt-2 text-sm text-muted-foreground">From farm to plate.</p>
        </div>
        <nav class="flex items-center gap-1">
            <april:button-link href="{{ route('front.home') }}" variant="link" size="sm">Home</april:button-link>
            <april:button-link href="{{ route('products.index') }}" variant="link" size="sm">Products</april:button-link>
            {{-- Point at the dashboard this visitor can actually reach: every
                 dashboard is role-restricted, so /admin would 403 for most
                 readers and bounce guests to the login screen. --}}
            @auth
                <april:button-link href="{{ $signedInUser->dashboardUrl() }}" variant="link" size="sm">
                    {{ $signedInUser->isAdmin() ? 'Back Office' : 'My Dashboard' }}
                </april:button-link>
            @else
                <april:button-link href="{{ route('login') }}" variant="link" size="sm">Sign in</april:button-link>
            @endauth
        </nav>
    </div>
</footer>