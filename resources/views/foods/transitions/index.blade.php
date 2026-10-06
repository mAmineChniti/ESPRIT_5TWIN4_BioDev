@extends('layouts.back')

@section('title', 'Supply Chain')

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('foods.index') }}" class="text-muted-foreground hover:text-foreground">← Back</a>
        <h1 class="text-2xl font-bold">Supply chain — {{ $food->name }}</h1>
    </div>
</div>

<div class="bg-card rounded-lg shadow p-6 max-w-3xl">
    @if(session('success'))
        <div class="mb-4 rounded-md bg-primary/10 px-4 py-3 text-sm text-primary">{{ session('success') }}</div>
    @endif

    <ol class="space-y-0">
        @php $previous = null; @endphp
        @forelse($food->transitions as $transition)
            <li class="relative flex gap-4 pb-6">
                <div class="flex flex-col items-center">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-bold">
                        {{ $transition->to_stage?->value }}
                    </span>
                    @if(! $loop->last)
                        <span class="mt-1 w-px flex-1 bg-muted"></span>
                    @endif
                </div>
                <div class="pb-1">
                    <p class="text-sm font-medium text-foreground">
                        {{ $transition->to_stage?->label() }}
                        @if($transition->from_stage)
                            <span class="font-normal text-muted-foreground">from {{ $transition->from_stage->label() }}</span>
                        @endif
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ $transition->occurred_at->format('Y-m-d H:i') }}
                        @if($transition->actor) — {{ $transition->actor->name }} @endif
                    </p>
                    @if($transition->notes)
                        <p class="mt-1 text-sm text-muted-foreground">{{ $transition->notes }}</p>
                    @endif
                </div>
            </li>
        @empty
            <li class="text-sm text-muted-foreground">No supply chain steps recorded yet.</li>
        @endforelse
    </ol>

    @can('update', $food)
        @php
            $currentStage = $food->transitions->last()?->to_stage;
            $nextStage = collect(\App\Enums\Stage::cases())
                ->first(fn ($stage) => $currentStage === null || array_search($stage->value, \App\Enums\Stage::order(), true) > array_search($currentStage->value, \App\Enums\Stage::order(), true));
        @endphp

        @if($nextStage)
            <form action="{{ route('foods.transitions.store', $food) }}" method="POST" class="mt-6 space-y-3 border-t border-border pt-6">
                @csrf
                <p class="text-sm font-medium text-foreground">Record the next step</p>
                <input type="hidden" name="to_stage" value="{{ $nextStage->value }}">
                <p class="text-sm text-muted-foreground">This product will be marked as <strong>{{ $nextStage->label() }}</strong>.</p>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-foreground">Notes</label>
                    <textarea name="notes" rows="2" class="w-full rounded-md border-input shadow-sm">{{ old('notes') }}</textarea>
                    @error('to_stage') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-2 px-4 rounded-md">
                    Record {{ $nextStage->label() }}
                </button>
            </form>
        @else
            <p class="mt-6 border-t border-border pt-6 text-sm text-muted-foreground">
                This product has completed every supply chain stage.
            </p>
        @endif
    @endcan
</div>
@endsection