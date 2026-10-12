@extends('layouts.back')

@section('title', __('Add Shipment'))

@section('content')
<div class="mb-6 flex items-center gap-4">
    <a href="{{ route('logistics.shipments.index') }}" class="text-muted-foreground hover:text-foreground">← {{ __('Back') }}</a>
    <h1 class="text-2xl font-bold">{{ __('Add a shipment') }}</h1>
</div>

<april:card class="max-w-2xl">
    <x-slot:content>
        <form action="{{ route('logistics.shipments.store') }}" method="POST" class="space-y-6" novalidate>
            @csrf
            @include('back.logistics.shipments.form')

            <div class="pt-4 flex justify-end">
                <april:button type="submit">{{ __('Save') }}</april:button>
            </div>
        </form>
    </x-slot:content>
</april:card>
@endsection