@extends('layouts.back')

@section('title', 'Edit Farm')

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Edit: {{ $farm->name }}</h1>
                <p class="text-sm text-muted-foreground">Update the farm details.</p>
            </div>
            <april:button-link href="{{ route('farms.index') }}" variant="outline">
                Back to list
            </april:button-link>
        </div>

        <x-farm-status :status="$farm->status" />

        <april:card>
            <x-slot:content>
                <form method="POST" action="{{ route('farms.update', $farm) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    @include('back.farms.partials.form-fields', ['farm' => $farm, 'regions' => $regions])

                    <div class="flex justify-end gap-3 pt-4">
                        <april:button-link href="{{ route('farms.index') }}" variant="ghost">Cancel</april:button-link>
                        <april:button type="submit">
                            <x-lucide-save class="mr-2 size-4" />
                            Update farm
                        </april:button>
                    </div>
                </form>
            </x-slot:content>
        </april:card>
    </div>
@endsection