@extends('layouts.back')

@section('title', 'Dashboard')

@section('content')
    <april:breadcrumb>
        <x-slot:list>
            <april:breadcrumb-item>
                <april:breadcrumb-link href="{{ url('/admin') }}">Admin</april:breadcrumb-link>
            </april:breadcrumb-item>
            <april:breadcrumb-separator />
            <april:breadcrumb-item>
                <april:breadcrumb-page>Dashboard</april:breadcrumb-page>
            </april:breadcrumb-item>
        </x-slot:list>
    </april:breadcrumb>

    {{-- Welcome banner with role --}}
    <div class="rounded-xl border border-border bg-card px-6 py-4 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold">Welcome back, {{ Auth::user()->name }} 👋</h2>
            <p class="text-sm text-muted-foreground mt-0.5">
                @if(Auth::user()->role === 'producer') You are logged in as a <span class="font-semibold text-primary">Producer</span>. Manage your food catalog below.
                @elseif(Auth::user()->role === 'processor') You are logged in as a <span class="font-semibold text-local">Processor</span>. Track transformation steps below.
                @elseif(Auth::user()->role === 'distributor') You are logged in as a <span class="font-semibold text-fairtrade">Distributor</span>. Monitor distribution data below.
                @elseif(Auth::user()->role === 'consumer') You are logged in as a <span class="font-semibold text-bio">Consumer</span>. Track your meals and nutritional intake below.
                @else You are logged in as an <span class="font-semibold text-destructive">Administrator</span>. Full access enabled.
                @endif
            </p>
        </div>
        <span class="px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wider
            @if(Auth::user()->role === 'producer') bg-primary/10 text-primary
            @elseif(Auth::user()->role === 'processor') bg-local/15 text-local
            @elseif(Auth::user()->role === 'distributor') bg-fairtrade/15 text-fairtrade
            @elseif(Auth::user()->role === 'consumer') bg-bio/15 text-bio
            @else bg-destructive/10 text-destructive @endif">
            {{ Auth::user()->role }}
        </span>
    </div>

    {{-- Stats: different per role --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mt-2">
        @if(Auth::user()->role === 'consumer')
            @foreach ([
                ['icon' => 'utensils', 'tint' => 'bg-local/15 text-local', 'label' => 'Meals logged', 'value' => $mealCount],
                ['icon' => 'flame', 'tint' => 'bg-footprint-medium/15 text-footprint-medium', 'label' => 'Avg. energy', 'value' => $avgCalories . ' kcal'],
                ['icon' => 'apple', 'tint' => 'bg-primary/10 text-primary', 'label' => 'Products in catalog', 'value' => $foodCount],
                ['icon' => 'shield-check', 'tint' => 'bg-bio/15 text-bio', 'label' => 'Certifications', 'value' => 3],
            ] as $stat)
                <april:card>
                    <x-slot:content>
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-muted-foreground">{{ $stat['label'] }}</p>
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $stat['tint'] }}">
                                <x-dynamic-component :component="'lucide-' . $stat['icon']" class="size-4.5" />
                            </span>
                        </div>
                        <p class="mt-2 text-3xl font-extrabold tracking-tight tabular-nums">{{ $stat['value'] }}</p>
                    </x-slot:content>
                </april:card>
            @endforeach
        @else
            @foreach ([
                ['icon' => 'apple', 'tint' => 'bg-primary/10 text-primary', 'label' => 'Products tracked', 'value' => $foodCount],
                ['icon' => 'utensils', 'tint' => 'bg-local/15 text-local', 'label' => 'Meals logged', 'value' => $mealCount],
                ['icon' => 'flame', 'tint' => 'bg-footprint-medium/15 text-footprint-medium', 'label' => 'Avg. energy', 'value' => $avgCalories . ' kcal'],
                ['icon' => 'tags', 'tint' => 'bg-fairtrade/15 text-fairtrade', 'label' => 'Categories', 'value' => $topCategories->count()],
            ] as $stat)
                <april:card>
                    <x-slot:content>
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-muted-foreground">{{ $stat['label'] }}</p>
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $stat['tint'] }}">
                                <x-dynamic-component :component="'lucide-' . $stat['icon']" class="size-4.5" />
                            </span>
                        </div>
                        <p class="mt-2 text-3xl font-extrabold tracking-tight tabular-nums">{{ $stat['value'] }}</p>
                    </x-slot:content>
                </april:card>
            @endforeach
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">

        {{-- Main card: depends on role --}}
        <april:card>
            @if(Auth::user()->role === 'consumer')
                <x-slot:title>Browse the catalog</x-slot:title>
                <x-slot:description>Discover products and their environmental footprint.</x-slot:description>
            @elseif(Auth::user()->role === 'producer')
                <x-slot:title>My food products</x-slot:title>
                <x-slot:description>Products you have added to the NutriTrace catalog.</x-slot:description>
            @elseif(Auth::user()->role === 'processor')
                <x-slot:title>Products in processing</x-slot:title>
                <x-slot:description>Track transformation steps for each product.</x-slot:description>
            @elseif(Auth::user()->role === 'distributor')
                <x-slot:title>Distribution catalog</x-slot:title>
                <x-slot:description>Products in your distribution chain.</x-slot:description>
            @else
                <x-slot:title>All products (Admin)</x-slot:title>
                <x-slot:description>Full catalog overview.</x-slot:description>
            @endif
            <x-slot:content>
                @if($latestFoods->isNotEmpty())
                    <april:data-table
                        searchable
                        :data="$latestFoods->map(fn ($food) => ['name' => $food->name, 'category' => ucfirst($food->category->name ?? 'other'), 'calories' => $food->calories . ' kcal', 'protein' => $food->protein . ' g'])->values()"
                        :columns="[['key' => 'name', 'label' => 'Product', 'sortable' => true], ['key' => 'category', 'label' => 'Category', 'sortable' => true], ['key' => 'calories', 'label' => 'Energy', 'sortable' => true], ['key' => 'protein', 'label' => 'Protein']]"
                    />
                @else
                    <p class="flex items-center gap-2 text-sm text-muted-foreground">
                        <x-lucide-info class="size-4" />
                        No products yet.
                    </p>
                @endif
            </x-slot:content>
            @if(in_array(Auth::user()->role, ['producer', 'processor', 'distributor']))
                <x-slot:footer>
                    <a href="{{ route('foods.index') }}" class="text-sm font-medium text-primary hover:underline">
                        Manage all products →
                    </a>
                </x-slot:footer>
            @endif
        </april:card>

        <div class="space-y-6">
            {{-- Consumer: quick info card --}}
            @if(Auth::user()->role === 'consumer')
                <april:card>
                    <x-slot:title>Your nutrition goals</x-slot:title>
                    <x-slot:description>Track your daily intake.</x-slot:description>
                    <x-slot:content>
                        <p class="text-sm text-muted-foreground">No meals logged yet. Start by browsing the catalog and logging a meal.</p>
                    </x-slot:content>
                </april:card>
            @else
                {{-- Pro roles: recent meals logged by consumers --}}
                <april:card>
                    <x-slot:title>Recent meals</x-slot:title>
                    <x-slot:description>What consumers are logging.</x-slot:description>
                    <x-slot:content>
                        @if($latestMeals->isNotEmpty())
                            <ul class="space-y-3">
                                @foreach($latestMeals as $meal)
                                    <li class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold">{{ $meal->name }}</p>
                                            <p class="text-xs text-muted-foreground">{{ $meal->consumed_on?->format('M d, Y') ?? 'Undated' }}</p>
                                        </div>
                                        <april:badge variant="secondary" class="capitalize">{{ $meal->type }}</april:badge>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-muted-foreground">No meals logged yet.</p>
                        @endif
                    </x-slot:content>
                </april:card>
            @endif

            {{-- Quick actions card --}}
            <april:card>
                <x-slot:title>Quick actions</x-slot:title>
                <x-slot:description>
                    @if(Auth::user()->role === 'consumer') Things you can do as a consumer.
                    @else Things you can do as a {{ Auth::user()->role }}.
                    @endif
                </x-slot:description>
                <x-slot:content>
                    <ul class="space-y-2.5 text-sm">
                        @if(Auth::user()->role === 'consumer')
                            <li class="flex items-center gap-2 text-muted-foreground">
                                <x-lucide-search class="size-4 text-bio" />
                                Browse the food catalog for certified products.
                            </li>
                            <li class="flex items-center gap-2 text-muted-foreground">
                                <x-lucide-utensils class="size-4 text-local" />
                                Log your meals to track your carbon footprint.
                            </li>
                            <li class="flex items-center gap-2 text-muted-foreground">
                                <x-lucide-shield-check class="size-4 text-fairtrade" />
                                Check certifications to fight greenwashing.
                            </li>
                        @elseif(Auth::user()->role === 'producer')
                            <li class="flex items-center gap-2 text-muted-foreground">
                                <x-lucide-plus-circle class="size-4 text-primary" />
                                <a href="{{ route('foods.create') }}" class="text-primary hover:underline">Add a new product to the catalog.</a>
                            </li>
                            <li class="flex items-center gap-2 text-muted-foreground">
                                <x-lucide-list class="size-4 text-primary" />
                                <a href="{{ route('foods.index') }}" class="text-primary hover:underline">View and manage your products.</a>
                            </li>
                        @elseif(Auth::user()->role === 'processor')
                            <li class="flex items-center gap-2 text-muted-foreground">
                                <x-lucide-factory class="size-4 text-local" />
                                Log transformation steps for raw materials.
                            </li>
                            <li class="flex items-center gap-2 text-muted-foreground">
                                <x-lucide-list class="size-4 text-local" />
                                <a href="{{ route('foods.index') }}" class="text-local hover:underline">View processed products.</a>
                            </li>
                        @elseif(Auth::user()->role === 'distributor')
                            <li class="flex items-center gap-2 text-muted-foreground">
                                <x-lucide-truck class="size-4 text-fairtrade" />
                                Track product distribution routes.
                            </li>
                            <li class="flex items-center gap-2 text-muted-foreground">
                                <x-lucide-list class="size-4 text-fairtrade" />
                                <a href="{{ route('foods.index') }}" class="text-fairtrade hover:underline">View distributed products.</a>
                            </li>
                        @else
                            <li class="flex items-center gap-2 text-muted-foreground">
                                <x-lucide-shield class="size-4 text-destructive" />
                                Full admin access — manage all entities.
                            </li>
                        @endif
                    </ul>
                </x-slot:content>
            </april:card>
        </div>
    </div>
@endsection
