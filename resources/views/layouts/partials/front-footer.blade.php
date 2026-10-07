<footer class="border-t border-border bg-card">
    <div class="mx-auto flex w-full max-w-5xl flex-col items-center justify-between gap-3 px-4 py-6 sm:flex-row">
        <div>
            <x-logo />
            <p class="mt-2 text-sm text-muted-foreground">From farm to plate.</p>
        </div>
        <nav class="flex items-center gap-1">
            <april:button-link href="{{ url('/') }}" variant="link" size="sm">Home</april:button-link>
            <april:button-link href="{{ url('/products') }}" variant="link" size="sm">Products</april:button-link>
            {{-- /admin is role gated, so only offer it where it resolves. --}}
            @auth
                <april:button-link href="{{ route('dashboard') }}" variant="link" size="sm">Back Office</april:button-link>
            @else
                <april:button-link href="{{ route('login') }}" variant="link" size="sm">Sign in</april:button-link>
            @endauth
        </nav>
    </div>
</footer>
