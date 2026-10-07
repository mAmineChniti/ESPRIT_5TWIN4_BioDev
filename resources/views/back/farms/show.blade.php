@extends('layouts.back')

@section('title', $farm->name)

@section('content')
    @php($viewer = auth()->user())
    @php($isAdmin = $viewer->isAdmin())
    @php($isOwner = $farm->user_id === $viewer->id)

    <div class="mx-auto max-w-4xl space-y-6">
        {{-- The rejection reason the admin recorded, so the producer knows
             what to fix. The back layout already renders session('success'). --}}
        @if($farm->isRejected())
            <april:alert title="Demande refusée">
                <x-slot:description>
                    @if($farm->rejection_reason)
        Motif communiqué par l'administrateur : « {{ $farm->rejection_reason }} »
                    @else
        Aucun motif n'a été fourni. Contactez l'administration pour plus d'informations.
                    @endif
                </x-slot:description>
            </april:alert>
        @endif

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight">{{ $farm->name }}</h1>

                    <april:badge variant="secondary">{{ $farm->farming_type }}</april:badge>

                    <x-farm-status :status="$farm->status" />
                </div>
                <p class="mt-1 text-sm text-muted-foreground">{{ $farm->address }}</p>
            </div>

            <div class="flex items-center gap-2">
                {{-- Approving and rejecting are admin-only and offered only
                     while the request is actually open. --}}
                @if($isAdmin && $farm->isPending())
                    <form method="POST" action="{{ route('back.farms.approve', $farm) }}">
                        @csrf
                        @method('PATCH')
                        <april:button type="submit" size="sm">
                            <x-lucide-check class="mr-1 size-4" />
                            Accepter
                        </april:button>
                    </form>

                    <x-confirm-action
                        :action="route('back.farms.reject', $farm)"
                        method="PATCH"
                        label="Confirmer le refus"
                        title="Refuser cette demande ?"
                        description="Le motif sera communiqué au producteur. La ferme ne sera pas publiée."
                        trigger-variant="destructive"

                    >
                        <x-lucide-x class="mr-1 size-4" />
                        Refuser
                    </x-confirm-action>
                @endif

                @if($farm->canBeEdited())
                    <april:button-link href="{{ route('back.farms.edit', $farm) }}" variant="outline">
                        <x-lucide-pencil class="mr-2 size-4" />
                        Modifier
                    </april:button-link>
                @else
                    {{-- State why the action is unavailable instead of hiding it. --}}
                    <april:button
                        type="button"
                        variant="outline"
                        disabled
                        aria-disabled="true"
                        title="Cette demande a été refusée et ne peut plus être modifiée."
                    >
                        <x-lucide-pencil class="mr-2 size-4" />
                        Modifier
                        <span class="sr-only"> : cette demande a été refusée et ne peut plus être modifiée.</span>
                    </april:button>
                @endif

                <april:button-link href="{{ route('back.farms.index') }}" variant="ghost">
                    Retour
                </april:button-link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Région Agricole</span>
                    @if($farm->region)
                        <p class="mt-1 text-lg font-bold text-primary">
                            <a href="{{ route('back.regions.show', $farm->region) }}" class="hover:underline">
                                {{ $farm->region->name }}
                            </a>
                        </p>
                        <span class="font-mono text-xs text-muted-foreground">({{ $farm->region->code }})</span>
                    @else
                        <p class="mt-1 text-lg font-medium text-muted-foreground">Non attribuée</p>
                    @endif
                </x-slot:content>
            </april:card>

            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Producteur</span>
                    <p class="mt-1 text-lg font-medium text-foreground">{{ $farm->producer_name ?? ($farm->user?->name ?? 'Non renseigné') }}</p>
                </x-slot:content>
            </april:card>

            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Type de Sol</span>
                    <p class="mt-1 text-lg font-medium text-foreground">{{ $farm->soil_type ?? ($farm->region?->soil_type ?? 'Non renseigné') }}</p>
                </x-slot:content>
            </april:card>

            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Surface</span>
                    <p class="mt-1 font-mono text-lg font-bold text-foreground">{{ number_format((float) $farm->surface_hectares, 2) }} ha</p>
                </x-slot:content>
            </april:card>
        </div>

        <april:card>
            <x-slot:title>Informations de contact &amp; création</x-slot:title>
            <x-slot:content>
                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                    <div>
                        <span class="block text-xs text-muted-foreground">Téléphone :</span>
                        <span class="font-medium">{{ $farm->phone ?? 'Non renseigné' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-muted-foreground">Adresse complète :</span>
                        <span class="font-medium">{{ $farm->address }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-muted-foreground">Date de création :</span>
                        <span class="font-mono text-xs font-medium">{{ $farm->created_at?->format('d/m/Y H:i') ?? '-' }}</span>
                    </div>
                </div>
            </x-slot:content>
        </april:card>

        @if($farm->description)
            <april:card>
                <x-slot:title>Description &amp; Spécificités</x-slot:title>
                <x-slot:content>
                    <p class="text-sm leading-relaxed text-foreground">{{ $farm->description }}</p>
                </x-slot:content>
            </april:card>
        @endif
    </div>
@endsection