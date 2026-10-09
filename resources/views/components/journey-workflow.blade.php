@props(['journey', 'manage' => false])

{{--
    One journey as a workflow, not a spreadsheet.

    The tracker follows the canonical origin → transport → storage → sale flow:
    recorded stages are completed and link to their step, the first unrecorded
    stage is current, the rest are upcoming. Below it, every recorded step is
    listed in order with its details — and, in manage mode, its actions plus a
    call to action that opens the create form prefilled with the next free
    position and the suggested stage.

    Used by the journey page (read-only) and the steps page (manage), so the
    two cannot drift apart.
--}}
@php
    use App\Models\JourneyStep;

    $recorded = $journey->steps;
    $firstOfType = $recorded->groupBy('type')->map->first();
    $suggested = JourneyStep::suggestedType($recorded->pluck('type'));
    $nextOrder = $journey->nextStepOrder();
    $recordedStages = collect(JourneyStep::FLOW)
        ->filter(fn (string $type): bool => $firstOfType->has($type))
        ->count();

    $items = [];

    foreach (JourneyStep::FLOW as $index => $type) {
        $hit = $firstOfType->get($type);

        if ($hit) {
            $items[] = [
                'step' => $index + 1,
                'state' => 'completed',
                'label' => $hit->label(),
                'description' => $hit->location.' · '.($hit->step_date?->format('j M Y') ?? 'No date'),
                'href' => route('processor.journeys.steps.show', [$journey, $hit]),
            ];
        } elseif ($type === $suggested) {
            $items[] = [
                'step' => $index + 1,
                'state' => 'current',
                'label' => JourneyStep::labels()[$type],
                'description' => 'Suggested next',
            ];
        } else {
            $items[] = [
                'step' => $index + 1,
                'state' => 'upcoming',
                'label' => JourneyStep::labels()[$type],
                'description' => 'Not recorded yet',
            ];
        }
    }
@endphp

<p class="text-sm text-muted-foreground" aria-live="polite">
    {{ $recordedStages }} of {{ count(JourneyStep::FLOW) }} stages recorded
</p>

<april:steps orientation="vertical" :items="$items" :current="array_search($suggested, JourneyStep::FLOW, true) + 1" />

@if ($recorded->isNotEmpty())
    <ol class="mt-6 space-y-3">
        @foreach ($recorded as $step)
            <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border p-4">
                <div class="min-w-0">
                    <p class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="font-semibold tabular-nums text-muted-foreground">#{{ $step->step_order }}</span>
                        <april:badge variant="secondary">{{ $step->label() }}</april:badge>
                        <span class="text-xs text-muted-foreground">{{ $step->step_date?->format('j M Y') }}</span>
                    </p>
                    <p class="mt-1 text-sm font-medium">{{ $step->location }}</p>
                    @if ($step->description)
                        <p class="mt-0.5 text-sm text-muted-foreground">{{ $step->description }}</p>
                    @endif
                </div>
                @if ($manage)
                    <div class="flex shrink-0 items-center gap-2">
                        <april:button-link
                            href="{{ route('processor.journeys.steps.show', [$journey, $step]) }}"
                            variant="ghost"
                            size="sm"
                            aria-label="View step {{ $step->step_order }}"
                        >
                            Details
                        </april:button-link>
                        <april:button-link
                            href="{{ route('processor.journeys.steps.edit', [$journey, $step]) }}"
                            variant="ghost"
                            size="sm"
                            aria-label="Edit step {{ $step->step_order }}"
                        >
                            Edit
                        </april:button-link>
                        <x-confirm-action
                            :action="route('processor.journeys.steps.destroy', [$journey, $step])"
                            label="Delete step"
                            title="Delete this step?"
                            description="Step {{ $step->step_order }} ({{ $step->label() }}) will be permanently deleted from this journey."
                            triggerVariant="ghost"
                            triggerSize="sm"
                        >Delete</x-confirm-action>
                    </div>
                @endif
            </li>
        @endforeach
    </ol>
@elseif ($manage)
    <p class="mt-6 rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
        No steps recorded yet. Start with the origin, where the product was grown.
    </p>
@endif

@if ($manage)
    @if ($recordedStages >= count(JourneyStep::FLOW))
        <april:alert variant="none" class="mt-6 border-secondary/50 bg-secondary/50 text-secondary-foreground">
            <x-slot:icon><x-lucide-circle-check class="size-4" /></x-slot:icon>
            <x-slot:description>
                Every workflow stage is recorded. You can still add further steps of any stage.
            </x-slot:description>
        </april:alert>
    @else
        <april:button-link
            href="{{ route('processor.journeys.steps.create', [$journey, 'type' => $suggested, 'step_order' => $nextOrder]) }}"
            class="mt-6"
        >
            <x-lucide-plus class="size-4" />
            Record {{ JourneyStep::labels()[$suggested] }} — step {{ $nextOrder }}
        </april:button-link>
    @endif
@endif
