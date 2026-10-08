@extends('layouts.back')

@section('title', $farm->name)

@section('content')
    @php($isAdmin = auth()->user()->isAdmin())

    <div class="mx-auto max-w-4xl space-y-6">
        {{-- The rejection reason the admin recorded, so the producer knows
             what to fix. The back layout already renders session('success'). --}}
        @if($farm->isRejected())
            <april:alert title="Request rejected">
                <x-slot:description>
                    @if($farm->rejection_reason)
        Reason provided by the administrator: “{{ $farm->rejection_reason }}”
                    @else
        No reason was provided. Contact an administrator for more information.
                    @endif
                </x-slot:description>
            </april:alert>
        @endif

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight">{{ $farm->name }}</h1>

                    <april:badge variant="secondary">{{ $farm->farming_type }}</april:badge>

                    <x-farm-status :status="$farm->status" />
                </div>
                <p class="mt-1 text-sm text-muted-foreground">{{ $farm->address }}</p>
            </div>

            <div class="flex items-center gap-2">
                {{-- Approving and rejecting are admin-only and offered only
                     while the request is actually open. --}}
                @if($isAdmin && $farm->isPending())
                    <form method="POST" action="{{ route('farms.approve', $farm) }}">
                        @csrf
                        @method('PATCH')
                        <april:button type="submit" size="sm">
                            <x-lucide-check class="mr-1 size-4" />
                            Approve
                        </april:button>
                    </form>

                    <x-farm-reject-action
                        :action="route('farms.reject', $farm)"
                        :farm-name="$farm->name"
                        :farm-id="$farm->id"
                    />
                @endif

                @if($farm->canBeEdited())
                    <april:button-link href="{{ route('farms.edit', $farm) }}" variant="outline">
                        <x-lucide-pencil class="mr-2 size-4" />
                        Edit
                    </april:button-link>
                @else
                    {{-- State why the action is unavailable instead of hiding it. --}}
                    <april:button
                        type="button"
                        variant="outline"
                        disabled
                        aria-disabled="true"
                        title="This request was rejected and cannot be edited."
                    >
                        <x-lucide-pencil class="mr-2 size-4" />
                        Edit
                        <span class="sr-only">: this request was rejected and cannot be edited.</span>
                    </april:button>
                @endif

                <april:button-link href="{{ route('farms.index') }}" variant="ghost">
                    Back
                </april:button-link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Agricultural region</span>
                    @if($farm->region)
                        <p class="mt-1 text-lg font-bold text-primary">
                            <a href="{{ route('regions.show', $farm->region) }}" class="hover:underline">
                                {{ $farm->region->name }}
                            </a>
                        </p>
                        <span class="font-mono text-xs text-muted-foreground">({{ $farm->region->code }})</span>
                    @else
                        <p class="mt-1 text-lg font-medium text-muted-foreground">Not assigned</p>
                    @endif
                </x-slot:content>
            </april:card>

            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Producer</span>
                    <p class="mt-1 text-lg font-medium text-foreground">{{ $farm->producer_name ?? ($farm->user?->name ?? 'Not provided') }}</p>
                </x-slot:content>
            </april:card>

            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Soil type</span>
                    <p class="mt-1 text-lg font-medium text-foreground">{{ $farm->soil_type ?? ($farm->region?->soil_type ?? 'Not provided') }}</p>
                </x-slot:content>
            </april:card>

            <april:card>
                <x-slot:content>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Surface</span>
                    <p class="mt-1 font-mono text-lg font-bold text-foreground">{{ number_format((float) $farm->surface_hectares, 2) }} ha</p>
                </x-slot:content>
            </april:card>
        </div>

        <april:card>
            <x-slot:title>Contact and creation information</x-slot:title>
            <x-slot:content>
                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                    <div>
                        <span class="block text-xs text-muted-foreground">Phone:</span>
                        <span class="font-medium">{{ $farm->phone ?? 'Not provided' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-muted-foreground">Full address:</span>
                        <span class="font-medium">{{ $farm->address }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-muted-foreground">Created:</span>
                        <span class="font-mono text-xs font-medium">{{ $farm->created_at?->format('d/m/Y H:i') ?? '-' }}</span>
                    </div>
                </div>
            </x-slot:content>
        </april:card>

        @if($farm->description)
            <april:card>
                <x-slot:title>Description and features</x-slot:title>
                <x-slot:content>
                    <p class="text-sm leading-relaxed text-foreground">{{ $farm->description }}</p>
                </x-slot:content>
            </april:card>
        @endif
    </div>
@endsection