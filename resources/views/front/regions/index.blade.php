@extends('layouts.front')

@section('title', 'Régions Agricoles & Fermes')

@section('content')
<div class="space-y-8">
    <div class="text-center max-w-2xl mx-auto space-y-3">
        <span class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
            Traçabilité du Champ à l'Assiette
        </span>
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">
            Découvrez nos Régions Agricoles
        </h1>
        <p class="text-muted-foreground text-sm sm:text-base">
            Explorez les bassins de production et les fermes d'origine de vos aliments pour une transparence totale.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($regions as $region)
            <div class="rounded-xl border border-border bg-card p-6 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between space-y-4">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-xs font-bold rounded-md bg-muted px-2 py-0.5 text-muted-foreground">
                            {{ $region->code }}
                        </span>
                        <span class="text-xs font-semibold text-primary">
                            {{ $region->farms_count }} ferme(s)
                        </span>
                    </div>

                    <h2 class="text-xl font-bold tracking-tight text-foreground">
                        {{ $region->name }}
                    </h2>

                    @if($region->climate)
                        <p class="text-xs text-muted-foreground">
                            <strong>Climat :</strong> {{ $region->climate }}
                        </p>
                    @endif

                    @if($region->description)
                        <p class="text-sm text-muted-foreground line-clamp-3">
                            {{ $region->description }}
                        </p>
                    @endif
                </div>

                <div class="pt-4 border-t border-border">
                    <april:button-link href="{{ route('front.regions.show', $region) }}" variant="outline" class="w-full justify-between">
                        <span>Voir la région et ses fermes</span>
                        <x-lucide-arrow-right class="size-4" />
                    </april:button-link>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
