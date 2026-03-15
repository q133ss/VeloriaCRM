<?php

namespace App\Services;

use App\Models\LearningArticle;
use App\Models\LearningLesson;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\Telegram\TelegramBotApiService;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class UsefulDigestService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly TelegramBotApiService $telegram,
    ) {
    }

    public function buildOverviewPayload(User $user, string $locale): array
    {
        $services = Service::forUser($user->id)
            ->with('category')
            ->orderByDesc('id')
            ->get();

        $specialty = $this->resolveSpecialty($services);
        $keywords = $specialty['keywords'];

        $articles = LearningArticle::query()
            ->where('is_published', true)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        $lessons = LearningLesson::with('category')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $sortedArticles = $this->sortBySpecialtyMatch($articles, $keywords, function (LearningArticle $article) use ($locale) {
            return implode(' ', array_filter([
                $article->getTranslationAsString('title', $locale, 'en'),
                $article->getTranslationAsString('summary', $locale, 'en'),
                $article->topic,
            ]));
        });

        $sortedLessons = $this->sortBySpecialtyMatch($lessons, $keywords, function (LearningLesson $lesson) use ($locale) {
            return implode(' ', array_filter([
                $lesson->getTranslationAsString('title', $locale, 'en'),
                $lesson->getTranslationAsString('summary', $locale, 'en'),
                $lesson->category?->getTranslationAsString('title', $locale, 'en'),
            ]));
        });

        $setting = $user->setting ?? new Setting();
        $digest = $this->buildDigestData($user, $setting, $sortedArticles, $sortedLessons, $locale);

        $postCards = $sortedArticles
            ->take(12)
            ->map(fn (LearningArticle $article) => $this->transformArticle($article, $locale))
            ->values();

        $featuredPost = $postCards->firstWhere('is_featured', true) ?? $postCards->first();

        $posts = $postCards
            ->reject(fn (array $post) => $featuredPost && $post['id'] === $featuredPost['id'])
            ->values()
            ->all();

        return [
            'meta' => [
                'title' => 'Полезное',
                'subtitle' => 'Короткие статьи, идеи и важные изменения для работы без лишнего шума.',
                'specialty' => [
                    'label' => $specialty['label'],
                    'hint' => $specialty['hint'],
                ],
            ],
            'digest' => $digest,
            'preferences' => $this->preferencesPayload($user, $setting),
            'featured_post' => $featuredPost,
            'filters' => $this->buildTopicFilters($postCards),
            'posts' => $posts,
        ];
    }

    public function updatePreferences(User $user, array $data): array
    {
        $setting = Setting::firstOrNew(['user_id' => $user->id]);
        $setting->fill([
            'weekly_useful_digest_enabled' => (bool) ($data['enabled'] ?? false),
            'weekly_useful_digest_channel' => $data['channel'] ?? 'platform',
            'weekly_useful_digest_preferences' => $data['preferences'] ?? null,
        ]);
        $setting->save();

        return $this->preferencesPayload($user, $setting);
    }

    public function dispatchEnabledDigests(?int $userId = null): array
    {
        $query = User::query()
            ->with('setting')
            ->whereHas('plans', function ($planQuery) {
                $planQuery->whereIn('name', ['pro', 'Pro', 'PRO', 'elite', 'Elite', 'ELITE']);
            })
            ->whereHas('setting', function ($settingQuery) {
                $settingQuery->where('weekly_useful_digest_enabled', true);
            });

        if ($userId !== null) {
            $query->whereKey($userId);
        }

        $users = $query->get();
        $items = [];
        $sent = 0;
        $skipped = 0;

        foreach ($users as $user) {
            try {
                $items[] = $this->dispatchForUser($user);
                $sent++;
            } catch (Throwable $exception) {
                Log::warning('Weekly useful digest dispatch skipped.', [
                    'user_id' => $user->id,
                    'exception' => $exception->getMessage(),
                ]);

                $items[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'status' => 'skipped',
                    'reason' => $exception->getMessage(),
                ];
                $skipped++;
            }
        }

        return [
            'processed' => $users->count(),
            'sent' => $sent,
            'skipped' => $skipped,
            'items' => $items,
        ];
    }

    public function dispatchForUser(User $user): array
    {
        $setting = $user->setting;

        if (! $setting || ! $setting->weekly_useful_digest_enabled) {
            throw new \RuntimeException('Weekly useful digest is disabled for this user.');
        }

        if (! $this->userHasProAccess($user)) {
            throw new \RuntimeException('Weekly useful digest is available only on Pro and Elite.');
        }

        $locale = app()->getLocale();
        $payload = $this->buildOverviewPayload($user, $locale);
        $delivery = $this->deliverDigest($user, $setting, $payload['digest']);

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'status' => 'sent',
            'delivery' => $delivery,
            'digest_title' => $payload['digest']['title'],
        ];
    }

    public function sendTestDigest(User $user, string $locale): array
    {
        $setting = Setting::firstOrNew(['user_id' => $user->id]);

        if (! $this->userHasProAccess($user)) {
            throw new \RuntimeException('Weekly useful digest is available only on Pro and Elite.');
        }

        $payload = $this->buildOverviewPayload($user, $locale);
        $delivery = $this->deliverDigest($user, $setting, $payload['digest']);

        return [
            'delivery' => $delivery,
            'digest' => $payload['digest'],
        ];
    }

    protected function deliverDigest(User $user, Setting $setting, array $digest): array
    {
        $channelPreference = (string) ($setting->weekly_useful_digest_channel ?: 'platform');
        $message = $this->formatDigestMessage($digest);

        $deliveredTo = [];
        $fallbackToPlatform = false;

        if (in_array($channelPreference, ['platform', 'both'], true)) {
            $this->notifications->send($user->id, $digest['title'], $message, '/useful');
            $deliveredTo[] = 'platform';
        }

        if (in_array($channelPreference, ['telegram', 'both'], true)) {
            if ($user->telegram_id && $setting->telegram_bot_token) {
                $this->telegram->sendMessage(
                    $setting->telegram_bot_token,
                    $user->telegram_id,
                    $digest['title'] . "\n\n" . $message
                );
                $deliveredTo[] = 'telegram';
            } else {
                $fallbackToPlatform = true;
            }
        }

        if ($fallbackToPlatform && ! in_array('platform', $deliveredTo, true)) {
            $this->notifications->send(
                $user->id,
                $digest['title'],
                $message . "\n\nTelegram не был настроен, поэтому дайджест сохранён внутри CRM.",
                '/useful'
            );
            $deliveredTo[] = 'platform_fallback';
        }

        if (empty($deliveredTo)) {
            throw new \RuntimeException('No delivery channel is available for this user.');
        }

        return [
            'channel_preference' => $channelPreference,
            'delivered_to' => $deliveredTo,
        ];
    }

    protected function buildDigestData(
        User $user,
        Setting $setting,
        Collection $articles,
        Collection $lessons,
        string $locale
    ): array {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $topArticle = $articles->first();
        $topLesson = $lessons->first();

        $items = collect()
            ->merge($articles->take(2)->map(function (LearningArticle $article) use ($locale) {
                return [
                    'eyebrow' => $article->topic ?: 'Важно',
                    'title' => $article->getTranslationAsString('title', $locale, 'en'),
                    'summary' => $article->getTranslationAsString('summary', $locale, 'en'),
                    'action' => $this->extractArticleAction($article, $locale)['label'],
                ];
            }))
            ->merge($lessons->take(1)->map(function (LearningLesson $lesson) use ($locale) {
                return [
                    'eyebrow' => 'Попробовать',
                    'title' => $lesson->getTranslationAsString('title', $locale, 'en'),
                    'summary' => $lesson->getTranslationAsString('summary', $locale, 'en'),
                    'action' => 'Открыть разбор',
                ];
            }))
            ->filter(fn (array $item) => $item['title'] !== '')
            ->values()
            ->all();

        $preferences = trim((string) ($setting->weekly_useful_digest_preferences ?? ''));

        return [
            'badge' => 'Что важно на этой неделе',
            'title' => 'Подборка спокойной недели',
            'summary' => $this->buildDigestSummary($user, $topArticle, $topLesson, $preferences, $locale),
            'week_label' => sprintf(
                '%s - %s',
                $weekStart->locale($locale)->isoFormat('D MMMM'),
                $weekEnd->locale($locale)->isoFormat('D MMMM')
            ),
            'items' => $items,
        ];
    }

    protected function buildDigestSummary(
        User $user,
        ?LearningArticle $article,
        ?LearningLesson $lesson,
        string $preferences,
        string $locale
    ): string {
        $parts = [];

        if ($article) {
            $parts[] = 'Важный материал недели: ' . $article->getTranslationAsString('title', $locale, 'en') . '.';
        }

        if ($lesson) {
            $parts[] = 'Можно быстро внедрить: ' . $lesson->getTranslationAsString('title', $locale, 'en') . '.';
        }

        if ($preferences !== '') {
            $parts[] = 'Учли ваш фокус: ' . Str::limit($preferences, 90);
        }

        if (empty($parts)) {
            $parts[] = 'Мы собрали короткую подборку материалов и идей, чтобы вам не приходилось искать важное вручную.';
        }

        return implode(' ', $parts);
    }

    protected function formatDigestMessage(array $digest): string
    {
        $lines = [
            $digest['week_label'] ?? '',
            $digest['summary'] ?? '',
        ];

        foreach (Arr::get($digest, 'items', []) as $item) {
            $lines[] = '• ' . trim(($item['title'] ?? '') . ': ' . ($item['action'] ?? 'Открыть'));
        }

        return trim(implode("\n", array_filter($lines)));
    }

    protected function preferencesPayload(User $user, Setting $setting): array
    {
        $available = $this->userHasProAccess($user);

        return [
            'available' => $available,
            'enabled' => $available ? (bool) $setting->weekly_useful_digest_enabled : false,
            'channel' => $available ? ($setting->weekly_useful_digest_channel ?: 'platform') : 'platform',
            'preferences' => $available ? ($setting->weekly_useful_digest_preferences ?: '') : '',
            'required_plan' => 'pro',
            'upgrade_url' => url('/subscription'),
        ];
    }

    protected function resolveSpecialty(Collection $services): array
    {
        $category = $services
            ->filter(fn (Service $service) => $service->category)
            ->groupBy(fn (Service $service) => $service->category_id)
            ->sortByDesc(fn (Collection $group) => $group->count())
            ->map(fn (Collection $group) => $group->first()->category)
            ->first();

        if ($category) {
            return [
                'label' => $category->name,
                'hint' => 'Подборка подстроена под ваши услуги и похожие запросы клиентов.',
                'keywords' => $this->keywordBag([$category->name]),
            ];
        }

        $serviceNames = $services->pluck('name')->filter()->take(3)->values()->all();
        $label = ! empty($serviceNames) ? implode(', ', $serviceNames) : 'вашу специализацию';

        return [
            'label' => $label,
            'hint' => 'Показываем материалы, которые проще всего применить в работе уже сейчас.',
            'keywords' => $this->keywordBag($serviceNames),
        ];
    }

    protected function keywordBag(array $items): array
    {
        return collect($items)
            ->filter()
            ->flatMap(function (string $item) {
                return preg_split('/[\s,.;:!?()\/\\\\-]+/u', mb_strtolower($item)) ?: [];
            })
            ->map(fn (string $keyword) => trim($keyword))
            ->filter(fn (string $keyword) => mb_strlen($keyword) >= 3)
            ->unique()
            ->values()
            ->all();
    }

    protected function sortBySpecialtyMatch(Collection $items, array $keywords, callable $extractor): Collection
    {
        if (empty($keywords)) {
            return $items->values();
        }

        return $items
            ->sortByDesc(function ($item) use ($keywords, $extractor) {
                $haystack = mb_strtolower((string) $extractor($item));

                return collect($keywords)->sum(function (string $keyword) use ($haystack) {
                    return Str::contains($haystack, $keyword) ? 1 : 0;
                });
            })
            ->values();
    }

    protected function transformArticle(LearningArticle $article, string $locale): array
    {
        $action = $this->extractArticleAction($article, $locale);
        $rawTopic = $article->topic ?: 'general';

        return [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->getTranslationAsString('title', $locale, 'en'),
            'summary' => $article->getTranslationAsString('summary', $locale, 'en'),
            'topic' => $this->humanizeTopic($rawTopic),
            'topic_key' => Str::slug($rawTopic, '-'),
            'reading_time_minutes' => $article->reading_time_minutes,
            'source_url' => $article->source_url,
            'published_at' => optional($article->published_at)->toIso8601String(),
            'is_featured' => (bool) $article->is_featured,
            'content' => $article->getTranslation('content', $locale, 'en'),
            'action' => $action,
        ];
    }

    protected function extractArticleAction(LearningArticle $article, string $locale): array
    {
        $action = $article->getTranslation('action', $locale, 'en');

        if (is_array($action)) {
            return [
                'label' => (string) ($action['label'] ?? 'Открыть материал'),
                'url' => $action['url'] ?? $article->source_url,
            ];
        }

        return [
            'label' => is_string($action) && $action !== '' ? $action : 'Открыть материал',
            'url' => $article->source_url,
        ];
    }

    protected function buildTopicFilters(Collection $posts): array
    {
        return $posts
            ->map(function (array $post) {
                return [
                    'key' => $post['topic_key'],
                    'label' => $post['topic'],
                ];
            })
            ->unique('key')
            ->take(6)
            ->values()
            ->prepend([
                'key' => 'all',
                'label' => 'Все',
            ])
            ->all();
    }

    protected function humanizeTopic(string $topic): string
    {
        $normalized = Str::of($topic)->trim()->lower()->replace('_', '-')->value();

        return match ($normalized) {
            'legal', 'tax', 'taxes', 'compliance' => 'Налоги и право',
            'loyalty' => 'Лояльность',
            'retention' => 'Возврат клиентов',
            'marketing', 'content', 'promotion' => 'Маркетинг',
            'clients', 'client-care' => 'Клиенты',
            'business', 'finance' => 'Бизнес',
            'important', 'news', 'update' => 'Важно',
            'general', '' => 'Полезное',
            default => Str::headline(str_replace(['_', '-'], ' ', $topic)),
        };
    }

    protected function userHasProAccess(User $user): bool
    {
        return $user->plans()
            ->whereIn('name', ['pro', 'Pro', 'PRO', 'elite', 'Elite', 'ELITE'])
            ->where(function ($query) {
                $query
                    ->whereNull('plan_user.ends_at')
                    ->orWhere('plan_user.ends_at', '>', Carbon::now());
            })
            ->exists();
    }
}
