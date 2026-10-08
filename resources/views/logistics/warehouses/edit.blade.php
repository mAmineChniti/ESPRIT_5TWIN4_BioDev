@extends('layouts.back')

@section('title', 'Edit Warehouse')

@section('content')
<div class="mb-6 flex items-center gap-4">
    <a href="{{ route('logistics.warehouses.index') }}" class="text-muted-foreground hover:text-foreground">← Back</a>
    <h1 class="text-2xl font-bold">Edit {{ $warehouse->name }}</h1>
</div>

<div class="bg-card rounded-lg shadow p-6 max-w-2xl">
    <form action="{{ route('logistics.warehouses.update', $warehouse) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        @include('logistics.warehouses.form')

        <div class="pt-4 flex justify-end">
            <button type="submit" class="bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-2 px-6 rounded-md">Update</button>
        </div>
    </form>
</div>
@endsection