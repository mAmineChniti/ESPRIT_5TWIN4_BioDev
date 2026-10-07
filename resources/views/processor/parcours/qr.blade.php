@extends('layouts.back')

@section('title', 'QR code du parcours - '.($parcours->produit?->name ?? 'Produit #'.$parcours->produit_id))

@section('content')
@php
    $productName = $parcours->produit?->name ?? 'Produit #'.$parcours->produit_id;
    $downloadUrl = 'data:image/svg+xml;charset=utf-8,'.rawurlencode($qrSvg);
@endphp

<div class="mb-6">
    <a href="{{ route('processor.parcours.show', $parcours) }}" class="text-sm text-muted-foreground hover:text-foreground">← Retour au parcours</a>
    <h1 class="mt-2 text-2xl font-bold">QR code du parcours - {{ $productName }}</h1>
    <p class="mt-1 text-muted-foreground">Scannez ce code pour ouvrir la page publique de traçabilité.</p>
</div>

<div class="max-w-2xl rounded-lg bg-card p-6 shadow">
    <div class="flex justify-center rounded-md bg-white p-6">
        {!! $qrSvg !!}
    </div>

    <div class="mt-6 flex flex-wrap justify-center gap-3">
        <a href="{{ $downloadUrl }}" download="parcours-{{ $parcours->id }}.svg"
            class="rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground hover:bg-primary/90">
            Télécharger le SVG
        </a>
        <a href="{{ $publicUrl }}" target="_blank" rel="noopener noreferrer"
            class="rounded-md border border-input px-4 py-2 font-medium hover:bg-muted">
            Tester le lien
        </a>
        <a href="{{ route('processor.parcours.show', $parcours) }}"
            class="rounded-md border border-input px-4 py-2 font-medium hover:bg-muted">
            Retour
        </a>
    </div>

    <div class="mt-6 border-t border-border pt-4">
        <p class="text-sm font-medium text-foreground">URL publique</p>
        <a href="{{ $publicUrl }}" target="_blank" rel="noopener noreferrer"
            class="mt-1 block break-all text-sm text-primary hover:underline">{{ $publicUrl }}</a>
    </div>
</div>
@endsection
