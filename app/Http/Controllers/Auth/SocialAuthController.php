<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SocialLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\Provider as ProviderContract;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Yandex\Provider as YandexProvider;

/**
 * Redirect-style social login. Only Yandex goes through here; VK ID signs in
 * through its web widget, see VkIdController.
 */
class SocialAuthController extends Controller
{
    public const SUPPORTED_PROVIDERS = ['yandex'];

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

    public function callback(Request $request, string $provider, SocialLoginService $social): RedirectResponse
    {
        if (!$this->isProviderSupported($provider)) {
            abort(404);
        }

        if (!$this->isProviderConfigured($provider)) {
            return $this->redirectToLoginWithError(
                __('auth.social_login_not_configured', ['provider' => $this->providerLabel($provider)])
            );
        }

        if ($request->filled('error') || !$request->filled('code')) {
            if ($request->filled('error')) {
                report(new \RuntimeException('Social login declined: ' . $request->input('error') . ' ' . $request->input('error_description')));
            }

            return $this->redirectToLoginWithError(
                __('auth.social_login_cancelled', ['provider' => $this->providerLabel($provider)])
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

        $user = $social->resolveUser(
            $provider,
            (string) $socialUser->getId(),
            $socialUser->getEmail(),
            $socialUser->getName() ?: $socialUser->getNickname()
        );

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

    private function makeProvider(string $provider): ProviderContract
    {
        return match ($provider) {
            'yandex' => Socialite::buildProvider(YandexProvider::class, $this->providerConfig('yandex')),
            default => abort(404),
        };
    }

    private function isProviderConfigured(string $provider): bool
    {
        $config = $this->providerConfig($provider);

        foreach (['client_id', 'client_secret', 'redirect'] as $key) {
            if (empty($config[$key])) {
                return false;
            }
        }

        return true;
    }

    private function providerConfig(string $provider): array
    {
        $config = config("services.{$provider}", []);
        $config['scope'] = Arr::wrap($config['scopes'] ?? []);

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
