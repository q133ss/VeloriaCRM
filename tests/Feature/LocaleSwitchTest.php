<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    public function test_login_page_uses_accept_language_when_session_locale_is_missing(): void
    {
        $response = $this->withHeaders([
            'Accept-Language' => 'ru-RU,ru;q=0.9,en;q=0.8',
        ])->get('/login');

        $response->assertOk();
        $response->assertSee('lang="ru"', false);
    }

    public function test_user_can_switch_locale_to_english(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->withSession(['locale' => 'ru'])
            ->from('/login')
            ->post(route('locale.update'), [
                'locale' => 'en',
            ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('locale', 'en');

        $this->withSession(['locale' => 'en'])
            ->get('/login')
            ->assertSee('lang="en"', false);
    }
}
