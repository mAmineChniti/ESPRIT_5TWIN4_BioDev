@extends('layouts.back')

@section('title', 'Create Agricultural Region')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">New Agricultural Region</h1>
            <p class="text-sm text-muted-foreground">Add a geographic production area to the platform.</p>
        </div>
        <april:button-link href="{{ route('regions.index') }}" variant="outline">
            Back to list
        </april:button-link>
    </div>

    <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
        <form method="POST" action="{{ route('regions.store') }}" class="space-y-5">
            @csrf

            <div class="space-y-2">
                <april:label for="name">Region name <span class="text-destructive">*</span></april:label>
                <april:input id="name" name="name" value="{{ old('name') }}" required
                    placeholder="Example: Cap Bon - Nabeul"
                    @class(['border-destructive' => $errors->has('name')])
                    @if($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif
                />
                @error('name') <p id="name-error" role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2">
                <april:label for="code">Unique code <span class="text-destructive">*</span></april:label>
                <april:input id="code" name="code" value="{{ old('code') }}" required
                    placeholder="Example: REG-CAPBON"
                    @class(['font-mono', 'border-destructive' => $errors->has('code')])
                    @if($errors->has('code')) aria-invalid="true" aria-describedby="code-error" @endif
                />
                @error('code') <p id="code-error" role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <april:label for="climate">Dominant climate</april:label>
                    <april:input id="climate" name="climate" value="{{ old('climate') }}"
                        placeholder="Example: Mild Mediterranean" />
                    @error('climate') <p role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <april:label for="soil_type">Dominant soil type</april:label>
                    <april:input id="soil_type" name="soil_type" value="{{ old('soil_type') }}"
                        placeholder="Example: Sandy clay" />
                    @error('soil_type') <p role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-2">
                <april:label for="description">Description and features</april:label>
                <x-textarea-field id="description" name="description" rows="4"
                    :value="old('description')"
                    placeholder="Describe the main crops, geographic features, and more."
                    describedby="description-error"
                    :aria-invalid="$errors->has('description') ? 'true' : 'false'" />
                @error('description') <p id="description-error" role="alert" class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <april:button-link href="{{ route('regions.index') }}" variant="ghost">Cancel</april:button-link>
                <april:button type="submit">Save region</april:button>
            </div>
        </form>
    </div>
</div>
@endsection
