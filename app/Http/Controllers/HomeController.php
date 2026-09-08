<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Throwable;

class HomeController extends Controller
{
    /**
     * Запасные цены на случай незасеянной базы. Держим их в коде рядом с
     * сидером и миграцией 2026_09_08_020000 — три места, но все три о цене.
     */
    private const FALLBACK_PRICES = [
        'lite' => 0,
        'pro' => 990,
        'elite' => 1990,
    ];

    public function __invoke(): View
    {
        return view('welcome', [
            'isAuthenticated' => Auth::guard('sanctum')->check(),
            'planPrices' => $this->planPrices(),
        ]);
    }

    /**
     * Цены берём из базы, а не из текста лендинга: главная и страница подписки
     * обязаны показывать одно и то же число, иначе разницу первым заметит тот,
     * кто уже собрался платить.
     *
     * Но главная — единственная страница, которую видит холодный трафик, и
     * падать из-за недоступной базы она не имеет права. Отсюда запасной набор.
     */
    private function planPrices(): array
    {
        try {
            $prices = Plan::query()->pluck('price', 'name')->all();
        } catch (Throwable) {
            $prices = [];
        }

        $resolved = [];

        foreach (self::FALLBACK_PRICES as $slug => $fallback) {
            $resolved[$slug] = (int) ($prices[$slug] ?? $fallback);
        }

        return $resolved;
    }
}
