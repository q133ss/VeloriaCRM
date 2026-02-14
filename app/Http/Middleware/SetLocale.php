<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    /**
     * @var string[]
     */
    private const SUPPORTED_LOCALES = ['ru', 'en'];

    public function handle(Request $request, Closure $next): mixed
    {
        $locale = null;

        if ($request->hasSession()) {
            $sessionLocale = $request->session()->get('locale');

            if (is_string($sessionLocale) && in_array($sessionLocale, self::SUPPORTED_LOCALES, true)) {
                $locale = $sessionLocale;
            }
        }

        if (! $locale) {
            $locale = $request->getPreferredLanguage(self::SUPPORTED_LOCALES);
        }

        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = config('app.fallback_locale', 'en');
        }

        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = 'en';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
