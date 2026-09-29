<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>NutriTrace{{ $title ? ' - ' . $title : '' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @aprilScripts
</head>
<body class="flex min-h-screen flex-col bg-background font-sans text-foreground antialiased">
    @include('layouts.partials.front-nav')

    <main class="relative flex flex-1 items-center justify-center overflow-hidden px-4 py-10">
        <div class="absolute -top-32 left-1/2 h-80 w-[36rem] -translate-x-1/2 rounded-full bg-primary/10 blur-3xl" aria-hidden="true"></div>

        <div class="relative grid w-full max-w-4xl overflow-hidden rounded-2xl border border-border bg-card shadow-sm lg:grid-cols-[1fr_1.1fr]">
            {{-- Brand panel --}}
            <div class="relative hidden flex-col justify-between overflow-hidden bg-primary p-8 text-primary-foreground lg:flex">
                <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
                <x-lucide-sprout class="absolute -bottom-12 -right-12 size-52 opacity-15" aria-hidden="true" />

                <div class="relative flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                        <x-lucide-sprout class="h-5 w-5" stroke-width="2.25" />
                    </span>
                    <span class="text-lg font-extrabold tracking-tight">NutriTrace</span>
                </div>

                <div class="relative">
                    <p class="text-2xl font-bold leading-snug">Honest food starts with knowing its story.</p>
                    <p class="mt-2 text-sm text-primary-foreground/80">Track every product from farm to plate — footprint and certifications included.</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <april:badge variant="none" class="border-white/25 bg-white/10 text-white">Organic</april:badge>
                        <april:badge variant="none" class="border-white/25 bg-white/10 text-white">Local</april:badge>
                        <april:badge variant="none" class="border-white/25 bg-white/10 text-white">Fair trade</april:badge>
                    </div>
                </div>
            </div>

            {{-- Form panel --}}
            <div class="p-8 sm:p-10">
                @if($title)
                    <h1 class="text-2xl font-bold tracking-tight">{{ $title }}</h1>
                @endif
                @if($description)
                    <p class="mt-1.5 text-sm text-muted-foreground">{{ $description }}</p>
                @endif
                <div class="mt-6">{{ $slot }}</div>
            </div>
        </div>
    </main>

    @include('layouts.partials.front-footer')
</body>
</html>
