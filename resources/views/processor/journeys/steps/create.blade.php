@extends('layouts.back')

@section('title', 'Add a step')

@section('content')
<div class="mb-6">
    <a href="{{ route('processor.journeys.steps.index', $journey) }}" class="text-sm text-muted-foreground hover:text-foreground">← Back to steps</a>
    <h1 class="mt-2 text-2xl font-bold">Add a step</h1>
</div>

<form action="{{ route('processor.journeys.steps.store', $journey) }}" method="POST" class="rounded-lg bg-card p-6 shadow">
    @csrf
    @include('processor.journeys.steps.form', ['submitLabel' => 'Add step'])
</form>
@endsection
