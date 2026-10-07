@extends('layouts.back')

@section('title', 'Supply Chain')

@section('content')
    <div class="mb-6">
        <div class="flex items-center gap-4">
            <april:button-link href="{{ route('foods.index') }}" variant="ghost" class="pl-0 text-muted-foreground hover:text-foreground">
                <x-lucide-arrow-left class="mr-2 size-4" />
                Back
            </april:button-link>
            <h1 class="text-2xl font-bold">Supply chain — {{ $food->name }}</h1>
        </div>
    </div>

    <april:card class="max-w-3xl">
        <x-slot:content>
            <ol class="space-y-0">
                @forelse($food->transitions as $transition)
                    <li class="relative flex gap-4 pb-6">
                        <div class="flex flex-col items-center">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
                                {{ $transition->to_stage?->value }}
                            </span>
                            @unless($loop->last)
                                <span class="mt-1 w-px flex-1 bg-muted"></span>
                            @endunless
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

            {{-- The record form is an update to this product's story, so it
                 follows the same rule the controller enforces. --}}
            @can('update', $food)
                @php($nextStage = $food->nextStage())

                @if($nextStage)
                    <form
                        action="{{ route('foods.transitions.store', $food) }}"
                        method="POST"
                        class="mt-6 space-y-3 border-t border-border pt-6"
                    >
                        @csrf
                        <input type="hidden" name="to_stage" value="{{ $nextStage->value }}">

                        <p class="text-sm font-medium text-foreground">
                            Record the next step
                        </p>
                        <p class="text-sm text-muted-foreground">
                            This product will be marked as <strong>{{ $nextStage->label() }}</strong>.
                        </p>

                        <div class="space-y-2">
                            <april:label for="transition-notes">Notes</april:label>
                            <april:textarea
                                id="transition-notes"
                                name="notes"
                                rows="2"
                                placeholder="Anything worth recording about this hand-off?"
                                aria-describedby="transition-notes-hint"
                            >{{ old('notes') }}</april:textarea>
                            <p id="transition-notes-hint" class="text-xs text-muted-foreground">
                                Optional. Visible to every consumer on the product page.
                            </p>
                        </div>

                        {{-- A rejected stage is announced and tied to the form. --}}
                        @error('to_stage')
                            <p role="alert" class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                        @error('notes')
                            <p role="alert" class="text-sm text-destructive">{{ $message }}</p>
                        @enderror

                        <april:button type="submit">
                            Record {{ $nextStage->label() }}
                        </april:button>
                    </form>
                @else
                    <p class="mt-6 border-t border-border pt-6 text-sm text-muted-foreground">
                        This product has completed every supply chain stage.
                    </p>
                @endif
            @endcan
        </x-slot:content>
    </april:card>
@endsection