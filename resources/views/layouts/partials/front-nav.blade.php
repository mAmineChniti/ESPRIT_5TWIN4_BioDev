<header class="sticky top-0 z-40 border-b border-border bg-card/85 backdrop-blur">
    <div class="mx-auto flex w-full max-w-5xl items-center justify-between px-4 py-4">
        <x-logo />
        <nav class="flex items-center gap-2">
            <april:button-link href="{{ url('/') }}" variant="ghost">Home</april:button-link>
            @auth
                @php
                    $dashRoute = Auth::user()->role === 'admin'
                        ? route('dashboard')
                        : route(Auth::user()->role . '.dashboard');
                @endphp
                <april:button-link href="{{ $dashRoute }}" variant="outline">My Dashboard</april:button-link>
            @else
                @if(!request()->routeIs('login'))
                    <april:button-link href="{{ route('login') }}" variant="ghost">Log in</april:button-link>
                @endif
                @if(!request()->routeIs('register'))
                    <april:button-link href="{{ route('register') }}">Sign up</april:button-link>
                @endif
            @endauth
        </nav>
    </div>
</header>
