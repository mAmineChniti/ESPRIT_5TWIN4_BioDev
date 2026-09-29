<x-guest-layout title="Reset password" description="We will email you a reset link.">
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div class="space-y-2">
            <april:label for="email">Email</april:label>
            <april:input id="email" type="email" name="email" :value="old('email')" placeholder="you@example.com" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center justify-end gap-3 pt-1">
            <april:button-link href="{{ route('login') }}" variant="link" size="sm">Back to login</april:button-link>
            <april:button type="submit" class="ms-auto">Email reset link</april:button>
        </div>
    </form>
</x-guest-layout>
