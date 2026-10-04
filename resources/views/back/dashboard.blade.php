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

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
        {{-- Latest foods: searchable April data table --}}
        <april:card>
            <x-slot:title>Latest products</x-slot:title>
            <x-slot:description>Searchable preview — full management arrives with the Food CRUD.</x-slot:description>
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
                        No products yet — run the seeders to populate the catalog.
                    </p>
                @endif
            </x-slot:content>
        </april:card>

        <div class="space-y-6">
            {{-- Recent meals --}}
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

        </div>
    </div>
@endsection
