@extends('layouts.back')

@section('title', 'Modifier une étape')

@section('content')
<div class="mb-6">
    <a href="{{ route('processor.parcours.etapes.show', [$parcours, $etape]) }}" class="text-sm text-muted-foreground hover:text-foreground">← Retour au détail</a>
    <h1 class="mt-2 text-2xl font-bold">Modifier l’étape n°{{ $etape->ordre }}</h1>
</div>

<form action="{{ route('processor.parcours.etapes.update', [$parcours, $etape]) }}" method="POST" class="rounded-lg bg-card p-6 shadow">
    @csrf
    @method('PUT')
    @include('processor.etapes._form', ['submitLabel' => 'Enregistrer les modifications'])
</form>
@endsection
