@extends('layouts.back')

@section('title', __('Edit Warehouse'))

@section('content')
<div class="mb-6 flex items-center gap-4">
    <a href="{{ route('logistics.warehouses.index') }}" class="text-muted-foreground hover:text-foreground">← {{ __('Back') }}</a>
    <h1 class="text-2xl font-bold">{{ __('Edit :name', ['name' => $warehouse->name]) }}</h1>
</div>

<april:card class="max-w-2xl">
    <x-slot:content>
        <form action="{{ route('logistics.warehouses.update', $warehouse) }}" method="POST" class="space-y-6" novalidate>
            @csrf
            @method('PUT')
            @include('back.logistics.warehouses.form')

            <div class="pt-4 flex justify-end">
                <april:button type="submit">{{ __('Update') }}</april:button>
            </div>
        </form>
    </x-slot:content>
</april:card>
@endsection