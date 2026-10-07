@extends('layouts.back')

@section('title', 'Ajouter une Ferme')

@section('content')
    @php($isAdmin = auth()->user()->isAdmin())

    <div class="mx-auto max-w-2xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Nouvelle Ferme / Exploitation</h1>
                <p class="text-sm text-muted-foreground">
                    @if($isAdmin)
                        Ajoutez directement une ferme validée et rattachée à une région agricole.
                    @else
                        Remplissez les informations de votre ferme et envoyez-la pour validation auprès de l'administrateur.
                    @endif
                </p>
            </div>
            <april:button-link href="{{ route('back.farms.index') }}" variant="outline">
                Retour à la liste
            </april:button-link>
        </div>

        {{-- Say up front what will happen to the submission, rather than
             colouring the submit button to hint at it. --}}
        @unless($isAdmin)
            <april:alert title="Note importante">
                <x-slot:description>
                    Votre nouvelle ferme sera automatiquement placée en statut « En attente de validation ».
                    Un administrateur doit accepter la demande avant qu'elle ne soit publiée.
                </x-slot:description>
            </april:alert>
        @endunless

        <april:card>
            <x-slot:content>
                <form method="POST" action="{{ route('back.farms.store') }}" class="space-y-5">
                    @csrf

                    @include('back.farms.partials.form-fields', ['farm' => null, 'regions' => $regions, 'isAdmin' => $isAdmin])

                    <div class="flex justify-end gap-3 pt-4">
                        <april:button-link href="{{ route('back.farms.index') }}" variant="ghost">Annuler</april:button-link>
                        <april:button type="submit">
                            <x-lucide-send class="mr-2 size-4" />
                            {{ $isAdmin ? 'Enregistrer la Ferme' : 'Envoyer pour validation' }}
                        </april:button>
                    </div>
                </form>
            </x-slot:content>
        </april:card>
    </div>
@endsection