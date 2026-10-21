<div class="space-y-4">
    @if (session('status') === 'two-factor-authentication-enabled')
        <p class="text-sm font-medium text-primary" role="status" aria-live="polite">
            {{ __('Two factor authentication setup initiated. Please scan the QR code below to confirm.') }}
        </p>
    @elseif (session('status') === 'two-factor-authentication-confirmed')
        <p class="text-sm font-medium text-primary" role="status" aria-live="polite">
            {{ __('Two factor authentication has been enabled.') }}
        </p>
    @elseif (session('status') === 'two-factor-authentication-disabled')
        <p class="text-sm font-medium text-primary" role="status" aria-live="polite">
            {{ __('Two factor authentication has been disabled.') }}
        </p>
    @elseif (session('status') === 'recovery-codes-generated')
        <p class="text-sm font-medium text-primary" role="status" aria-live="polite">
            {{ __('New recovery codes have been generated.') }}
        </p>
    @endif

    @if ($user->hasTwoFactorEnabled())
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <april:badge variant="secondary">{{ __('Enabled') }}</april:badge>
                <span class="text-sm text-muted-foreground">{{ __('Two factor authentication is currently active on your account.') }}</span>
            </div>

            @if (! empty($user->two_factor_recovery_codes))
                <div class="space-y-2">
                    <p class="text-sm font-medium">{{ __('Recovery codes') }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ __('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.') }}
                    </p>
                    <div class="grid grid-cols-2 gap-2 rounded-md border border-border bg-muted/40 p-3 font-mono text-xs">
                        @foreach ($user->two_factor_recovery_codes as $recoveryCode)
                            <div>{{ $recoveryCode }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-3 pt-2">
                <form method="post" action="{{ route('two-factor.recovery-codes') }}" novalidate>
                    @csrf
                    <april:button type="submit" variant="outline">{{ __('Regenerate recovery codes') }}</april:button>
                </form>

                <form method="post" action="{{ route('two-factor.disable') }}" novalidate>
                    @csrf
                    @method('delete')
                    <april:button type="submit" variant="destructive">{{ __('Disable 2FA') }}</april:button>
                </form>
            </div>
        </div>
    @elseif ($user->twoFactorPendingConfirmation())
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <april:badge variant="outline">{{ __('Pending confirmation') }}</april:badge>
                <span class="text-sm text-muted-foreground">{{ __('Scan the QR code and enter the 6-digit code to activate.') }}</span>
            </div>

            <div class="space-y-2">
                <p class="text-sm text-muted-foreground">
                    {{ __("To finish enabling two factor authentication, scan the following QR code using your phone's authenticator application, or enter the setup key manually.") }}
                </p>

                @if ($twoFactorQrCode)
                    <div class="inline-block rounded-lg border border-border bg-card p-3">
                        {!! $twoFactorQrCode !!}
                    </div>
                @endif

                @if ($twoFactorSecret)
                    <p class="text-xs text-muted-foreground">
                        {{ __('Setup key:') }}
                        <span class="font-mono font-semibold select-all text-foreground">{{ $twoFactorSecret }}</span>
                    </p>
                @endif
            </div>

            <form id="cancel-two-factor" method="post" action="{{ route('two-factor.disable') }}" class="hidden" novalidate>
                @csrf
                @method('delete')
            </form>

            <form method="post" action="{{ route('two-factor.confirm') }}" class="max-w-xs space-y-3" novalidate>
                @csrf
                <div class="space-y-1">
                    <april:label for="two_factor_code">{{ __('Authentication code') }}</april:label>
                    <april:input id="two_factor_code" name="code" type="text" inputmode="numeric" placeholder="123456"
                                 autocomplete="one-time-code" required autofocus
                                 aria-describedby="two_factor_code_error"
                                 :aria-invalid="$errors->has('code') ? 'true' : 'false'" />
                    <x-input-error id="two_factor_code_error" :messages="$errors->get('code')" />
                </div>

                <div class="flex items-center gap-3">
                    <april:button type="submit">{{ __('Confirm') }}</april:button>
                    <april:button type="submit" form="cancel-two-factor" variant="ghost" size="sm">{{ __('Cancel') }}</april:button>
                </div>
            </form>
        </div>
    @else
        <div class="space-y-4">
            <p class="text-sm text-muted-foreground">
                {{ __('When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone\'s Google Authenticator or compatible application.') }}
            </p>

            <form method="post" action="{{ route('two-factor.enable') }}" novalidate>
                @csrf
                <april:button type="submit">{{ __('Enable 2FA') }}</april:button>
            </form>
        </div>
    @endif
</div>
