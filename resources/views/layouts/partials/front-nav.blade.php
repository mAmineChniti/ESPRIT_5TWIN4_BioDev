<header class="sticky top-0 z-40 border-b border-border bg-card/85 backdrop-blur">
    <div class="mx-auto flex w-full max-w-5xl items-center justify-between px-4 py-4">
        <x-logo />
        <nav class="flex items-center gap-2">
            {{-- The logo is the only link home; no duplicate "Home" entry. --}}
            <april:button-link href="{{ route('products.index') }}" variant="ghost"
                @class(['bg-muted' => request()->routeIs('products.*')])>Products</april:button-link>
            <april:button-link href="{{ route('agricultural-regions.index') }}" variant="ghost"
                @class(['bg-muted' => request()->routeIs('agricultural-regions.*')])>Agricultural Regions & Farms</april:button-link>
            <april:button-link href="{{ route('greenwashing') }}" variant="ghost"
                @class(['bg-muted' => request()->routeIs('greenwashing')])>Spot greenwashing</april:button-link>
            @auth
                {{-- Espace Consommateur: AI audit, assistant, recommendations.
                     It reports on the reader's own meals, reviews and reports,
                     so it belongs to consumers alone — offering it to a producer
                     led to a "Log a meal" button that 403s. --}}
                @if(auth()->user()->role === 'consumer')
                    <april:button-link href="{{ route('consumer.space') }}" variant="ghost"
                        @class(['bg-muted' => request()->routeIs('consumer.space', 'consumer.recommendations')])>
                        Consumer Space
                    </april:button-link>
                @endif
                {{-- Resolved from the role: every dashboard is role-restricted,
                     so a fixed href would 403 for the reader who sees this. --}}
                <april:button-link href="{{ auth()->user()->dashboardUrl() }}" variant="outline">My Dashboard</april:button-link>
            @else
                @if(!request()->routeIs('login'))
                    <april:button-link href="{{ route('login') }}" variant="ghost">Log in</april:button-link>
                @endif
                @if(!request()->routeIs('register'))
                    <april:button-link href="{{ route('register') }}">Sign up</april:button-link>
                @endif
            @endauth
            <x-theme-toggle />
        </nav>
    </div>
</header>
