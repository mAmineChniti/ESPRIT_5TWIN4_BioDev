<x-guest-layout title="{{ __('Two-factor Confirmation') }}" description="{{ __('Please confirm access to your account by entering the authentication code provided by your authenticator application.') }}">
    <div x-data="{ recovery: false }">
        <p class="mb-4 text-sm text-muted-foreground" x-show="! recovery">
            {{ __('Please confirm access to your account by entering the authentication code provided by your authenticator application.') }}
        </p>

        <p class="mb-4 text-sm text-muted-foreground" x-show="recovery" x-cloak>
            {{ __('Please confirm access to your account by entering one of your emergency recovery codes.') }}
        </p>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-4" novalidate>
            @csrf

            <div class="space-y-2" x-show="! recovery">
                <april:label for="code">{{ __('Code') }}</april:label>
                <april:input id="code" type="text" inputmode="numeric" name="code" autofocus autocomplete="one-time-code"
                             placeholder="123456" aria-describedby="code-error"
                             :aria-invalid="$errors->has('code') ? 'true' : 'false'" />
                <x-input-error id="code-error" :messages="$errors->get('code')" />
            </div>

            <div class="space-y-2" x-show="recovery" x-cloak>
                <april:label for="recovery_code">{{ __('Recovery code') }}</april:label>
                <april:input id="recovery_code" type="text" name="recovery_code" autocomplete="one-time-code"
                             placeholder="XXXXX-XXXXX" aria-describedby="recovery-code-error"
                             :aria-invalid="$errors->has('recovery_code') ? 'true' : 'false'" />
                <x-input-error id="recovery-code-error" :messages="$errors->get('recovery_code')" />
            </div>

            <div class="flex items-center justify-between gap-3 pt-1">
                <april:button type="button" variant="link" size="sm" class="px-0"
                              x-show="! recovery"
                              x-on:click="recovery = true; $nextTick(() => { document.getElementById('recovery_code')?.focus() })">
                    {{ __('Use a recovery code') }}
                </april:button>

                <april:button type="button" variant="link" size="sm" class="px-0"
                              x-show="recovery" x-cloak
                              x-on:click="recovery = false; $nextTick(() => { document.getElementById('code')?.focus() })">
                    {{ __('Use an authentication code') }}
                </april:button>

                <april:button type="submit" class="ms-auto">{{ __('Log in') }}</april:button>
            </div>
        </form>
    </div>
</x-guest-layout>
