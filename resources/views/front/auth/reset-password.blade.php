<x-guest-layout title="New password">
    <form method="POST" action="{{ route('password.store') }}" class="space-y-4" novalidate>
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="space-y-2">
            <april:label for="email">Email</april:label>
            <april:input id="email" type="email" name="email" :value="old('email', $request->email)" placeholder="you@example.com"
                         required autofocus autocomplete="username"
                         aria-describedby="email-error"
                         :aria-invalid="$errors->has('email') ? 'true' : 'false'" />
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>

        <div class="space-y-2">
            <april:label for="password">Password</april:label>
            <april:input id="password" type="password" name="password" placeholder="••••••••" required autocomplete="new-password"  aria-describedby="password-error"
                 :aria-invalid="$errors->has('password') ? 'true' : 'false'"/>
            <x-input-error id="password-error" :messages="$errors->get('password')" />
        </div>

        <div class="space-y-2">
            <april:label for="password_confirmation">Confirm password</april:label>
            <april:input id="password_confirmation" type="password" name="password_confirmation" placeholder="••••••••" required autocomplete="new-password"  aria-describedby="password_confirmation-error"
                 :aria-invalid="$errors->has('password_confirmation') ? 'true' : 'false'"/>
            <x-input-error id="password_confirmation-error" :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="flex items-center justify-end pt-1">
            <april:button type="submit">Reset password</april:button>
        </div>
    </form>
</x-guest-layout>
