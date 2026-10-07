@php
    use App\Models\Farm;

    $role = auth()->user()->role;
    $isAdmin = auth()->user()->isAdmin();
    $showsOwnProducts = auth()->user()->isProfessional();
    $pendingRequestsCount = $isAdmin ? Farm::query()->pending()->count() : 0;
@endphp

<april:sidebar>
    <x-slot:header>
        <div class="px-2 py-2">
            <x-logo />
        </div>
    </x-slot:header>

    <x-slot:content>
        <april:sidebar-menu>
            <april:sidebar-group-label>Overview</april:sidebar-group-label>
            <april:sidebar-menu-item>
                <april:sidebar-menu-button-link
                    href="{{ auth()->user()->dashboardUrl() }}"
                    :active="request()->routeIs('dashboard') || request()->routeIs('admin.dashboard') || request()->routeIs('producer.dashboard') || request()->routeIs('processor.dashboard') || request()->routeIs('distributor.dashboard') || request()->routeIs('consumer.dashboard')">
                    <x-lucide-layout-dashboard />
                    <span>Dashboard</span>
                </april:sidebar-menu-button-link>
            </april:sidebar-menu-item>
        </april:sidebar-menu>

        @if($isAdmin)
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
                    <span>{{ $showsOwnProducts ? 'My Products' : 'Browse Products' }}</span>
                </april:sidebar-menu-button-link>
            </april:sidebar-menu-item>
        </april:sidebar-menu>

        @if(in_array($role, ['admin', 'producer'], true))
            <april:sidebar-menu>
                <april:sidebar-group-label>Gestion Agricole</april:sidebar-group-label>
                <april:sidebar-menu-item>
                    <april:sidebar-menu-button-link href="{{ route('back.regions.index') }}" :active="request()->routeIs('back.regions.*')">
                        <x-lucide-map-pin />
                        <span>Régions Agricoles</span>
                    </april:sidebar-menu-button-link>
                </april:sidebar-menu-item>
                <april:sidebar-menu-item>
                    <april:sidebar-menu-button-link href="{{ route('back.farms.index') }}" :active="request()->routeIs('back.farms.*') && ! request()->routeIs('back.farms.requests')">
                        <x-lucide-tractor />
                        <span>Fermes & Exploitations</span>
                    </april:sidebar-menu-button-link>
                </april:sidebar-menu-item>
                @if($isAdmin)
                    <april:sidebar-menu-item>
                        <april:sidebar-menu-button-link href="{{ route('back.farms.requests') }}" :active="request()->routeIs('back.farms.requests')">
                            <x-lucide-clipboard-check />
                            <span class="flex flex-1 items-center justify-between gap-2">
                                <span>Demandes de Fermes</span>
                                @if($pendingRequestsCount > 0)
                                    <april:badge variant="secondary" class="rounded-full px-2 py-0.5 text-xs font-bold leading-none">
                                        {{ $pendingRequestsCount }}
                                    </april:badge>
                                @endif
                            </span>
                        </april:sidebar-menu-button-link>
                    </april:sidebar-menu-item>
                @endif
            </april:sidebar-menu>
        @endif

        {{-- Meals are logged by consumers --}}
        @if($role === 'consumer')
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
                <april:sidebar-menu-button-link href="{{ route('front.home') }}" :active="false">
                    <x-lucide-globe />
                    <span>View site</span>
                </april:sidebar-menu-button-link>
            </april:sidebar-menu-item>
        </april:sidebar-menu>

        <div class="flex items-center gap-2.5 rounded-lg border border-sidebar-border bg-background/50 px-2.5 py-2">
            <april:avatar size="sm">
                <x-slot:fallback class="bg-primary text-xs font-bold text-primary-foreground">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</x-slot:fallback>
            </april:avatar>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ auth()->user()->email }}</p>
                <p class="mt-1 truncate text-xs font-bold uppercase text-primary">{{ $role }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <april:button type="submit" variant="ghost" size="icon" title="Log out" aria-label="Log out">
                    <x-lucide-log-out class="size-4" />
                </april:button>
            </form>
        </div>

        <p class="px-2 pb-1 pt-2 text-xs text-sidebar-foreground/60">NutriTrace v0.1 · boilerplate</p>
    </x-slot:footer>
</april:sidebar>