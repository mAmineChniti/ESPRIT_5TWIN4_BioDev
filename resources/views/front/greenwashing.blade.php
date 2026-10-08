@extends('layouts.front')

@section('title', 'Spotting greenwashing')

@section('content')
<section class="rounded-2xl border border-border bg-card p-8 shadow-sm">
    <h1 class="text-3xl font-bold">How to spot greenwashing</h1>
    <p class="mt-3 max-w-3xl text-base text-muted-foreground">
        Greenwashing is a claim that sounds environmental but is not backed by anything you can check.
        It is difficult to prove by eye. These are the specific things worth checking on any product —
        and what NutriTrace records so you do not have to guess.
    </p>
</section>

<div class="mt-6 grid gap-4 md:grid-cols-2">
    @php
        $flags = [
            [
                'icon' => 'badge-check',
                'title' => 'A certification with no issuer',
                'body' => '“Organic”, “eco”, “natural” printed on a pack means nothing on its own. A real certification names the body that issued it and carries a certificate number.',
                'check' => 'Look for who issued it. NutriTrace shows the issuer and number, and flags anything expired.',
            ],
            [
                'icon' => 'palette',
                'title' => 'The green tint',
                'body' => 'Green packaging, a leaf icon, or brown kraft paper are design choices. They carry no information about how the product was made.',
                'check' => 'Ignore the packaging colour. NutriTrace scores the recorded chain instead.',
            ],
            [
                'icon' => 'gauge',
                'title' => 'A single letter grade with no method',
                'body' => 'An A–E badge is only useful if someone defined how each grade is reached. Without a published method it is decoration.',
                'check' => 'NutriTrace grades are one fixed scale used for every product, so they can be compared.',
            ],
            [
                'icon' => 'map-pin-off',
                'title' => 'Vague or borrowed origin',
                'body' => '“Made in Europe” covers a very large area. “Product of France” can still be assembled from imported parts.',
                'check' => 'NutriTrace records who registered the product and every step it passed through.',
            ],
            [
                'icon' => 'timer-off',
                'title' => 'A chain with suspiciously tidy dates',
                'body' => 'If every step is recorded the same day, the record was probably written after the fact rather than as the product moved.',
                'check' => 'The timeline shows how long each step really took, so compressed chains stand out.',
            ],
            [
                'icon' => 'file-question',
                'title' => 'A claim nobody maintains',
                'body' => 'Certification schemes change and lapse. A badge that was fair three years ago may no longer be.',
                'check' => 'NutriTrace stores the expiry date and marks lapsed certifications on the product page.',
            ],
        ];
    @endphp

    @foreach($flags as $flag)
        <section class="rounded-xl border border-border bg-card p-5 shadow-sm">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <x-dynamic-component :component="'lucide-'.$flag['icon']" class="size-4" />
                </span>
                <div>
                    <h2 class="font-semibold">{{ $flag['title'] }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ $flag['body'] }}</p>
                    <p class="mt-3 flex items-start gap-2 rounded-lg bg-primary/5 p-3 text-sm">
                        <x-lucide-check class="mt-0.5 size-4 shrink-0 text-primary" />
                        <span>{{ $flag['check'] }}</span>
                    </p>
                </div>
            </div>
        </section>
    @endforeach
</div>

<section class="mt-6 rounded-2xl border border-border bg-card p-8 shadow-sm">
    <h2 class="text-xl font-semibold">What a transparency score means</h2>
    <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
        Every product page carries a score out of 100. It is calculated from things that can be checked,
        never from marketing language:
    </p>

    <ul class="mt-4 space-y-3 text-sm">
        <li class="flex items-start gap-3">
            <span class="mt-1 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">+12</span>
            <span>Each supply chain stage that has actually been recorded, up to all three.</span>
        </li>
        <li class="flex items-start gap-3">
            <span class="mt-1 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">+15</span>
            <span>Certifications on file, plus another +5 if at least one is still within its validity window.</span>
        </li>
        <li class="flex items-start gap-3">
            <span class="mt-1 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">+10</span>
            <span>A recorded environmental grade, so the product can be compared with others.</span>
        </li>
        <li class="flex items-start gap-3">
            <span class="mt-1 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-destructive/15 text-xs font-bold text-destructive">−15</span>
            <span>Each greenwashing report a reviewer has upheld, capped at −40.</span>
        </li>
    </ul>

    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            ['Well traced', 'Every stage recorded and a score of 80 or above.', \App\Enums\VerdictTone::Low],
            ['Partly traced', 'The chain is incomplete, or evidence is missing.', \App\Enums\VerdictTone::Medium],
            ['Unverified', 'No supply chain has been recorded at all.', \App\Enums\VerdictTone::Medium],
            ['At risk', 'One or more reports have been upheld.', \App\Enums\VerdictTone::High],
        ] as [$level, $detail, $tone])
            <div class="rounded-lg border border-border p-4">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-3 w-3 shrink-0 rounded-full {{ $tone->fillClasses() }}"></span>
                    <p class="text-sm font-semibold">{{ $level }}</p>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">{{ $detail }}</p>
            </div>
        @endforeach
    </div>

    <april:button-link href="{{ route('products.index') }}" class="mt-6">
        <x-lucide-search class="size-4" />
        Check a product
    </april:button-link>
</section>
@endsection