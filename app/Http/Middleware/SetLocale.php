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

    private const DEFAULT_LOCALE = 'ru';

    public function handle(Request $request, Closure $next): mixed
    {
        $locale = null;

        if ($request->hasSession()) {
            $sessionLocale = $request->session()->get('locale');

            if (is_string($sessionLocale) && in_array($sessionLocale, self::SUPPORTED_LOCALES, true)) {
                $locale = $sessionLocale;
            }
        }

        // Russian is the product default. Accept-Language is deliberately ignored:
        // link-preview crawlers (Telegram, etc.) send "en" and would cache an
        // English snippet. English is only served when the user picks it.
        if (! $locale) {
            $locale = self::DEFAULT_LOCALE;
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
