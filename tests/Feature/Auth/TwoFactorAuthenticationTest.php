<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_can_be_initiated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('two-factor.enable'));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'two-factor-authentication-enabled');

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNotEmpty($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertTrue($user->twoFactorPendingConfirmation());
        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_two_factor_can_be_confirmed_with_valid_totp(): void
    {
        $service = app(TwoFactorAuthenticationService::class);
        $secret = $service->generateSecretKey();
        $secretBytes = $service->base32Decode($secret);
        $validCode = $service->calculateCode($secretBytes, time());

        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $service->generateRecoveryCodes(),
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('two-factor.confirm'), [
                'code' => $validCode,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'two-factor-authentication-confirmed');

        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertTrue($user->hasTwoFactorEnabled());
    }

    public function test_two_factor_cannot_be_confirmed_with_invalid_code(): void
    {
        $service = app(TwoFactorAuthenticationService::class);
        $secret = $service->generateSecretKey();

        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $service->generateRecoveryCodes(),
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('two-factor.confirm'), [
                'code' => '000000',
            ]);

        $response->assertSessionHasErrors('code');

        $user->refresh();
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_recovery_codes_can_be_regenerated(): void
    {
        $service = app(TwoFactorAuthenticationService::class);
        $oldCodes = ['CODE1-11111', 'CODE2-22222'];

        $user = User::factory()->create([
            'two_factor_secret' => $service->generateSecretKey(),
            'two_factor_recovery_codes' => $oldCodes,
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('two-factor.recovery-codes'));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'recovery-codes-generated');

        $user->refresh();
        $this->assertNotSame($oldCodes, $user->two_factor_recovery_codes);
        $this->assertCount(8, $user->two_factor_recovery_codes);
    }

    public function test_two_factor_can_be_disabled(): void
    {
        $service = app(TwoFactorAuthenticationService::class);

        $user = User::factory()->create([
            'two_factor_secret' => $service->generateSecretKey(),
            'two_factor_recovery_codes' => $service->generateRecoveryCodes(),
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('two-factor.disable'));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'two-factor-authentication-disabled');

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_user_with_two_factor_is_redirected_to_challenge_on_login(): void
    {
        $service = app(TwoFactorAuthenticationService::class);

        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'two_factor_secret' => $service->generateSecretKey(),
            'two_factor_recovery_codes' => $service->generateRecoveryCodes(),
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
        $this->assertSame($user->id, session('login.id'));
    }

    public function test_user_can_authenticate_via_challenge_using_totp(): void
    {
        $service = app(TwoFactorAuthenticationService::class);
        $secret = $service->generateSecretKey();
        $secretBytes = $service->base32Decode($secret);
        $validCode = $service->calculateCode($secretBytes, time());

        $user = User::factory()->create([
            'role' => 'consumer',
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $service->generateRecoveryCodes(),
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->withSession(['login.id' => $user->id])
            ->post(route('two-factor.login.store'), [
                'code' => $validCode,
            ]);

        $response->assertRedirect(route('consumer.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_authenticate_with_invalid_two_factor_code(): void
    {
        $service = app(TwoFactorAuthenticationService::class);

        $user = User::factory()->create([
            'two_factor_secret' => $service->generateSecretKey(),
            'two_factor_recovery_codes' => $service->generateRecoveryCodes(),
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->withSession(['login.id' => $user->id])
            ->post(route('two-factor.login.store'), [
                'code' => '999999',
            ]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_user_can_authenticate_via_recovery_code(): void
    {
        $service = app(TwoFactorAuthenticationService::class);
        $recoveryCodes = ['RECOVER-11111', 'RECOVER-22222'];

        $user = User::factory()->create([
            'role' => 'producer',
            'two_factor_secret' => $service->generateSecretKey(),
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->withSession(['login.id' => $user->id])
            ->post(route('two-factor.login.store'), [
                'recovery_code' => 'RECOVER-11111',
            ]);

        $response->assertRedirect(route('producer.dashboard'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertNotContains('RECOVER-11111', $user->two_factor_recovery_codes);
        $this->assertContains('RECOVER-22222', $user->two_factor_recovery_codes);
    }

    public function test_two_factor_challenge_screen_requires_login_id_in_session(): void
    {
        $response = $this->get(route('two-factor.login'));
        $response->assertRedirect(route('login'));

        $user = User::factory()->create();
        $responseWithSession = $this->withSession(['login.id' => $user->id])
            ->get(route('two-factor.login'));
        $responseWithSession->assertOk();
    }
}
