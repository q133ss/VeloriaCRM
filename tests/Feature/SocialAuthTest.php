<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    private function configure(): void
    {
        foreach (['vkid', 'yandex'] as $provider) {
            config([
                "services.{$provider}.client_id" => 'id',
                "services.{$provider}.client_secret" => 'secret',
                "services.{$provider}.redirect" => "http://localhost/auth/{$provider}/callback",
            ]);
        }
    }

    private function fakeProvider(?string $id, ?string $email, string $name = 'Anna Master'): void
    {
        $social = (new SocialUser)->map(['id' => $id, 'name' => $name, 'email' => $email, 'nickname' => null]);

        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->andReturn($social);

        Socialite::shouldReceive('buildProvider')->andReturn($driver);
    }

    public function test_only_vkid_and_yandex_are_supported(): void
    {
        $this->get('/auth/google/redirect')->assertNotFound();
        $this->get('/auth/vkontakte/redirect')->assertNotFound();
    }

    public function test_login_and_register_pages_offer_only_vk_and_yandex(): void
    {
        foreach (['/login', '/register'] as $page) {
            $this->get($page)
                ->assertOk()
                ->assertSee('/auth/vkid/redirect', false)
                ->assertSee('/auth/yandex/redirect', false)
                ->assertDontSee('/auth/google/redirect', false)
                ->assertDontSee('ri-twitter-fill', false)
                ->assertDontSee('ri-github-fill', false);
        }
    }

    public function test_unconfigured_provider_returns_to_login_with_message(): void
    {
        config(['services.vkid.client_id' => null]);

        $this->get('/auth/vkid/redirect')
            ->assertRedirect(route('login'))
            ->assertSessionHas('auth_error');
    }

    public function test_new_user_is_created_and_linked(): void
    {
        $this->configure();
        $this->fakeProvider('42', 'Anna@Example.com');

        $this->get('/auth/yandex/callback')->assertRedirect('/dashboard')->assertCookie('token');

        $user = User::where('email', 'anna@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id, 'provider' => 'yandex', 'provider_id' => '42']);
    }

    public function test_repeat_login_does_not_create_a_second_user(): void
    {
        $this->configure();
        $this->fakeProvider('42', 'anna@example.com');

        $this->get('/auth/yandex/callback');
        auth()->logout();
        $this->get('/auth/yandex/callback');

        $this->assertSame(1, User::count());
        $this->assertSame(1, SocialAccount::count());
    }

    public function test_user_without_email_can_sign_in(): void
    {
        $this->configure();
        $this->fakeProvider('777', null, 'Vk Person');

        $this->get('/auth/vkid/callback')->assertRedirect('/dashboard');

        $user = SocialAccount::where('provider', 'vkid')->firstOrFail()->user;
        $this->assertNull($user->email);
        $this->assertSame('Vk Person', $user->name);
    }

    public function test_existing_account_with_same_email_gets_linked(): void
    {
        $this->configure();
        $existing = User::factory()->create(['email' => 'anna@example.com']);
        $this->fakeProvider('42', 'anna@example.com');

        $this->get('/auth/yandex/callback')->assertRedirect('/dashboard');

        $this->assertSame(1, User::count());
        $this->assertAuthenticatedAs($existing);
    }

    public function test_suspended_user_cannot_sign_in(): void
    {
        $this->configure();
        $user = User::factory()->create(['email' => 'anna@example.com']);
        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();
        $this->fakeProvider('42', 'anna@example.com');

        $this->get('/auth/yandex/callback')
            ->assertRedirect(route('login'))
            ->assertSessionHas('auth_error');

        $this->assertGuest();
    }

    public function test_provider_failure_goes_back_to_login(): void
    {
        $this->configure();
        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->andThrow(new \RuntimeException('boom'));
        Socialite::shouldReceive('buildProvider')->andReturn($driver);

        $this->get('/auth/vkid/callback')
            ->assertRedirect(route('login'))
            ->assertSessionHas('auth_error');
    }
}
