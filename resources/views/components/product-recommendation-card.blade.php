@props(['recommendation'])

@php $food = $recommendation->food; @endphp

<april:card class="flex h-full flex-col">
    <x-slot:content>
        <div class="flex h-full flex-col">
            <div class="flex items-start justify-between gap-2">
                <h3 class="font-semibold">
                    <a href="{{ route('products.show', $food) }}" class="hover:text-primary">{{ $food->name }}</a>
                </h3>
                <x-eco-score :score="$food->environmental_score" />
            </div>

            <p class="mt-1 text-xs text-muted-foreground">
                {{ $food->category->name ?? 'Uncategorised' }}
                @if($food->origin) · {{ $food->origin }} @endif
            </p>

            {{-- Why it is suggested. Written by the model from the record; the
                 ranking that chose it did not come from the model. --}}
            <p class="mt-3 text-sm text-foreground">{{ $recommendation->why }}</p>

            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach($food->certifications as $certification)
                    <april:badge variant="none" class="bg-primary/10 text-primary">
                        {{ $certification->name }}
                    </april:badge>
                @endforeach
            </div>

            <div class="mt-auto flex items-center justify-between gap-2 pt-4">
                <april:tooltip>
                    <x-slot:trigger>
                        <span class="inline-flex cursor-help items-center gap-1.5 text-sm">
                            <x-lucide-shield-check class="size-4 text-primary" />
                            <span class="font-semibold tabular-nums">{{ $recommendation->transparencyScore }}</span>
                            <span class="text-xs text-muted-foreground">/100</span>
                        </span>
                    </x-slot:trigger>
                    <x-slot:content>{{ $recommendation->reason }}</x-slot:content>
                </april:tooltip>

                <april:button-link href="{{ route('products.show', $food) }}" variant="link" size="sm">
                    See the evidence
                </april:button-link>
            </div>
        </div>
    </x-slot:content>
</april:card>