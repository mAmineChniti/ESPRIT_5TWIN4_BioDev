{{--
    Shared dashboard body for the supply chain roles and the admin.

    Rendered through one thin wrapper per route (back.admin.dashboard,
    back.producer.dashboard, back.processor.dashboard,
    back.distributor.dashboard) so every dashboard URL maps to its own view
    file. A consumer never sees this partial: /consumer/dashboard is served by
    ConsumerDashboardController, which renders back.consumer.dashboard.
--}}
    @php($user = auth()->user())

    <april:breadcrumb>
        <x-slot:list>
            {{-- This dashboard is shared by every role, so the Admin crumb is
                 only rendered where /admin actually resolves. --}}
            @if($user->isAdmin())
                <april:breadcrumb-item>
                    <april:breadcrumb-link href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</april:breadcrumb-link>
                </april:breadcrumb-item>
                <april:breadcrumb-separator />
            @endif
            <april:breadcrumb-item>
                <april:breadcrumb-page>{{ __('Dashboard') }}</april:breadcrumb-page>
            </april:breadcrumb-item>
        </x-slot:list>
    </april:breadcrumb>

    {{-- Welcome banner with role --}}
    <div class="flex items-center justify-between rounded-xl border border-border bg-card px-6 py-4">
        <div>
            <h2 class="text-lg font-bold">{{ __('Welcome back, :name', ['name' => $user->name]) }}</h2>
            <p class="mt-0.5 text-sm text-muted-foreground">
                @switch($user->role)
                    @case('producer')
                        {!! __('You are logged in as a :role. Manage your food catalog below.', ['role' => '<span class="font-semibold text-primary">'.e(__('Producer')).'</span>']) !!}
                        @break
                    @case('processor')
                        {!! __('You are logged in as a :role. Track transformation steps below.', ['role' => '<span class="font-semibold text-primary">'.e(__('Processor')).'</span>']) !!}
                        @break
                    @case('distributor')
                        {!! __('You are logged in as a :role. Monitor distribution data below.', ['role' => '<span class="font-semibold text-primary">'.e(__('Distributor')).'</span>']) !!}
                        @break
                    @default
                        {!! __('You are logged in as an :role. Full access enabled.', ['role' => '<span class="font-semibold text-destructive">'.e(__('Administrator')).'</span>']) !!}
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
            ['icon' => 'apple', 'label' => $user->isAdmin() ? __('Products tracked') : __('My products'), 'value' => $user->isAdmin() ? $foodCount : $myFoodCount],
            ['icon' => 'utensils', 'label' => __('Meals logged'), 'value' => $mealCount],
            ['icon' => 'flame', 'label' => __('Avg. energy'), 'value' => $avgCalories.' kcal'],
            ['icon' => 'tags', 'label' => __('Categories'), 'value' => $topCategories->count()],
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
                    <x-slot:title>{{ __('My food products') }}</x-slot:title>
                    <x-slot:description>{{ __('Products you have added to the NutriTrace catalog.') }}</x-slot:description>
                    @break
                @case('processor')
                    <x-slot:title>{{ __('Products in processing') }}</x-slot:title>
                    <x-slot:description>{{ __('Track transformation steps for each product.') }}</x-slot:description>
                    @break
                @case('distributor')
                    <x-slot:title>{{ __('Distribution catalog') }}</x-slot:title>
                    <x-slot:description>{{ __('Products in your distribution chain.') }}</x-slot:description>
                    @break
                @default
                    <x-slot:title>{{ __('All products (Admin)') }}</x-slot:title>
                    <x-slot:description>{{ __('Full catalog overview.') }}</x-slot:description>
            @endswitch

            <x-slot:content>
                @if($latestFoods->isNotEmpty())
                    <april:data-table
                        searchable
                        :data="$latestFoods->map(fn ($food) => ['name' => $food->name, 'category' => $food->category?->name ?? __('Uncategorised'), 'calories' => $food->calories.' kcal', 'protein' => $food->protein.' g'])->values()"
                        :columns="[['key' => 'name', 'label' => __('Product'), 'sortable' => true], ['key' => 'category', 'label' => __('Category'), 'sortable' => true], ['key' => 'calories', 'label' => __('Energy'), 'sortable' => true], ['key' => 'protein', 'label' => __('Protein')]]"
                    />
                @else
                    <p class="flex items-center gap-2 text-sm text-muted-foreground">
                        <x-lucide-info class="size-4" />
                        {{ __('No products yet.') }}
                    </p>
                @endif
            </x-slot:content>

            @if($user->isProfessional())
                <x-slot:footer>
                    <april:button-link href="{{ route('foods.index') }}" variant="link" size="sm">
                        {{ __('Manage all products') }}
                        <x-lucide-arrow-right class="ms-1 size-4" />
                    </april:button-link>
                </x-slot:footer>
            @endif
        </april:card>

        <div class="space-y-6">
            {{-- What is moving through the chain --}}
            <april:card>
                <x-slot:title>{{ __('Supply chain') }}</x-slot:title>
                <x-slot:description>{{ __('Where products currently sit.') }}</x-slot:description>
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
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ __('Latest movements') }}</p>
                        <ul class="space-y-2">
                            @foreach($recentTransitions as $transition)
                                <li class="flex items-center justify-between gap-3">
                                    <a href="{{ route('foods.show', $transition->food_id) }}" class="truncate text-sm font-medium hover:underline">
                                        {{ $transition->food?->name ?? __('Removed product') }}
                                    </a>
                                    <span class="shrink-0 text-xs text-muted-foreground">
                                        {{ $transition->to_stage?->label() }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-muted-foreground">{{ __('No supply chain steps recorded yet.') }}</p>
                    @endif
                </x-slot:content>
            </april:card>

            {{-- Quick actions --}}
            <april:card>
                <x-slot:title>{{ __('Quick actions') }}</x-slot:title>
                <x-slot:description>{{ __('Things you can do as a :role.', ['role' => $user->role]) }}</x-slot:description>
                <x-slot:content>
                    <ul class="space-y-2.5 text-sm">
                        @switch($user->role)
                            @case('producer')
                                <li class="flex items-center gap-2 text-muted-foreground">
                                    <x-lucide-plus-circle class="size-4 text-primary" />
                                    <a href="{{ route('foods.create') }}" class="text-primary hover:underline">{{ __('Add a new product to the catalog.') }}</a>
                                </li>
                                <li class="flex items-center gap-2 text-muted-foreground">
                                    <x-lucide-list class="size-4 text-primary" />
                                    <a href="{{ route('foods.index') }}" class="text-primary hover:underline">{{ __('View and manage your products.') }}</a>
                                </li>
                                @break
                            @case('processor')
                                <li class="flex items-center gap-2 text-muted-foreground">
                                    <x-lucide-factory class="size-4 text-primary" />
                                    <a href="{{ route('processor.journeys.index') }}" class="text-primary hover:underline">{{ __('Log transformation steps for raw materials.') }}</a>
                                </li>
                                <li class="flex items-center gap-2 text-muted-foreground">
                                    <x-lucide-list class="size-4 text-primary" />
                                    <a href="{{ route('foods.index') }}" class="text-primary hover:underline">{{ __('View processed products.') }}</a>
                                </li>
                                @break
                            @case('distributor')
                                <li class="flex items-center gap-2 text-muted-foreground">
                                    <x-lucide-truck class="size-4 text-primary" />
                                    {{ __('Track product distribution routes.') }}
                                </li>
                                <li class="flex items-center gap-2 text-muted-foreground">
                                    <x-lucide-list class="size-4 text-primary" />
                                    <a href="{{ route('foods.index') }}" class="text-primary hover:underline">{{ __('View distributed products.') }}</a>
                                </li>
                                @break
                            @default
                                <li class="flex items-center gap-2 text-muted-foreground">
                                    <x-lucide-shield class="size-4 text-destructive" />
                                    {{ __('Full admin access — manage all entities.') }}
                                </li>
                        @endswitch
                    </ul>
                </x-slot:content>
            </april:card>
        </div>
    </div>