@php
    use App\Models\AnalysisDispute;
    use App\Models\Farm;

    $user = auth()->user();
    $role = $user->role;
    $isAdmin = $user->isAdmin();
    $showsOwnProducts = $user->isProfessional();
    $pendingRequestsCount = $isAdmin ? Farm::query()->pending()->count() : 0;
    $pendingDisputesCount = $isAdmin ? AnalysisDispute::query()->pending()->count() : 0;
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
                    href="{{ $user->dashboardUrl() }}"
                    :active="request()->routeIs('dashboard') || request()->routeIs('admin.dashboard') || request()->routeIs('producer.dashboard') || request()->routeIs('processor.dashboard') || request()->routeIs('distributor.dashboard') || request()->routeIs('consumer.dashboard')">
                    <x-lucide-layout-dashboard />
                    <span>Dashboard</span>
                </april:sidebar-menu-button-link>
            </april:sidebar-menu-item>
        </april:sidebar-menu>

        @if($isAdmin)
            {{-- Admin-only destinations --}}
            <april:sidebar-menu>
                <april:sidebar-group-label>Administration</april:sidebar-group-label>
                <april:sidebar-menu-item>
                    <april:sidebar-menu-button-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">
                        <x-lucide-users />
                        <span>Manage Users</span>
                    </april:sidebar-menu-button-link>
                </april:sidebar-menu-item>
                <april:sidebar-menu-item>
                    <april:sidebar-menu-button-link href="{{ route('admin.reports.index') }}" :active="request()->routeIs('admin.reports.*')">
                        <x-lucide-flag />
                        <span>Greenwashing Reports</span>
                    </april:sidebar-menu-button-link>
                </april:sidebar-menu-item>
                <april:sidebar-menu-item>
                    <april:sidebar-menu-button-link href="{{ route('admin.analysis-disputes.index') }}" :active="request()->routeIs('admin.analysis-disputes.*')">
                        <x-lucide-message-square-warning />
                        <span class="flex flex-1 items-center justify-between gap-2">
                            <span>Analysis Reports</span>
                            @if($pendingDisputesCount > 0)
                                <april:badge variant="secondary" class="rounded-full px-2 py-0.5 text-xs font-bold leading-none">
                                    {{ $pendingDisputesCount }}
                                </april:badge>
                            @endif
                        </span>
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

        {{-- Traceability is the processor's own work: the journeys and the steps
             that make each one up. Without this the whole module is URL-only. --}}
        @if($role === 'processor')
            <april:sidebar-menu>
                <april:sidebar-group-label>Traceability</april:sidebar-group-label>
                <april:sidebar-menu-item>
                    <april:sidebar-menu-button-link href="{{ route('processor.journeys.index') }}" :active="request()->routeIs('processor.journeys.*') && ! request()->routeIs('processor.journeys.steps.*')">
                        <x-lucide-route />
                        <span>Journeys</span>
                    </april:sidebar-menu-button-link>
                </april:sidebar-menu-item>
            </april:sidebar-menu>
        @endif

        @if(in_array($role, ['admin', 'producer'], true))
            <april:sidebar-menu>
                <april:sidebar-group-label>Agriculture</april:sidebar-group-label>
                <april:sidebar-menu-item>
                    <april:sidebar-menu-button-link href="{{ route('regions.index') }}" :active="request()->routeIs('regions.*')">
                        <x-lucide-map-pin />
                        <span>Agricultural Regions</span>
                    </april:sidebar-menu-button-link>
                </april:sidebar-menu-item>
                <april:sidebar-menu-item>
                    <april:sidebar-menu-button-link href="{{ route('farms.index') }}" :active="request()->routeIs('farms.*') && ! request()->routeIs('farms.requests')">
                        <x-lucide-tractor />
                        <span>Farms</span>
                    </april:sidebar-menu-button-link>
                </april:sidebar-menu-item>
                @if($isAdmin)
                    <april:sidebar-menu-item>
                        <april:sidebar-menu-button-link href="{{ route('farms.requests') }}" :active="request()->routeIs('farms.requests')">
                            <x-lucide-clipboard-check />
                            <span class="flex flex-1 items-center justify-between gap-2">
                                <span>Farm Requests</span>
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

        {{-- Logistics: distributors and admins --}}
        @if(in_array($role, ['distributor', 'admin'], true))
            <april:sidebar-menu>
                <april:sidebar-group-label>Logistics</april:sidebar-group-label>
                <april:sidebar-menu-item>
                    <april:sidebar-menu-button-link href="{{ route('logistics.warehouses.index') }}" :active="request()->routeIs('logistics.warehouses.*')">
                        <x-lucide-warehouse />
                        <span>Warehouses</span>
                    </april:sidebar-menu-button-link>
                </april:sidebar-menu-item>
                <april:sidebar-menu-item>
                    <april:sidebar-menu-button-link href="{{ route('logistics.shipments.index') }}" :active="request()->routeIs('logistics.shipments.*')">
                        <x-lucide-truck />
                        <span>Shipments</span>
                    </april:sidebar-menu-button-link>
                </april:sidebar-menu-item>
            </april:sidebar-menu>
        @endif
        @if($role === 'consumer')
            <april:sidebar-menu>
                <april:sidebar-group-label>Nutrition</april:sidebar-group-label>
                <april:sidebar-menu-item>
                    <april:sidebar-menu-button-link href="{{ route('consumer.space') }}" :active="request()->routeIs('consumer.space')">
                        <x-lucide-sparkles />
                        <span>Consumer Space</span>
                    </april:sidebar-menu-button-link>
                </april:sidebar-menu-item>
                <april:sidebar-menu-item>
                    <april:sidebar-menu-button-link href="{{ route('consumer.recommendations') }}" :active="request()->routeIs('consumer.recommendations')">
                        <x-lucide-leaf />
                        <span>Recommendations</span>
                    </april:sidebar-menu-button-link>
                </april:sidebar-menu-item>
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
                <april:sidebar-menu-button-link href="{{ route('home') }}" :active="false">
                    <x-lucide-globe />
                    <span>View site</span>
                </april:sidebar-menu-button-link>
            </april:sidebar-menu-item>
        </april:sidebar-menu>

        <div class="flex items-center gap-2.5 rounded-lg border border-sidebar-border bg-background/50 px-2.5 py-2">
            <april:avatar size="sm">
                <x-slot:fallback class="bg-primary text-xs font-bold text-primary-foreground">{{ strtoupper(substr($user->name, 0, 1)) }}</x-slot:fallback>
            </april:avatar>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ $user->email }}</p>
                <p class="mt-1 truncate text-xs font-bold uppercase text-primary">{{ $role }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" novalidate>
                @csrf
                <april:button type="submit" variant="ghost" size="icon" title="Log out" aria-label="Log out">
                    <x-lucide-log-out class="size-4" />
                </april:button>
            </form>
        </div>

        <p class="px-2 pb-1 pt-2 text-xs text-sidebar-foreground/60">NutriTrace v0.1 · boilerplate</p>
    </x-slot:footer>
</april:sidebar>