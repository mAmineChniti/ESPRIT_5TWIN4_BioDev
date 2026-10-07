@extends('layouts.back')

@section('title', 'Détail de l’étape')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="{{ route('processor.parcours.etapes.index', $parcours) }}" class="text-sm text-muted-foreground hover:text-foreground">← Retour aux étapes</a>
        <h1 class="mt-2 text-2xl font-bold">Détail de l’étape n°{{ $etape->ordre }}</h1>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('processor.parcours.etapes.edit', [$parcours, $etape]) }}" class="rounded-md border border-input px-4 py-2 font-medium hover:bg-muted">Modifier</a>
        <form action="{{ route('processor.parcours.etapes.destroy', [$parcours, $etape]) }}" method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer cette étape ?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-md bg-destructive px-4 py-2 font-medium text-destructive-foreground hover:bg-destructive/90">Supprimer</button>
        </form>
    </div>
</div>

<dl class="grid grid-cols-1 gap-6 rounded-lg bg-card p-6 shadow sm:grid-cols-2">
    <div><dt class="text-sm text-muted-foreground">Ordre</dt><dd class="mt-1 font-medium">{{ $etape->ordre }}</dd></div>
    <div><dt class="text-sm text-muted-foreground">Type</dt><dd class="mt-1 font-medium">{{ ucfirst($etape->type) }}</dd></div>
    <div><dt class="text-sm text-muted-foreground">Lieu</dt><dd class="mt-1 font-medium">{{ $etape->lieu }}</dd></div>
    <div><dt class="text-sm text-muted-foreground">Date</dt><dd class="mt-1 font-medium">{{ $etape->date_etape?->format('d/m/Y') }}</dd></div>
    <div class="sm:col-span-2"><dt class="text-sm text-muted-foreground">Description</dt><dd class="mt-1">{{ $etape->description ?: 'Aucune description.' }}</dd></div>
</dl>
@endsection
