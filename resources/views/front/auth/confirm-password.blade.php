<x-guest-layout title="Confirm password" description="This is a secure area — please confirm your password.">
    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4" novalidate>
        @csrf

        <div class="space-y-2">
            <april:label for="password">Password</april:label>
            <april:input id="password" type="password" name="password" placeholder="••••••••" required autocomplete="current-password"  aria-describedby="password-error"
                 :aria-invalid="$errors->has('password') ? 'true' : 'false'"/>
            <x-input-error id="password-error" :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center justify-end pt-1">
            <april:button type="submit">Confirm</april:button>
        </div>
    </form>
</x-guest-layout>
