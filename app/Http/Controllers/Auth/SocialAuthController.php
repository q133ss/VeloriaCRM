<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider as ProviderContract;
use Laravel\Socialite\Contracts\User as SocialUser;
use Laravel\Socialite\Facades\Socialite;
use MoveMoveApp\VKID\Provider as VkIdProvider;
use SocialiteProviders\Yandex\Provider as YandexProvider;

class SocialAuthController extends Controller
{
    public const SUPPORTED_PROVIDERS = ['vkid', 'yandex'];

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

        if ($socialUser->getId() === null || $socialUser->getId() === '') {
            return $this->redirectToLoginWithError(
                __('auth.social_login_failed', ['provider' => $this->providerLabel($provider)])
            );
        }

        $user = $this->resolveUser($provider, $socialUser);

        if ($user->isSuspended()) {
            return $this->redirectToLoginWithError(__('auth.failed'));
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

        return redirect()->intended('/dashboard')->withCookie($cookie);
    }

    /**
     * Provider id first; then an existing account with the same (provider
     * verified) email; otherwise a new account. Email may be missing.
     */
    private function resolveUser(string $provider, SocialUser $socialUser): User
    {
        $providerId = (string) $socialUser->getId();
        $email = $socialUser->getEmail() ? Str::lower(trim($socialUser->getEmail())) : null;

        $linked = SocialAccount::where('provider', $provider)->where('provider_id', $providerId)->first();

        if ($linked) {
            return $linked->user;
        }

        return DB::transaction(function () use ($provider, $providerId, $email, $socialUser) {
            $user = $email ? User::where('email', $email)->first() : null;

            if (!$user) {
                $user = new User([
                    'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: ($email ?: 'Veloria'),
                    'email' => $email,
                ]);
                $user->password = Hash::make(Str::random(40));
                $user->email_verified_at = $email ? now() : null;
                $user->save();
            }

            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_id' => $providerId,
                'email' => $email,
            ]);

            return $user;
        });
    }

    private function makeProvider(string $provider): ProviderContract
    {
        return match ($provider) {
            'vkid' => Socialite::buildProvider(VkIdProvider::class, $this->providerConfig('vkid')),
            'yandex' => Socialite::buildProvider(YandexProvider::class, $this->providerConfig('yandex')),
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
        $config['client_secret'] = $config['client_secret'] ?? '';

        if ($provider === 'yandex') {
            $config['scope'] = Arr::wrap($config['scopes'] ?? []);
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
