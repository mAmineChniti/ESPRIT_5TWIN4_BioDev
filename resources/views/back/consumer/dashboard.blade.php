@extends('layouts.back')

@section('title', __('My Consumer Space'))

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold">{{ __('My Consumer Space') }}</h1>
        <p class="text-sm text-muted-foreground">{{ __('What you have eaten, rated and flagged.') }}</p>
    </div>
    <div class="flex gap-2">
        <april:button-link href="{{ route('products.index') }}" variant="outline">{{ __('Browse products') }}</april:button-link>
        <april:button-link href="{{ route('meals.create') }}">{{ __('Log a meal') }}</april:button-link>
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach([
        ['icon' => 'utensils', 'label' => __('Meals logged'), 'value' => $stats['mealsLogged']],
        ['icon' => 'star', 'label' => __('Reviews written'), 'value' => $stats['reviewsWritten']],
        ['icon' => 'flag', 'label' => __('Reports filed'), 'value' => $stats['reportsFiled']],
        ['icon' => 'shield-alert', 'label' => __('Of which upheld'), 'value' => $stats['reportsUpheld']],
    ] as $stat)
        <april:card>
            <x-slot:content>
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-muted-foreground">{{ $stat['label'] }}</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <x-dynamic-component :component="'lucide-'.$stat['icon']" class="size-4" />
                    </span>
                </div>
                <p class="mt-2 text-3xl font-extrabold tabular-nums">{{ $stat['value'] }}</p>
            </x-slot:content>
        </april:card>
    @endforeach
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <april:card>
        <x-slot:title>{{ __('Energy per day') }}</x-slot:title>
        <x-slot:description>{{ trans_choice('{0} Estimated from the products you logged, over the last :count days.|{1} Estimated from the products you logged, over the last :count day.|[2,*] Estimated from the products you logged, over the last :count days.', $stats['chartWindowDays']) }}</x-slot:description>
        <x-slot:content>
            @if($energy->isNotEmpty())
                <div class="h-56">
                    <canvas id="chart-energy"></canvas>
                </div>
            @else
                <p class="text-sm text-muted-foreground">{{ __('Log a meal to start a trend.') }}</p>
            @endif
        </x-slot:content>
    </april:card>

    <april:card>
        <x-slot:title>{{ __('Eco grades of what you eat') }}</x-slot:title>
        <x-slot:description>{{ __(':share% of the products you ate are certified.', ['share' => $certifiedShare]) }}</x-slot:description>
        <x-slot:content>
            @if($grades->isNotEmpty())
                <div class="h-56">
                    <canvas id="chart-grades"></canvas>
                </div>
            @else
                <p class="text-sm text-muted-foreground">{{ __('No graded products in your meals yet.') }}</p>
            @endif
        </x-slot:content>
    </april:card>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <april:card>
        <x-slot:title>{{ __('My reviews') }}</x-slot:title>
        <x-slot:content>
            @forelse($myReviews as $review)
                <div class="flex items-center justify-between gap-3 border-b border-border py-3 last:border-0">
                    <div class="min-w-0">
                        <a href="{{ route('products.show', $review->food_id) }}"
                           class="truncate text-sm font-semibold hover:text-primary hover:underline">
                            {{ $review->food?->name ?? __('Removed product') }}
                        </a>
                        @if($review->body)
                            <p class="mt-0.5 line-clamp-2 text-xs text-muted-foreground">{{ $review->body }}</p>
                        @endif
                    </div>
                    <span class="shrink-0 text-sm font-semibold text-secondary-foreground">{{ $review->rating }}/5</span>
                </div>
            @empty
                <p class="py-4 text-sm text-muted-foreground">
                    {{ __('You have not reviewed anything yet.') }}
                    <a href="{{ route('products.index') }}" class="text-primary underline">{{ __('Find a product') }}</a>.
                </p>
            @endforelse
        </x-slot:content>
    </april:card>

    <april:card>
        <x-slot:title>{{ __('My greenwashing reports') }}</x-slot:title>
        <x-slot:content>
            @forelse($myReports as $report)
                <div class="border-b border-border py-3 last:border-0">
                    <div class="flex items-center justify-between gap-3">
                        <a href="{{ route('products.show', $report->food_id) }}"
                           class="truncate text-sm font-semibold hover:text-primary hover:underline">
                            {{ $report->food?->name ?? __('Removed product') }}
                        </a>
                        @php
                            [$variant, $extra] = match ($report->status->value) {
                                'upheld' => ['destructive', ''],
                                'dismissed' => ['none', 'bg-muted text-muted-foreground'],
                                default => ['secondary', ''],
                            };
                        @endphp
                        <april:badge variant="{{ $variant }}" class="shrink-0 {{ $extra }}">
                            {{ $report->status->label() }}
                        </april:badge>
                    </div>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{ $report->reason->label() }} · {{ $report->created_at->diffForHumans() }}
                    </p>
                </div>
            @empty
                <p class="py-4 text-sm text-muted-foreground">
                    {{ __('You have not flagged anything yet.') }}
                </p>
            @endforelse
        </x-slot:content>
    </april:card>
</div>

<april:card class="mt-6">
    <x-slot:title>{{ __('Recent meals') }}</x-slot:title>
    <x-slot:content>
        @forelse($meals->take(6) as $meal)
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border py-3 last:border-0">
                <div>
                    <p class="text-sm font-semibold">{{ $meal->name }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ \App\Models\Meal::typeLabel($meal->type) }} · {{ $meal->consumed_on?->translatedFormat('j M Y') }}
                        · {{ $meal->totalCalories() }} kcal
                    </p>
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ $meal->foods->pluck('name')->join(', ') ?: __('No products recorded') }}
                </p>
            </div>
        @empty
            <p class="py-4 text-sm text-muted-foreground">
                {{ __('Nothing logged yet.') }}
                <a href="{{ route('meals.create') }}" class="text-primary underline">{{ __('Log your first meal') }}</a>.
            </p>
        @endforelse
    </x-slot:content>
</april:card>

<script type="application/json" id="consumer-dashboard-data">{!! json_encode(['energy' => $energy, 'grades' => $grades, 'labels' => ['calories' => __('Calories (kcal)'), 'grade' => __('Grade')]]) !!}</script>
@endsection

@push('scripts')
    @vite(['resources/js/charts.js'])
@endpush