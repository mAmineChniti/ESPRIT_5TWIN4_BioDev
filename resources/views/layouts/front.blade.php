<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php($pageTitle = trim($__env->yieldContent('title')))
    <title>{{ $pageTitle === '' ? 'NutriTrace' : "NutriTrace - {$pageTitle}" }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <x-theme-bootstrap />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @aprilScripts
    @stack('styles')
</head>
<body class="flex min-h-screen flex-col bg-background font-sans text-foreground antialiased">
    @include('layouts.partials.front-nav')

    <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-8">
        @if(session('success'))
            <april:alert title="Success">
                <x-slot:description>{{ session('success') }}</x-slot:description>
            </april:alert>
        @endif

        @yield('content')
    </main>

    @include('layouts.partials.front-footer')

    @stack('scripts')
</body>
</html>
