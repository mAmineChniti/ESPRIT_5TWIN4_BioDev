@extends('layouts.back')

@section('title', __('Journey QR code - :name', ['name' => $journey->product?->name ?? __('Product #:id', ['id' => $journey->product_id])]))

@section('content')
@php
    $productName = $journey->product?->name ?? __('Product #:id', ['id' => $journey->product_id]);
    $downloadUrl = 'data:image/svg+xml;charset=utf-8,'.rawurlencode($qrSvg);
@endphp

<div class="mb-6">
    <a href="{{ route('processor.journeys.show', $journey) }}" class="text-sm text-muted-foreground hover:text-foreground">← {{ __('Back to journey') }}</a>
    <h1 class="mt-2 text-2xl font-bold">{{ __('Journey QR code - :name', ['name' => $productName]) }}</h1>
    <p class="mt-1 text-muted-foreground">{{ __('Scan this code to open the public traceability page.') }}</p>
</div>

<april:card class="max-w-2xl">
    <x-slot:content>
        {{-- The SVG paints its own white background with a 4-module quiet
             zone (see JourneyController::qrCode), so this panel follows the
             theme tokens and the code still scans in dark mode. --}}
        <div class="flex justify-center rounded-md bg-card p-6">
            {!! $qrSvg !!}
        </div>

        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <april:button-link href="{{ $downloadUrl }}" download="journey-{{ $journey->id }}.svg">
                {{ __('Download SVG') }}
            </april:button-link>
            <april:button-link href="{{ $publicUrl }}" variant="outline" target="_blank" rel="noopener noreferrer">
                {{ __('Test link') }}
            </april:button-link>
            <april:button-link href="{{ route('processor.journeys.show', $journey) }}" variant="outline">
                {{ __('Back') }}
            </april:button-link>
        </div>

        <div class="mt-6 border-t border-border pt-4">
            <p class="text-sm font-medium text-foreground">{{ __('Public URL') }}</p>
            <a href="{{ $publicUrl }}" target="_blank" rel="noopener noreferrer"
                class="mt-1 block break-all text-sm text-primary hover:underline">{{ $publicUrl }}</a>
        </div>
    </x-slot:content>
</april:card>
@endsection
