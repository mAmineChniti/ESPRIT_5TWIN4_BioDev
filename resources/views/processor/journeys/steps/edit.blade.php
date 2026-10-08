@extends('layouts.back')

@section('title', 'Edit a step')

@section('content')
<div class="mb-6">
    <a href="{{ route('processor.journeys.steps.show', [$journey, $step]) }}" class="text-sm text-muted-foreground hover:text-foreground">← Back to details</a>
    <h1 class="mt-2 text-2xl font-bold">Edit step #{{ $step->step_order }}</h1>
</div>

<form action="{{ route('processor.journeys.steps.update', [$journey, $step]) }}" method="POST" class="rounded-lg bg-card p-6 shadow">
    @csrf
    @method('PUT')
    @include('processor.journeys.steps.form', ['submitLabel' => 'Save changes'])
</form>
@endsection
