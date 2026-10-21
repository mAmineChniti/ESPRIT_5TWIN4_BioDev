<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TwoFactorAuthenticationController extends Controller
{
    public function __construct(
        protected TwoFactorAuthenticationService $twoFactorService
    ) {}

    /**
     * Enable two factor authentication: generate a secret key and recovery codes.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('status', 'two-factor-already-enabled');
        }

        $secret = $this->twoFactorService->generateSecretKey();
        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('status', 'two-factor-authentication-enabled');
    }

    /**
     * Confirm two factor authentication using a 6-digit TOTP code.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            return back()->withErrors(['code' => __('Two factor authentication has not been initiated.')]);
        }

        if (! $this->twoFactorService->verify($user->two_factor_secret, (string) $request->input('code'))) {
            return back()->withErrors(['code' => __('The provided two factor authentication code was invalid.')]);
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        return back()->with('status', 'two-factor-authentication-confirmed');
    }

    /**
     * Regenerate the recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return back();
        }

        $user->forceFill([
            'two_factor_recovery_codes' => $this->twoFactorService->generateRecoveryCodes(),
        ])->save();

        return back()->with('status', 'recovery-codes-generated');
    }

    /**
     * Disable two factor authentication.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('status', 'two-factor-authentication-disabled');
    }
}
