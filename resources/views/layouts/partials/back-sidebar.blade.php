
<april:sidebar>
    <x-slot:header>
        <div class="px-2 py-2">
            <x-logo />
        </div>
    </x-slot:header>

    <x-slot:content>
        <april:sidebar-menu>
            <april:sidebar-group-label>Overview</april:sidebar-group-label>
            @php
                $role = Auth::user()?->role;
                $dashHref = $role === 'admin'
                    ? route('admin.dashboard')
                    : ($role && \Illuminate\Support\Facades\Route::has($role . '.dashboard') ? route($role . '.dashboard') : route('dashboard'));
            @endphp
            <april:sidebar-menu-item>
                <april:sidebar-menu-button-link
                    href="{{ $dashHref }}"
                    :active="request()->routeIs('dashboard') || request()->routeIs('producer.dashboard') || request()->routeIs('processor.dashboard') || request()->routeIs('distributor.dashboard') || request()->routeIs('consumer.dashboard')">
                    <x-lucide-layout-dashboard />
                    <span>Dashboard</span>
                </april:sidebar-menu-button-link>
            </april:sidebar-menu-item>
        </april:sidebar-menu>

        @if(Auth::user()->role === 'admin')
        {{-- Admin: manage users --}}
        <april:sidebar-menu>
            <april:sidebar-group-label>Administration</april:sidebar-group-label>
            <april:sidebar-menu-item>
                <april:sidebar-menu-button-link href="{{ route('admin.users') }}" :active="request()->routeIs('admin.users')">
                    <x-lucide-users />
                    <span>Manage Users</span>
                </april:sidebar-menu-button-link>
            </april:sidebar-menu-item>
        </april:sidebar-menu>
        @endif

        {{-- Catalog is readable by everyone signed in --}}
        <april:sidebar-menu>
            <april:sidebar-group-label>Catalog</april:sidebar-group-label>
            <april:sidebar-menu-item>
                <april:sidebar-menu-button-link href="{{ route('foods.index') }}" :active="request()->routeIs('foods.*')">
                    <x-lucide-apple />
                    <span>{{ Auth::user()->isProfessional() && ! Auth::user()->isAdmin() ? 'My Products' : 'Browse Products' }}</span>
                </april:sidebar-menu-button-link>
            </april:sidebar-menu-item>
        </april:sidebar-menu>

        {{-- Meals are logged by consumers --}}
        @if(Auth::user()->role === 'consumer')
            <april:sidebar-menu>
                <april:sidebar-group-label>Nutrition</april:sidebar-group-label>
                <april:sidebar-menu-item>
                    <april:sidebar-menu-button-link href="{{ route('meals.index') }}" :active="request()->routeIs('meals.*')">
                        <x-lucide-utensils />
                        <span>My Meals</span>
                    </april:sidebar-menu-button-link>
                </april:sidebar-menu-item>
            </april:sidebar-menu>
        @endif
    </x-slot:content>

    <x-slot:footer>
        <april:sidebar-menu>
            <april:sidebar-menu-item>
                <april:sidebar-menu-button-link href="{{ route('profile.edit') }}" :active="request()->routeIs('profile.edit')">
                    <x-lucide-circle-user-round />
                    <span>Profile</span>
                </april:sidebar-menu-button-link>
            </april:sidebar-menu-item>
            <april:sidebar-menu-item>
                <april:sidebar-menu-button-link href="{{ url('/') }}" :active="false">
                    <x-lucide-globe />
                    <span>View site</span>
                </april:sidebar-menu-button-link>
            </april:sidebar-menu-item>
        </april:sidebar-menu>

        @auth
            <div class="flex items-center gap-2.5 rounded-lg border border-sidebar-border bg-background/50 px-2.5 py-2">
                <april:avatar size="sm">
                    <x-slot:fallback class="bg-primary text-xs font-bold text-primary-foreground">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</x-slot:fallback>
                </april:avatar>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold">{{ Auth::user()->name }}</p>
                    <p class="truncate text-xs text-muted-foreground">{{ Auth::user()->email }}</p>
                    <p class="truncate text-xs font-bold text-primary mt-1 uppercase">{{ Auth::user()->role }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <april:button type="submit" variant="ghost" size="icon" title="Log out">
                        <x-lucide-log-out class="size-4" />
                    </april:button>
                </form>
            </div>
        @endauth
        <p class="px-2 pb-1 pt-2 text-xs text-sidebar-foreground/60">NutriTrace v0.1 · boilerplate</p>
    </x-slot:footer>
</april:sidebar>
