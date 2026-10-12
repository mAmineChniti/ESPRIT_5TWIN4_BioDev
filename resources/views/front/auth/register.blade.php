<x-guest-layout title="{{ __('Register') }}" description="{{ __('Join NutriTrace to track honest food.') }}">
    <form method="POST" action="{{ route('register') }}" class="space-y-4" novalidate>
        @csrf

        <div class="space-y-2">
            <april:label for="name">{{ __('Name') }}</april:label>
            <april:input id="name" type="text" name="name" :value="old('name')" placeholder="Jane Doe" required autofocus autocomplete="name"  aria-describedby="name-error"
                 :aria-invalid="$errors->has('name') ? 'true' : 'false'"/>
            <x-input-error id="name-error" :messages="$errors->get('name')" />
        </div>

        <div class="space-y-2">
            <april:label for="email">{{ __('Email') }}</april:label>
            <april:input id="email" type="email" name="email" :value="old('email')" placeholder="you@example.com" required autocomplete="username"  aria-describedby="email-error"
                 :aria-invalid="$errors->has('email') ? 'true' : 'false'"/>
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>

        <div class="space-y-2">
            <april:label for="role">{{ __('Role') }}</april:label>
            {{-- native-select keeps a real <select>, so old('role') round-trips
                 on a validation failure. The previous markup carried file:* and
                 placeholder:* classes that do nothing on a select. --}}
            <april:native-select id="role" name="role" required
                                   aria-describedby="role-error"
                                   :aria-invalid="$errors->has('role') ? 'true' : 'false'">
                <option value="consumer" @selected(old('role', 'consumer') === 'consumer')>{{ __('Consumer') }}</option>
                <option value="producer" @selected(old('role') === 'producer')>{{ __('Producer') }}</option>
                <option value="processor" @selected(old('role') === 'processor')>{{ __('Processor') }}</option>
                <option value="distributor" @selected(old('role') === 'distributor')>{{ __('Distributor') }}</option>
            </april:native-select>
            <x-input-error id="role-error" :messages="$errors->get('role')" />
        </div>

        <div class="space-y-2">
            <april:label for="password">{{ __('Password') }}</april:label>
            <april:input id="password" type="password" name="password" placeholder="••••••••" required autocomplete="new-password"  aria-describedby="password-error"
                 :aria-invalid="$errors->has('password') ? 'true' : 'false'"/>
            <x-input-error id="password-error" :messages="$errors->get('password')" />
        </div>

        <div class="space-y-2">
            <april:label for="password_confirmation">{{ __('Confirm password') }}</april:label>
            <april:input id="password_confirmation" type="password" name="password_confirmation" placeholder="••••••••" required autocomplete="new-password"  aria-describedby="password_confirmation-error"
                 :aria-invalid="$errors->has('password_confirmation') ? 'true' : 'false'"/>
            <x-input-error id="password_confirmation-error" :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="flex items-center justify-between gap-3 pt-1">
            <april:button-link href="{{ route('login') }}" variant="link" size="sm">{{ __('Already registered?') }}</april:button-link>

            <april:button type="submit" class="ms-auto">{{ __('Register') }}</april:button>
        </div>
    </form>
</x-guest-layout>
