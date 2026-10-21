@extends('layouts.back')

@section('title', __('Certificate - :name', ['name' => $farm->name]))

@push('styles')
    <style>
        @media print {

            aside,
            header,
            nav,
            footer,
            .print\:hidden,
            april-sidebar,
            april-sidebar-inset>header {
                display: none !important;
            }

            body {
                background-color: #ffffff !important;
                color: #0f172a !important;
                font-family: inherit;
                padding: 0 !important;
                margin: 0 !important;
            }

            main {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }

            .certificate-sheet {
                box-shadow: none !important;
                border: 2px solid #0f172a !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                page-break-inside: avoid;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $qrDownloadUrl = 'data:image/svg+xml;charset=utf-8,' . rawurlencode($qrSvg);
    @endphp

    <div class="mx-auto max-w-4xl space-y-6">
        {{-- Non-printable Action Toolbar --}}
        <div class="flex flex-wrap items-center justify-between gap-4 print:hidden">
            <div>
                <a href="{{ route('farms.show', $farm) }}" class="text-sm text-muted-foreground hover:text-foreground">
                    ← {{ __('Back to farm details') }}
                </a>
                <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Official Farm Certificate') }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ __('Official traceability and accreditation record with scannable QR verification.') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <april:button type="button" onclick="window.print()">
                    <x-lucide-printer class="mr-2 size-4" />
                    {{ __('Print / Save as PDF') }}
                </april:button>

                <april:button-link href="{{ $qrDownloadUrl }}" download="farm-{{ $farm->id }}-qrcode.svg" variant="outline">
                    <x-lucide-qr-code class="mr-2 size-4" />
                    {{ __('Download QR Code') }}
                </april:button-link>

                <april:button-link href="{{ $verificationUrl }}" variant="ghost" target="_blank" rel="noopener noreferrer">
                    <x-lucide-external-link class="mr-2 size-4" />
                    {{ __('Test Public Verification') }}
                </april:button-link>
            </div>
        </div>

        {{-- Official Certificate A4 Sheet --}}
        <div class="certificate-sheet relative rounded-lg border-2 border-border bg-card p-8 shadow-md">
            {{-- Inner decorative certificate border --}}
            <div class="relative rounded border border-border/80 p-6">
                {{-- Certificate Header --}}
                <div class="flex flex-wrap items-start justify-between gap-6 border-b border-border pb-6">
                    <div class="flex items-center gap-4">
                        <div class="flex size-14 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <x-lucide-shield-check class="size-8" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-bold tracking-wider text-primary uppercase">NutriTrace
                                    Registry</span>
                                <span class="text-xs text-muted-foreground">•</span>
                                <span
                                    class="text-xs font-semibold text-muted-foreground uppercase">{{ __('Farm-to-Plate Traceability') }}</span>
                            </div>
                            <h2 class="text-xl font-extrabold tracking-tight text-foreground uppercase sm:text-2xl">
                                {{ __('Certificate of Agricultural Registration') }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{ __('Official registration record issued under agricultural traceability standards.') }}
                            </p>
                        </div>
                    </div>

                    <div class="text-right">
                        <span
                            class="block text-xs font-semibold uppercase text-muted-foreground">{{ __('Certificate Reference') }}</span>
                        <span class="font-mono text-sm font-bold text-foreground">{{ $certificateRef }}</span>
                        <span class="mt-1 block text-xs text-muted-foreground">{{ __('Date of Issue:') }}
                            {{ now()->format('d/m/Y') }}</span>
                    </div>
                </div>

                {{-- Certification Status Banner --}}
                <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-md bg-muted/40 p-4">
                    <div class="flex items-center gap-3">
                        <span
                            class="text-xs font-bold uppercase text-muted-foreground">{{ __('Accreditation Status:') }}</span>
                        <x-farm-status :status="$farm->status" />
                        <april:badge variant="secondary">{{ \App\Models\Farm::farmingTypeLabel($farm->farming_type) }}
                        </april:badge>
                    </div>
                    <div class="text-xs text-muted-foreground">
                        <span class="font-semibold">{{ __('Registration ID:') }}</span>
                        #{{ str_pad((string) $farm->id, 5, '0', STR_PAD_LEFT) }}
                    </div>
                </div>

                {{-- Main Certificate Body --}}
                <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
                    {{-- Left details column (2 cols) --}}
                    <div class="space-y-6 md:col-span-2">
                        {{-- Farm Identification --}}
                        <div>
                            <h3
                                class="flex items-center gap-2 border-b border-border/60 pb-2 text-xs font-bold tracking-wider text-muted-foreground uppercase">
                                <x-lucide-tractor class="size-4 text-primary" />
                                {{ __('Farm Identification') }}
                            </h3>
                            <dl class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm">
                                <div>
                                    <dt class="text-xs text-muted-foreground">{{ __('Holding Name:') }}</dt>
                                    <dd class="font-bold text-foreground">{{ $farm->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-muted-foreground">{{ __('Producer / Operator:') }}</dt>
                                    <dd class="font-medium text-foreground">
                                        {{ $farm->producer_name ?? ($farm->user?->name ?? __('Not provided')) }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-xs text-muted-foreground">{{ __('Physical Address:') }}</dt>
                                    <dd class="font-medium text-foreground">{{ $farm->address }}</dd>
                                </div>
                                @if($farm->phone)
                                    <div>
                                        <dt class="text-xs text-muted-foreground">{{ __('Contact Phone:') }}</dt>
                                        <dd class="font-mono text-xs text-foreground">{{ $farm->phone }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-xs text-muted-foreground">{{ __('Registered On:') }}</dt>
                                    <dd class="font-mono text-xs text-foreground">
                                        {{ $farm->created_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Agronomic Attributes --}}
                        <div>
                            <h3
                                class="flex items-center gap-2 border-b border-border/60 pb-2 text-xs font-bold tracking-wider text-muted-foreground uppercase">
                                <x-lucide-map-pin class="size-4 text-primary" />
                                {{ __('Geographic & Agronomic Specifications') }}
                            </h3>
                            <dl class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm">
                                <div>
                                    <dt class="text-xs text-muted-foreground">{{ __('Agricultural Region:') }}</dt>
                                    <dd class="font-semibold text-primary">
                                        {{ $farm->region?->name ?? __('Not assigned') }}
                                        @if($farm->region?->code)
                                            <span
                                                class="font-mono text-xs text-muted-foreground">({{ $farm->region->code }})</span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-muted-foreground">{{ __('Registered Surface Area:') }}</dt>
                                    <dd class="font-mono font-bold text-foreground">
                                        {{ \App\LocalizedNumber::format($farm->surface_hectares, 2) }} hectares</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-muted-foreground">{{ __('Soil Classification:') }}</dt>
                                    <dd class="font-medium text-foreground">
                                        {{ $farm->soil_type ?? ($farm->region?->soil_type ?? __('Not specified')) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-muted-foreground">{{ __('Regional Climate:') }}</dt>
                                    <dd class="font-medium text-foreground">
                                        {{ $farm->region?->climate ?? __('Not specified') }}</dd>
                                </div>
                            </dl>
                        </div>

                        @if($farm->description)
                            <div>
                                <h3
                                    class="border-b border-border/60 pb-2 text-xs font-bold tracking-wider text-muted-foreground uppercase">
                                    {{ __('Operational Description') }}
                                </h3>
                                <p class="mt-2 text-xs leading-relaxed text-muted-foreground">
                                    {{ $farm->description }}
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Right verification QR seal column (1 col) --}}
                    <div
                        class="flex flex-col items-center justify-between rounded-md border border-border/70 bg-card p-4 text-center">
                        <div class="w-full">
                            <span
                                class="text-xs font-bold tracking-wider uppercase text-foreground">{{ __('Digital Traceability Seal') }}</span>
                            <p class="mt-1 text-[11px] text-muted-foreground">
                                {{ __('Scan to verify authentic certificate on NutriTrace') }}</p>

                            <div class="my-4 flex justify-center rounded border border-border/40 bg-background p-3 shadow-inner">
                                {!! $qrSvg !!}
                            </div>

                            <span
                                class="block font-mono text-[10px] text-muted-foreground uppercase">{{ __('Verification Code') }}</span>
                            <span
                                class="block break-all font-mono text-xs font-bold text-primary">{{ $certificateRef }}</span>
                        </div>

                        <div class="mt-4 w-full border-t border-border/60 pt-3 text-[10px] text-muted-foreground">
                            <p>{{ __('Public URL:') }}</p>
                            <a href="{{ $verificationUrl }}" target="_blank" rel="noopener noreferrer"
                                class="mt-0.5 block break-all text-primary hover:underline">
                                {{ $verificationUrl }}
                            </a>
                        </div>
                    </div>
                </div>


                {{-- Certificate Footer / Attestation --}}
                <div class="mt-8 border-t-2 border-border pt-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 text-xs">
                        <div>
                            <span
                                class="font-bold uppercase text-foreground">{{ __('Official Verification Notice') }}</span>
                            <p class="mt-1 text-muted-foreground leading-relaxed">
                                {{ __('This certificate is generated by the NutriTrace platform. The authenticity of this record can be confirmed at any time by scanning the QR code above or visiting the official registry verification URL.') }}
                            </p>
                        </div>
                        <div class="flex flex-col items-end justify-end text-right">
                            <div class="border-b border-border/80 pb-1 text-center w-48">
                                <span class="font-serif italic text-muted-foreground text-sm">NutriTrace Official
                                    Registry</span>
                            </div>
                            <span class="mt-1 block font-mono text-[10px] text-muted-foreground uppercase">
                                {{ __('Authorized Accreditation System') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection