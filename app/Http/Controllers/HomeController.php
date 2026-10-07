<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\Landing\TemplateRegistry;
use App\Support\VideoEmbed;
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

    public function __invoke(TemplateRegistry $registry): View
    {
        [$templates, $templateCount] = $this->templates($registry);

        return view('welcome', [
            'isAuthenticated' => Auth::guard('sanctum')->check(),
            'planPrices' => $this->planPrices(),
            'templates' => $templates,
            'templateCount' => $templateCount,
            'androidAppUrl' => config('landing_home.android_app_url') ?: null,
            'videoReviews' => $this->videoReviews(),
        ]);
    }

    /**
     * Галерея сайтов на главной. Шаблоны читаются из файлов-манифестов, и
     * битый манифест не должен ронять страницу холодного трафика — отсюда
     * тот же запасной путь, что у цен: без галереи, но с главной.
     *
     * @return array{0: list<array{slug: string, title: string, thumb: string}>, 1: int}
     */
    private function templates(TemplateRegistry $registry): array
    {
        try {
            $layouts = $registry->layouts();
        } catch (Throwable) {
            return [[], 0];
        }

        $featured = collect(config('landing_home.featured_templates', []))
            ->map(fn (string $slug) => $layouts->get($slug))
            ->filter(fn (?array $layout) => $layout && $layout['thumb'])
            ->map(fn (array $layout) => [
                'slug' => $layout['slug'],
                'title' => __($layout['name']),
                'thumb' => $layout['thumb'],
            ])
            ->values()
            ->all();

        return [$featured, $layouts->count()];
    }

    /**
     * Отзывы без распознанной ссылки на ролик выкидываем: карточка, по
     * которой ничего не играет, хуже, чем её отсутствие.
     *
     * @return list<array{embed: string, poster: ?string, name: string, role: ?string, city: ?string}>
     */
    private function videoReviews(): array
    {
        return collect(config('landing_home.video_reviews', []))
            ->map(fn (array $review) => [
                'embed' => VideoEmbed::url($review['url'] ?? null),
                'poster' => $review['poster'] ?? null,
                'name' => (string) ($review['name'] ?? ''),
                'role' => $review['role'] ?? null,
                'city' => $review['city'] ?? null,
            ])
            ->filter(fn (array $review) => $review['embed'] !== null)
            ->values()
            ->all();
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
