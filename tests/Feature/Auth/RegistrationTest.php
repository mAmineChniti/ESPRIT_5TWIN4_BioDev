<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'consumer',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('consumer.dashboard', absolute: false));
    }

    public function test_the_chosen_role_is_persisted_on_registration(): void
    {
        $this->post('/register', [
            'name' => 'Farm Owner',
            'email' => 'farmer@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'producer',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'farmer@example.com',
            'role' => 'producer',
        ]);
    }
}
