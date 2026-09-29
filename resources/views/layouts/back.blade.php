<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriTrace - @yield('title', 'Dashboard')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @aprilScripts
    @stack('styles')
</head>
<body class="min-h-screen bg-background font-sans text-foreground antialiased">
<april:sidebar-layout>
    @include('layouts.partials.back-sidebar')

    <april:sidebar-inset>
        @include('layouts.partials.back-topbar')

        <main class="flex-1 space-y-6 p-6">
            @if(session('success'))
                <april:alert title="Success">
                    <x-slot:description>{{ session('success') }}</x-slot:description>
                </april:alert>
            @endif

            @yield('content')
        </main>
    </april:sidebar-inset>
</april:sidebar-layout>
@stack('scripts')
</body>
</html>
