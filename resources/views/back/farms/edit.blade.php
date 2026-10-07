@extends('layouts.back')

@section('title', 'Modifier la Ferme')

@section('content')
    @php($isAdmin = auth()->user()->isAdmin())

    <div class="mx-auto max-w-2xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Modifier : {{ $farm->name }}</h1>
                <p class="text-sm text-muted-foreground">Mettez à jour les informations de l'exploitation.</p>
            </div>
            <april:button-link href="{{ route('back.farms.index') }}" variant="outline">
                Retour à la liste
            </april:button-link>
        </div>

        <x-farm-status :status="$farm->status" />

        <april:card>
            <x-slot:content>
                <form method="POST" action="{{ route('back.farms.update', $farm) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    @include('back.farms.partials.form-fields', ['farm' => $farm, 'regions' => $regions, 'isAdmin' => $isAdmin])

                    <div class="flex justify-end gap-3 pt-4">
                        <april:button-link href="{{ route('back.farms.index') }}" variant="ghost">Annuler</april:button-link>
                        <april:button type="submit">
                            <x-lucide-save class="mr-2 size-4" />
                            Mettre à jour
                        </april:button>
                    </div>
                </form>
            </x-slot:content>
        </april:card>
    </div>
@endsection