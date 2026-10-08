@extends('layouts.back')

@section('title', 'Add Farm')

@section('content')
    @php($isAdmin = auth()->user()->isAdmin())

    <div class="mx-auto max-w-2xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">New Farm / Holding</h1>
                <p class="text-sm text-muted-foreground">
                    @if($isAdmin)
                        Add an approved farm linked to an agricultural region.
                    @else
                        Enter your farm details and submit them for administrator approval.
                    @endif
                </p>
            </div>
            <april:button-link href="{{ route('back.farms.index') }}" variant="outline">
                Back to list
            </april:button-link>
        </div>

        {{-- Say up front what will happen to the submission, rather than
             colouring the submit button to hint at it. --}}
        @unless($isAdmin)
            <april:alert title="Important note">
                <x-slot:description>
                        Your new farm will automatically be marked as pending.
                        An administrator must approve it before it is published.
                </x-slot:description>
            </april:alert>
        @endunless

        <april:card>
            <x-slot:content>
                <form method="POST" action="{{ route('back.farms.store') }}" class="space-y-5">
                    @csrf

                    @include('back.farms.partials.form-fields', ['farm' => null, 'regions' => $regions, 'isAdmin' => $isAdmin])

                    <div class="flex justify-end gap-3 pt-4">
                        <april:button-link href="{{ route('back.farms.index') }}" variant="ghost">Cancel</april:button-link>
                        <april:button type="submit">
                            <x-lucide-send class="mr-2 size-4" />
                            {{ $isAdmin ? 'Save Farm' : 'Submit for review' }}
                        </april:button>
                    </div>
                </form>
            </x-slot:content>
        </april:card>
    </div>
@endsection