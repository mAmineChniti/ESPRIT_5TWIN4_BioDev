@extends('layouts.back')

@section('title', 'Supply Chain')

@section('content')
<div class="mb-6">
    <april:button-link href="{{ route('foods.show', $food) }}" variant="link" size="sm" class="text-muted-foreground">
        <x-lucide-arrow-left class="size-4" />
        Back to {{ $food->name }}
    </april:button-link>
    <h1 class="text-2xl font-bold">Supply chain — {{ $food->name }}</h1>
</div>

<april:card class="max-w-3xl">
    <x-slot:content>
        @if(session('success'))
            <april:alert class="mb-4" aria-live="polite">
                <x-slot:icon><x-lucide-circle-check class="size-4" /></x-slot:icon>
                <x-slot:description>{{ session('success') }}</x-slot:description>
            </april:alert>
        @endif

        @php
            // Only the role that performs the next stage may sign for it, so the
            // recorded actor always matches the hand-off being claimed.
            $recordedCount = $food->transitions->count();
        @endphp

        @if($recordedCount > 0)
            <april:steps orientation="vertical" :items="collect(\App\Enums\Stage::cases())
                ->map(fn ($stage) => [
                    'label' => $stage->label(),
                    'state' => $food->transitions->contains('to_stage', $stage) ? 'completed' : 'upcoming',
                    'description' => (string) ($food->transitions
                        ->firstWhere('to_stage', $stage)?->occurred_at?->format('j M Y H:i') ?? ''),
                ])->all()" />
        @endif

        <ol class="{{ $recordedCount > 0 ? 'mt-6 space-y-2' : '' }}">
            @forelse($food->transitions as $transition)
                <li class="rounded-lg border border-border p-4">
                    <p class="text-sm font-medium text-foreground">
                        {{ $transition->to_stage?->label() }}
                        @if($transition->from_stage)
                            <span class="font-normal text-muted-foreground">
                                from {{ $transition->from_stage->label() }}
                            </span>
                        @endif
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ $transition->occurred_at->format('Y-m-d H:i') }}
                        @if($transition->actor)
                            — {{ $transition->actor->name }} ({{ $transition->actor->role }})
                        @endif
                    </p>
                    @if($transition->notes)
                        <p class="mt-1 text-sm text-muted-foreground">{{ $transition->notes }}</p>
                    @endif
                </li>
            @empty
                <p class="text-sm text-muted-foreground">No supply chain steps recorded yet.</p>
            @endforelse
        </ol>

        @can('recordTransition', [$food, $nextStage])
            <form action="{{ route('foods.transitions.store', $food) }}" method="POST"
                  class="mt-6 space-y-3 border-t border-border pt-6">
                @csrf
                <p class="text-sm font-medium text-foreground">Record the next step</p>
                <input type="hidden" name="to_stage" value="{{ $nextStage->value }}">
                <p class="text-sm text-muted-foreground">
                    This product will be marked as <strong>{{ $nextStage->label() }}</strong>.
                </p>
                <div class="space-y-2">
                    <april:label for="notes">Notes</april:label>
                    <april:textarea id="notes" name="notes" rows="2"
                                    aria-describedby="to_stage-error">{{ old('notes') }}</april:textarea>
                    <p id="to_stage-error" class="text-sm text-destructive" role="alert" aria-live="polite">
                        @error('to_stage') {{ $message }} @enderror
                    </p>
                </div>
                <april:button type="submit">Record {{ $nextStage->label() }}</april:button>
            </form>
        @else
            {{-- @can compiled to a plain "if" is what let this pair with
                 @elseif/@endif, but @endcan is the explicit form and does not
                 depend on that. --}}
            @if($nextStage === null)
                <p class="mt-6 border-t border-border pt-6 text-sm text-muted-foreground">
                    This product has completed every supply chain stage.
                </p>
            @else
                <p class="mt-6 border-t border-border pt-6 text-sm text-muted-foreground">
                    The next step is <strong>{{ $nextStage->label() }}</strong>. Only an account with the
                    <strong>{{ $nextStage->requiredRole() }}</strong> role can record it, and you are
                    signed in as a {{ auth()->user()->role }}.
                </p>
            @endif
        @endcan
    </x-slot:content>
</april:card>
@endsection