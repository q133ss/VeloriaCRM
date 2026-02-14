<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthLandingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_token_for_dashboard_redirect_flow(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ]);
    }

    public function test_welcome_shows_guest_actions_for_unauthorized_user(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('/login')
            ->assertSee('/register');
    }

    public function test_welcome_shows_dashboard_and_logout_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('/dashboard')
            ->assertSee('data-welcome-logout', false);
    }
}
