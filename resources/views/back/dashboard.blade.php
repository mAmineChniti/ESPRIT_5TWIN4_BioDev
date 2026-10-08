{{--
    The dashboard for the supply chain roles and the admin.

    A consumer never sees this view: /consumer/dashboard is served by
    ConsumerDashboardController, which renders back.consumer-dashboard. So this
    template has no consumer branch to read.
--}}
@extends('layouts.back')

@section('title', 'Dashboard')

@section('content')
    @php($user = auth()->user())

    <april:breadcrumb>
        <x-slot:list>
            {{-- This dashboard is shared by every role, so the Admin crumb is
                 only rendered where /admin actually resolves. --}}
            @if($user->isAdmin())
                <april:breadcrumb-item>
                    <april:breadcrumb-link href="{{ route('admin.dashboard') }}">Admin</april:breadcrumb-link>
                </april:breadcrumb-item>
                <april:breadcrumb-separator />
            @endif
            <april:breadcrumb-item>
                <april:breadcrumb-page>Dashboard</april:breadcrumb-page>
            </april:breadcrumb-item>
        </x-slot:list>
    </april:breadcrumb>

    {{-- Welcome banner with role --}}
    <div class="flex items-center justify-between rounded-xl border border-border bg-card px-6 py-4">
        <div>
            <h2 class="text-lg font-bold">Welcome back, {{ $user->name }}</h2>
            <p class="mt-0.5 text-sm text-muted-foreground">
                @switch($user->role)
                    @case('producer')
                        You are logged in as a <span class="font-semibold text-primary">Producer</span>. Manage your food catalog below.
                        @break
                    @case('processor')
                        You are logged in as a <span class="font-semibold text-primary">Processor</span>. Track transformation steps below.
                        @break
                    @case('distributor')
                        You are logged in as a <span class="font-semibold text-primary">Distributor</span>. Monitor distribution data below.
                        @break
                    @default
                        You are logged in as an <span class="font-semibold text-destructive">Administrator</span>. Full access enabled.
                @endswitch
            </p>
        </div>

        <april:badge variant="{{ $user->isAdmin() ? 'none' : 'secondary' }}"
            @class(['bg-destructive/10 text-destructive' => $user->isAdmin()])>
            {{ $user->role }}
        </april:badge>
    </div>

    {{-- Stats --}}
    <div class="mt-2 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['icon' => 'apple', 'label' => $user->isAdmin() ? 'Products tracked' : 'My products', 'value' => $user->isAdmin() ? $foodCount : $myFoodCount],
            ['icon' => 'utensils', 'label' => 'Meals logged', 'value' => $mealCount],
            ['icon' => 'flame', 'label' => 'Avg. energy', 'value' => $avgCalories.' kcal'],
            ['icon' => 'tags', 'label' => 'Categories', 'value' => $topCategories->count()],
        ] as $stat)
            <april:card>
                <x-slot:content>
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-muted-foreground">{{ $stat['label'] }}</p>
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <x-dynamic-component :component="'lucide-'.$stat['icon']" class="size-4" />
                        </span>
                    </div>
                    <p class="mt-2 text-3xl font-extrabold tabular-nums tracking-tight">{{ $stat['value'] }}</p>
                </x-slot:content>
            </april:card>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
        <april:card>
            @switch($user->role)
                @case('producer')
                    <x-slot:title>My food products</x-slot:title>
                    <x-slot:description>Products you have added to the NutriTrace catalog.</x-slot:description>
                    @break
                @case('processor')
                    <x-slot:title>Products in processing</x-slot:title>
                    <x-slot:description>Track transformation steps for each product.</x-slot:description>
                    @break
                @case('distributor')
                    <x-slot:title>Distribution catalog</x-slot:title>
                    <x-slot:description>Products in your distribution chain.</x-slot:description>
                    @break
                @default
                    <x-slot:title>All products (Admin)</x-slot:title>
                    <x-slot:description>Full catalog overview.</x-slot:description>
            @endswitch

            <x-slot:content>
                @if($latestFoods->isNotEmpty())
                    <april:data-table
                        searchable
                        :data="$latestFoods->map(fn ($food) => ['name' => $food->name, 'category' => ucfirst($food->category->name ?? 'other'), 'calories' => $food->calories.' kcal', 'protein' => $food->protein.' g'])->values()"
                        :columns="[['key' => 'name', 'label' => 'Product', 'sortable' => true], ['key' => 'category', 'label' => 'Category', 'sortable' => true], ['key' => 'calories', 'label' => 'Energy', 'sortable' => true], ['key' => 'protein', 'label' => 'Protein']]"
                    />
                @else
                    <p class="flex items-center gap-2 text-sm text-muted-foreground">
                        <x-lucide-info class="size-4" />
                        No products yet.
                    </p>
                @endif
            </x-slot:content>

            @if($user->isProfessional())
                <x-slot:footer>
                    <april:button-link href="{{ route('foods.index') }}" variant="link" size="sm">
                        Manage all products
                        <x-lucide-arrow-right class="ml-1 size-4" />
                    </april:button-link>
                </x-slot:footer>
            @endif
        </april:card>

        <div class="space-y-6">
            {{-- What is moving through the chain --}}
            <april:card>
                <x-slot:title>Supply chain</x-slot:title>
                <x-slot:description>Where products currently sit.</x-slot:description>
                <x-slot:content>
                    @if($pendingStage->isNotEmpty())
                        <ul class="mb-4 space-y-2">
                            @foreach(App\Enums\Stage::cases() as $stage)
                                <li class="flex items-center justify-between gap-3">
                                    <span class="text-sm">{{ $stage->label() }}</span>
                                    <april:badge variant="secondary">{{ $pendingStage->get($stage->value, 0) }}</april:badge>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if($recentTransitions->isNotEmpty())
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Latest movements</p>
                        <ul class="space-y-2">
                            @foreach($recentTransitions as $transition)
                                <li class="flex items-center justify-between gap-3">
                                    <a href="{{ route('foods.show', $transition->food_id) }}" class="truncate text-sm font-medium hover:underline">
                                        {{ $transition->food?->name ?? 'Removed product' }}
                                    </a>
                                    <span class="shrink-0 text-xs text-muted-foreground">
                                        {{ $transition->to_stage?->label() }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-muted-foreground">No supply chain steps recorded yet.</p>
                    @endif
                </x-slot:content>
            </april:card>

            {{-- Quick actions --}}
            <april:card>
                <x-slot:title>Quick actions</x-slot:title>
                <x-slot:description>Things you can do as a {{ $user->role }}.</x-slot:description>
                <x-slot:content>
                    <ul class="space-y-2.5 text-sm">
                        @switch($user->role)
                            @case('producer')
                                <li class="flex items-center gap-2 text-muted-foreground">
                                    <x-lucide-plus-circle class="size-4 text-primary" />
                                    <a href="{{ route('foods.create') }}" class="text-primary hover:underline">Add a new product to the catalog.</a>
                                </li>
                                <li class="flex items-center gap-2 text-muted-foreground">
                                    <x-lucide-list class="size-4 text-primary" />
                                    <a href="{{ route('foods.index') }}" class="text-primary hover:underline">View and manage your products.</a>
                                </li>
                                @break
                            @case('processor')
                                <li class="flex items-center gap-2 text-muted-foreground">
                                    <x-lucide-factory class="size-4 text-primary" />
                                    <a href="{{ route('processor.journeys.index') }}" class="text-primary hover:underline">Log transformation steps for raw materials.</a>
                                </li>
                                <li class="flex items-center gap-2 text-muted-foreground">
                                    <x-lucide-list class="size-4 text-primary" />
                                    <a href="{{ route('foods.index') }}" class="text-primary hover:underline">View processed products.</a>
                                </li>
                                @break
                            @case('distributor')
                                <li class="flex items-center gap-2 text-muted-foreground">
                                    <x-lucide-truck class="size-4 text-primary" />
                                    Track product distribution routes.
                                </li>
                                <li class="flex items-center gap-2 text-muted-foreground">
                                    <x-lucide-list class="size-4 text-primary" />
                                    <a href="{{ route('foods.index') }}" class="text-primary hover:underline">View distributed products.</a>
                                </li>
                                @break
                            @default
                                <li class="flex items-center gap-2 text-muted-foreground">
                                    <x-lucide-shield class="size-4 text-destructive" />
                                    Full admin access — manage all entities.
                                </li>
                        @endswitch
                    </ul>
                </x-slot:content>
            </april:card>
        </div>
    </div>
@endsection