@extends('layouts.back')

@section('title', 'Ajouter une étape')

@section('content')
<div class="mb-6">
    <a href="{{ route('processor.parcours.etapes.index', $parcours) }}" class="text-sm text-muted-foreground hover:text-foreground">← Retour aux étapes</a>
    <h1 class="mt-2 text-2xl font-bold">Ajouter une étape</h1>
</div>

<form action="{{ route('processor.parcours.etapes.store', $parcours) }}" method="POST" class="rounded-lg bg-card p-6 shadow">
    @csrf
    @include('processor.etapes._form', ['submitLabel' => 'Ajouter l’étape'])
</form>
@endsection
