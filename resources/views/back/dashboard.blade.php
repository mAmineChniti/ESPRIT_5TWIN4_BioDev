@extends('layouts.back')

@section('title', 'Dashboard')

@section('content')
    <april:breadcrumb>
        <x-slot:list>
            {{-- This dashboard is shared by every role, so the Admin crumb is
                 only rendered where /admin actually resolves. --}}
            @if(Auth::user()->isAdmin())
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
    <div class="rounded-xl border border-border bg-card px-6 py-4 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold">Welcome back, {{ Auth::user()->name }} 👋</h2>
            <p class="text-sm text-muted-foreground mt-0.5">
                @if(Auth::user()->role === 'producer') You are logged in as a <span class="font-semibold text-primary">Producer</span>. Manage your food catalog below.
                @elseif(Auth::user()->role === 'processor') You are logged in as a <span class="font-semibold text-primary">Processor</span>. Track transformation steps below.
                @elseif(Auth::user()->role === 'distributor') You are logged in as a <span class="font-semibold text-primary">Distributor</span>. Monitor distribution data below.
                @else You are logged in as an <span class="font-semibold text-destructive">Administrator</span>. Full access enabled.
                @endif
            </p>
        </div>
        <april:badge variant="none"
                     class="uppercase tracking-wider {{ Auth::user()->isAdmin() ? 'bg-destructive/10 text-destructive' : 'bg-primary/10 text-primary' }}">
            {{ Auth::user()->role }}
        </april:badge>
    </div>

    {{-- Stats: different per role --}}
    {{-- This view is only routed for admin, producer, processor and distributor;
         consumers land on their own dashboard, so there is no consumer branch
         here and $myMeals is never read. --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mt-2">
        @foreach ([
                ['icon' => 'apple', 'tint' => 'bg-primary/10 text-primary', 'label' => Auth::user()->role === 'admin' ? 'Products tracked' : 'My products', 'value' => Auth::user()->role === 'admin' ? $foodCount : $myFoodCount],
                ['icon' => 'utensils', 'tint' => 'bg-primary/10 text-primary', 'label' => 'Meals logged', 'value' => $mealCount],
                ['icon' => 'flame', 'tint' => 'bg-secondary/50 text-secondary-foreground', 'label' => 'Avg. energy', 'value' => $avgCalories . ' kcal'],
                ['icon' => 'tags', 'tint' => 'bg-primary/10 text-primary', 'label' => 'Categories', 'value' => $topCategories->count()],
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
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">

        {{-- Main card: depends on role --}}
        <april:card>
            @if(Auth::user()->role === 'producer')
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
            {{-- Supply chain professionals: what is moving through the chain --}}
            <april:card>
                <x-slot:title>Supply chain</x-slot:title>
                <x-slot:description>Where products currently sit.</x-slot:description>
                <x-slot:content>
                    @if($pendingStage->isNotEmpty())
                            <ul class="space-y-2 mb-4">
                                @foreach(\App\Enums\Stage::cases() as $stage)
                                    <li class="flex items-center justify-between gap-3">
                                        <span class="text-sm">{{ $stage->label() }}</span>
                                        <april:badge variant="secondary">{{ $pendingStage->get($stage->value, 0) }}</april:badge>
                                    </li>
                                @endforeach
                            </ul>
                    @endif

                    @if($recentTransitions->isNotEmpty())
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground mb-2">Latest movements</p>
                            <ul class="space-y-2">
                                @foreach($recentTransitions as $transition)
                                    <li class="flex items-center justify-between gap-3">
                                        <a href="{{ route('foods.show', $transition->food_id) }}" class="text-sm font-medium hover:underline truncate">
                                            {{ $transition->food?->name ?? 'Removed product' }}
                                        </a>
                                        <span class="text-xs text-muted-foreground shrink-0">
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

            {{-- Quick actions card --}}
            <april:card>
                <x-slot:title>Quick actions</x-slot:title>
                <x-slot:description>Things you can do as a {{ Auth::user()->role }}.</x-slot:description>
                <x-slot:content>
                    <ul class="space-y-2.5 text-sm">
                        @if(Auth::user()->role === 'producer')
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
                                <x-lucide-factory class="size-4 text-primary" />
                                Log transformation steps for raw materials.
                            </li>
                            <li class="flex items-center gap-2 text-muted-foreground">
                                <x-lucide-list class="size-4 text-primary" />
                                <a href="{{ route('foods.index') }}" class="text-primary hover:underline">View processed products.</a>
                            </li>
                        @elseif(Auth::user()->role === 'distributor')
                            <li class="flex items-center gap-2 text-muted-foreground">
                                <x-lucide-truck class="size-4 text-primary" />
                                Track product distribution routes.
                            </li>
                            <li class="flex items-center gap-2 text-muted-foreground">
                                <x-lucide-list class="size-4 text-primary" />
                                <a href="{{ route('foods.index') }}" class="text-primary hover:underline">View distributed products.</a>
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
