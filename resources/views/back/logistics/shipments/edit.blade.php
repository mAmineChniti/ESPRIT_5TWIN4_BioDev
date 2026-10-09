@extends('layouts.back')

@section('title', 'Edit Shipment')

@section('content')
<div class="mb-6 flex items-center gap-4">
    <a href="{{ route('logistics.shipments.index') }}" class="text-muted-foreground hover:text-foreground">← Back</a>
    <h1 class="text-2xl font-bold">Edit {{ $shipment->reference }}</h1>
</div>

<div class="bg-card rounded-lg shadow p-6 max-w-2xl">
    <form action="{{ route('logistics.shipments.update', $shipment) }}" method="POST" class="space-y-6" novalidate>
        @csrf
        @method('PUT')
        @include('back.logistics.shipments.form')

        <div class="pt-4 flex justify-end">
            <april:button type="submit">Update</april:button>
        </div>
    </form>
</div>
@endsection