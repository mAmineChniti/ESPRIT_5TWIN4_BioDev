<x-guest-layout title="Login" description="Log in to manage the catalog.">
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div class="space-y-2">
            <april:label for="email">Email</april:label>
            <april:input id="email" type="email" name="email" :value="old('email')" placeholder="you@example.com" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="space-y-2">
            <april:label for="password">Password</april:label>
            <april:input id="password" type="password" name="password" placeholder="••••••••" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center gap-2">
            <april:checkbox id="remember_me" name="remember" />
            <april:label for="remember_me" class="font-normal text-muted-foreground">Remember me</april:label>
        </div>

        <div class="flex items-center justify-between gap-3 pt-1">
            @if (Route::has('password.request'))
                <april:button-link href="{{ route('password.request') }}" variant="link" size="sm">Forgot your password?</april:button-link>
            @endif

            <april:button type="submit" class="ms-auto">Log in</april:button>
        </div>
    </form>

    <p class="mt-6 text-center text-sm text-muted-foreground">
        No account yet?
        <april:button-link href="{{ route('register') }}" variant="link" size="sm" class="px-1">Create one</april:button-link>
    </p>
</x-guest-layout>
