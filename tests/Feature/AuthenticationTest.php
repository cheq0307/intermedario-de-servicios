<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_register_and_reach_dashboard(): void
    {
        $response = $this->post('/register', [
            'account_type' => 'client',
            'name' => 'Cliente Ejemplo',
            'email' => 'cliente@example.test',
            'password' => 'Seguro123',
            'password_confirmation' => 'Seguro123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'cliente@example.test',
            'account_type' => 'client',
        ]);
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'Seguro123']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Seguro123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }
}
