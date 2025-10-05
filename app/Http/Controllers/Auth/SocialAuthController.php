<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider as ProviderContract;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\VKontakte\Provider as VKontakteProvider;
use SocialiteProviders\Yandex\Provider as YandexProvider;

class SocialAuthController extends Controller
{
    public const SUPPORTED_PROVIDERS = ['vkontakte', 'yandex', 'google'];

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        if (!$this->isProviderSupported($provider)) {
            abort(404);
        }

        if (!$this->isProviderConfigured($provider)) {
            return $this->redirectToLoginWithError(
                __('auth.social_login_not_configured', ['provider' => $this->providerLabel($provider)])
            );
        }

        return $this->makeProvider($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        if (!$this->isProviderSupported($provider)) {
            abort(404);
        }

        if (!$this->isProviderConfigured($provider)) {
            return $this->redirectToLoginWithError(
                __('auth.social_login_not_configured', ['provider' => $this->providerLabel($provider)])
            );
        }

        try {
            $socialUser = $this->makeProvider($provider)->user();
        } catch (\Throwable $throwable) {
            report($throwable);

            return $this->redirectToLoginWithError(
                __('auth.social_login_failed', ['provider' => $this->providerLabel($provider)])
            );
        }

        $email = $socialUser->getEmail();

        if (empty($email)) {
            return $this->redirectToLoginWithError(
                __('auth.social_login_email_missing', ['provider' => $this->providerLabel($provider)])
            );
        }

        $user = User::firstOrNew(['email' => $email]);

        if (!$user->exists) {
            $user->fill([
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: $email,
            ]);

            $user->password = Hash::make(Str::random(40));
            $user->email_verified_at = now();
            $user->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        $token = $user->createToken('api')->plainTextToken;

        $sameSite = config('session.same_site', 'lax');

        $cookie = cookie(
            'token',
            $token,
            60 * 24 * 30,
            '/',
            null,
            config('session.secure', false),
            false,
            false,
            $sameSite ? strtolower((string) $sameSite) : null
        );

        return redirect()->intended('/')->withCookie($cookie);
    }

    private function makeProvider(string $provider): ProviderContract
    {
        return match ($provider) {
            'google' => Socialite::driver('google'),
            'vkontakte' => Socialite::buildProvider(
                VKontakteProvider::class,
                $this->providerConfig('vkontakte')
            ),
            'yandex' => Socialite::buildProvider(
                YandexProvider::class,
                $this->providerConfig('yandex')
            ),
            default => abort(404),
        };
    }

    private function isProviderConfigured(string $provider): bool
    {
        $config = $this->providerConfig($provider);

        $requiredKeys = ['client_id', 'client_secret', 'redirect'];

        foreach ($requiredKeys as $key) {
            if (empty($config[$key])) {
                return false;
            }
        }

        return true;
    }

    private function providerConfig(string $provider): array
    {
        $config = config("services.{$provider}", []);

        if ($provider === 'vkontakte') {
            $config['scope'] = Arr::wrap($config['scopes'] ?? []);
            $config['version'] = $config['version'] ?? '5.131';
        }

        if ($provider === 'yandex') {
            $config['scope'] = Arr::wrap($config['scopes'] ?? []);
        }

        if ($provider === 'google') {
            $config['scope'] = Arr::wrap($config['scopes'] ?? ['openid', 'profile', 'email']);
        }

        return $config;
    }

    private function redirectToLoginWithError(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('auth_error', $message);
    }

    private function providerLabel(string $provider): string
    {
        return __('auth.providers.' . $provider, [], app()->getLocale());
    }

    private function isProviderSupported(string $provider): bool
    {
        return in_array($provider, self::SUPPORTED_PROVIDERS, true);
    }
}
