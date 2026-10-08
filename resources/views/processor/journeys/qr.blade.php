@extends('layouts.back')

@section('title', 'Journey QR code - '.($journey->product?->name ?? 'Product #'.$journey->product_id))

@section('content')
@php
    $productName = $journey->product?->name ?? 'Product #'.$journey->product_id;
    $downloadUrl = 'data:image/svg+xml;charset=utf-8,'.rawurlencode($qrSvg);
@endphp

<div class="mb-6">
    <a href="{{ route('processor.journeys.show', $journey) }}" class="text-sm text-muted-foreground hover:text-foreground">← Back to journey</a>
    <h1 class="mt-2 text-2xl font-bold">Journey QR code - {{ $productName }}</h1>
    <p class="mt-1 text-muted-foreground">Scan this code to open the public traceability page.</p>
</div>

<div class="max-w-2xl rounded-lg bg-card p-6 shadow">
    <div class="flex justify-center rounded-md bg-white p-6">
        {!! $qrSvg !!}
    </div>

    <div class="mt-6 flex flex-wrap justify-center gap-3">
        <a href="{{ $downloadUrl }}" download="journey-{{ $journey->id }}.svg"
            class="rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground hover:bg-primary/90">
            Download SVG
        </a>
        <a href="{{ $publicUrl }}" target="_blank" rel="noopener noreferrer"
            class="rounded-md border border-input px-4 py-2 font-medium hover:bg-muted">
            Test link
        </a>
        <a href="{{ route('processor.journeys.show', $journey) }}"
            class="rounded-md border border-input px-4 py-2 font-medium hover:bg-muted">
            Back
        </a>
    </div>

    <div class="mt-6 border-t border-border pt-4">
        <p class="text-sm font-medium text-foreground">Public URL</p>
        <a href="{{ $publicUrl }}" target="_blank" rel="noopener noreferrer"
            class="mt-1 block break-all text-sm text-primary hover:underline">{{ $publicUrl }}</a>
    </div>
</div>
@endsection
