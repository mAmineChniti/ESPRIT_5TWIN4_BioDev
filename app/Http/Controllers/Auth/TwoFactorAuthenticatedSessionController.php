<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorAuthenticatedSessionController extends Controller
{
    public function __construct(
        protected TwoFactorAuthenticationService $twoFactorService
    ) {}

    /**
     * Show the two factor authentication challenge view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return view('front.auth.two-factor-challenge');
    }

    /**
     * Attempt to authenticate using the two factor challenge code or recovery code.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        /** @var User|null $user */
        $user = User::find($request->session()->get('login.id'));

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget(['login.id', 'login.remember']);

            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $code = $request->input('code');
        $recoveryCode = $request->input('recovery_code');

        if ($code !== null && trim((string) $code) !== '') {
            if (! $this->twoFactorService->verify((string) $user->two_factor_secret, (string) $code)) {
                throw ValidationException::withMessages([
                    'code' => [__('The provided two factor authentication code was invalid.')],
                ]);
            }
        } elseif ($recoveryCode !== null && trim((string) $recoveryCode) !== '') {
            if (! $this->twoFactorService->verifyAndConsumeRecoveryCode($user, (string) $recoveryCode)) {
                throw ValidationException::withMessages([
                    'recovery_code' => [__('The provided recovery code was invalid.')],
                ]);
            }
        } else {
            throw ValidationException::withMessages([
                'code' => [__('Please enter an authentication code or a recovery code.')],
            ]);
        }

        $remember = (bool) $request->session()->get('login.remember', false);
        $request->session()->forget(['login.id', 'login.remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended($user->dashboardUrl());
    }
}
