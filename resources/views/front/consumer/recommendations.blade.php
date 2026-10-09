@extends('layouts.front')

@section('title', 'Responsible Recommendations')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold">More responsible products</h1>
    <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
        Everything below is at least as well evidenced as what you already buy, drawn from the categories
        you have logged. The ordering comes from each product's transparency score, its environmental
        grade and how complete its supply chain is — the explanation is written from that same record.
    </p>
</div>

@if($recommendations->isEmpty())
    <april:card>
        <x-slot:content>
            <div class="p-10 text-center">
                <x-lucide-leaf class="mx-auto mb-3 size-10 text-muted-foreground" />
                <p class="text-sm font-medium">No recommendations yet.</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Log a meal so NutriTrace knows which categories you buy from, and better-evidenced
                    products in those categories will be suggested here.
                </p>
                <april:button-link href="{{ route('meals.create') }}" class="mt-4">Log a meal</april:button-link>
            </div>
        </x-slot:content>
    </april:card>
@else
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($recommendations as $recommendation)
            <x-product-recommendation-card :recommendation="$recommendation" />
        @endforeach
    </div>
@endif

<april:card class="mt-8">
    <x-slot:title>How these are chosen</x-slot:title>
    <x-slot:description>So you can judge the list rather than trust it.</x-slot:description>
    <x-slot:content>
        <ol class="space-y-2.5 text-sm text-muted-foreground">
            <li class="flex items-start gap-2.5">
                <span class="mt-0.5 inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">1</span>
                Only categories you have actually logged meals from, so nothing is suggested out of the blue.
            </li>
            <li class="flex items-start gap-2.5">
                <span class="mt-0.5 inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">2</span>
                Ranked by transparency score out of 100, then by environmental grade, so a fully evidenced
                product always beats a well-labelled one.
            </li>
            <li class="flex items-start gap-2.5">
                <span class="mt-0.5 inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">3</span>
                Products with an upheld greenwashing report rank last, whatever else they score.
            </li>
            <li class="flex items-start gap-2.5">
                <span class="mt-0.5 inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">4</span>
                The sentence on each card is written from that record. The model never chooses what to
                recommend — it only explains a choice already made from the data.
            </li>
        </ol>
    </x-slot:content>
</april:card>
@endsection