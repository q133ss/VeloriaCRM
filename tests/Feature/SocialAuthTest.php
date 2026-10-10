<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
        config([
            'services.yandex.client_id' => 'id',
            'services.yandex.client_secret' => 'secret',
            'services.yandex.redirect' => 'http://localhost/auth/yandex/callback',
        ]);
    }

    private function fakeVk(array $user, int $status = 200): void
    {
        config(['services.vkid.client_id' => '54815286']);
        Http::fake(['id.vk.ru/oauth2/user_info' => Http::response(['user' => $user], $status)]);
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
        $this->get('/auth/vkid/redirect')->assertNotFound();
    }

    public function test_login_and_register_pages_offer_only_vk_and_yandex(): void
    {
        foreach (['/login', '/register'] as $page) {
            $this->get($page)
                ->assertOk()
                ->assertSee('id="vkid-button"', false)
                ->assertSee('/auth/yandex/redirect', false)
                ->assertDontSee('/auth/google/redirect', false)
                ->assertDontSee('ri-twitter-fill', false)
                ->assertDontSee('ri-github-fill', false);
        }
    }

    public function test_unconfigured_provider_returns_to_login_with_message(): void
    {
        config(['services.yandex.client_id' => null]);

        $this->get('/auth/yandex/redirect')
            ->assertRedirect(route('login'))
            ->assertSessionHas('auth_error');
    }

    public function test_yandex_scopes_are_sent_space_separated(): void
    {
        $this->configure();

        $location = $this->get('/auth/yandex/redirect')->assertRedirect()->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame('login:email login:info', $query['scope']);
    }

    public function test_new_user_is_created_and_linked(): void
    {
        $this->configure();
        $this->fakeProvider('42', 'Anna@Example.com');

        $this->get('/auth/yandex/callback?code=abc')->assertRedirect('/dashboard')->assertCookie('token');

        $user = User::where('email', 'anna@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id, 'provider' => 'yandex', 'provider_id' => '42']);
    }

    public function test_token_cookie_is_plain_and_authenticates_the_api(): void
    {
        $this->configure();
        $this->fakeProvider('42', 'anna@example.com');

        $response = $this->get('/auth/yandex/callback?code=abc');
        $plain = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === 'token')->getValue();

        $this->assertMatchesRegularExpression('/^\d+\|[A-Za-z0-9]+$/', $plain);

        $this->flushSession();
        $this->withUnencryptedCookie('token', $plain)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'anna@example.com');
    }

    public function test_repeat_login_does_not_create_a_second_user(): void
    {
        $this->configure();
        $this->fakeProvider('42', 'anna@example.com');

        $this->get('/auth/yandex/callback?code=abc');
        auth()->logout();
        $this->get('/auth/yandex/callback?code=abc');

        $this->assertSame(1, User::count());
        $this->assertSame(1, SocialAccount::count());
    }

    public function test_user_without_email_can_sign_in(): void
    {
        $this->configure();
        $this->fakeProvider('777', null, 'Ya Person');

        $this->get('/auth/yandex/callback?code=abc')->assertRedirect('/dashboard');

        $user = SocialAccount::where('provider', 'yandex')->firstOrFail()->user;
        $this->assertNull($user->email);
        $this->assertSame('Ya Person', $user->name);
    }

    public function test_existing_account_with_same_email_gets_linked(): void
    {
        $this->configure();
        $existing = User::factory()->create(['email' => 'anna@example.com']);
        $this->fakeProvider('42', 'anna@example.com');

        $this->get('/auth/yandex/callback?code=abc')->assertRedirect('/dashboard');

        $this->assertSame(1, User::count());
        $this->assertAuthenticatedAs($existing);
    }

    public function test_suspended_user_cannot_sign_in(): void
    {
        $this->configure();
        $user = User::factory()->create(['email' => 'anna@example.com']);
        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();
        $this->fakeProvider('42', 'anna@example.com');

        $this->get('/auth/yandex/callback?code=abc')
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

        $this->get('/auth/yandex/callback?code=abc')
            ->assertRedirect(route('login'))
            ->assertSessionHas('auth_error');
    }

    public function test_callback_without_code_goes_back_to_login_with_message(): void
    {
        $this->configure();

        $this->get('/auth/yandex/callback')->assertRedirect(route('login'))->assertSessionHas('auth_error');
        $this->get('/auth/yandex/callback?error=access_denied')->assertRedirect(route('login'))->assertSessionHas('auth_error');
    }

    public function test_vk_sdk_is_served_from_our_own_domain(): void
    {
        config(['services.vkid.client_id' => '54815286']);

        $this->get('/login')->assertOk()->assertDontSee('unpkg.com', false)->assertSee('sdk-2.6.9.js', false);
        $this->assertFileExists(public_path('assets/vendor/libs/vkid/sdk-2.6.9.js'));
    }

    public function test_vk_widget_token_signs_in_and_creates_user(): void
    {
        $this->fakeVk(['user_id' => '9', 'first_name' => 'Olga', 'last_name' => 'Ivanova', 'email' => 'olga@example.com']);

        $this->postJson('/api/v1/auth/vkid', ['access_token' => 'abc'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('social_accounts', ['provider' => 'vkid', 'provider_id' => '9']);
        $this->assertSame('Olga Ivanova', User::where('email', 'olga@example.com')->value('name'));
        Http::assertSent(fn ($r) => $r['client_id'] === '54815286' && $r['access_token'] === 'abc');
    }

    public function test_vk_widget_rejects_a_token_vk_does_not_recognise(): void
    {
        $this->fakeVk([], 401);

        $this->postJson('/api/v1/auth/vkid', ['access_token' => 'bad'])->assertStatus(401);
        $this->assertSame(0, User::count());
    }

    public function test_vk_widget_works_without_email_and_refuses_suspended(): void
    {
        $this->fakeVk(['user_id' => '10', 'first_name' => 'Vera']);
        $this->postJson('/api/v1/auth/vkid', ['access_token' => 'abc'])->assertOk();

        SocialAccount::firstOrFail()->user->forceFill(['status' => User::STATUS_SUSPENDED])->save();
        $this->postJson('/api/v1/auth/vkid', ['access_token' => 'abc'])->assertStatus(403);
    }

    public function test_vk_widget_requires_a_token_and_configuration(): void
    {
        $this->postJson('/api/v1/auth/vkid', [])->assertStatus(422);

        config(['services.vkid.client_id' => null]);
        $this->postJson('/api/v1/auth/vkid', ['access_token' => 'abc'])->assertStatus(503);
    }
}
