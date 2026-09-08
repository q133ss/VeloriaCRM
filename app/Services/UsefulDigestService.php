<?php

namespace App\Services;

use App\Models\LearningArticle;
use App\Models\LearningLesson;
use App\Models\Service;
use App\Models\Setting;
use App\Models\UsefulCategory;
use App\Models\User;
use App\Services\Telegram\TelegramBotApiService;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    /**
     * @param  array{search?: ?string, category?: ?string, unread?: bool}  $filters
     */
    public function buildOverviewPayload(User $user, string $locale, array $filters = []): array
    {
        $services = Service::forUser($user->id)
            ->with('category')
            ->orderByDesc('id')
            ->get();

        $specialty = $this->resolveSpecialty($services, $locale);
        $keywords = $specialty['keywords'];

        $articles = LearningArticle::query()
            ->with('usefulCategory')
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
            $category = $this->resolveArticleCategory($article, $locale);

            return implode(' ', array_filter([
                $article->getTranslationAsString('title', $locale, 'en'),
                $article->getTranslationAsString('summary', $locale, 'en'),
                $category['label'],
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

        $readAt = $this->readMarks($user);

        $allCards = $sortedArticles
            ->map(fn (LearningArticle $article) => $this->transformArticle($article, $locale, $keywords, $readAt))
            ->values();

        $postCards = $this->applyFilters($allCards, $filters);
        $isFiltered = $postCards->count() !== $allCards->count()
            || trim((string) ($filters['search'] ?? '')) !== ''
            || (($filters['category'] ?? 'all') !== 'all' && ($filters['category'] ?? null) !== null)
            || ! empty($filters['unread']);

        // «Статья недели» is a recommendation, not a search result: while the
        // master is looking for something specific it steps out of the way.
        $featuredPost = $isFiltered
            ? null
            : ($allCards->firstWhere('is_featured', true) ?? $allCards->first());

        $posts = $postCards
            ->reject(fn (array $post) => $featuredPost && $post['id'] === $featuredPost['id'])
            ->values()
            ->all();

        return [
            'meta' => [
                'title' => $this->copyForLocale($locale, 'Полезное', 'Useful'),
                'subtitle' => $this->copyForLocale(
                    $locale,
                    'Короткие статьи, идеи и важные изменения для работы без лишнего шума.',
                    'Short articles, ideas and important updates without extra noise.'
                ),
                'specialty' => [
                    'label' => $specialty['label'],
                    'hint' => $specialty['hint'],
                    'is_specific' => $specialty['is_specific'],
                ],
            ],
            'digest' => $digest,
            'preferences' => $this->preferencesPayload($user, $setting),
            'featured_post' => $featuredPost,
            'filters' => $this->buildTopicFilters($allCards, $locale),
            'posts' => $posts,
            'counts' => [
                'total' => $allCards->count(),
                'unread' => $allCards->reject(fn (array $post) => $post['is_read'])->count(),
                'shown' => count($posts) + ($featuredPost ? 1 : 0),
            ],
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
            ->whereHas('setting', function ($settingQuery) {
                $settingQuery->where('weekly_useful_digest_enabled', true);
            });

        if ($userId !== null) {
            $query->whereKey($userId);
        }

        // Was a seventh private copy of the tier check, written as a subquery on
        // plan names. One rule now, the same one the middleware and the settings
        // screen use.
        $users = $query->get()->filter(fn (User $user) => $user->hasProAccess())->values();
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
                $message . "\n\n" . $this->copyForLocale(app()->getLocale(), 'Telegram не был настроен, поэтому дайджест сохранён внутри CRM.', 'Telegram was not configured, so the digest was delivered inside the CRM.'),
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
                $category = $this->resolveArticleCategory($article, $locale);

                return [
                    'eyebrow' => $category['label'],
                    'title' => $article->getTranslationAsString('title', $locale, 'en'),
                    'summary' => $article->getTranslationAsString('summary', $locale, 'en'),
                    'action' => $this->extractArticleAction($article, $locale)['label'],
                ];
            }))
            ->merge($lessons->take(1)->map(function (LearningLesson $lesson) use ($locale) {
                return [
                    'eyebrow' => $this->copyForLocale($locale, 'Попробовать', 'Try next'),
                    'title' => $lesson->getTranslationAsString('title', $locale, 'en'),
                    'summary' => $lesson->getTranslationAsString('summary', $locale, 'en'),
                    'action' => $this->copyForLocale($locale, 'Открыть разбор', 'Open guide'),
                ];
            }))
            ->filter(fn (array $item) => $item['title'] !== '')
            ->values()
            ->all();

        $preferences = trim((string) ($setting->weekly_useful_digest_preferences ?? ''));

        return [
            'badge' => $this->copyForLocale($locale, 'Что важно на этой неделе', 'What matters this week'),
            'title' => $this->copyForLocale($locale, 'Подборка спокойной недели', 'A calm weekly digest'),
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
            $parts[] = $this->copyForLocale($locale, 'Важный материал недели:', 'Top useful post this week:') . ' ' . $article->getTranslationAsString('title', $locale, 'en') . '.';
        }

        if ($lesson) {
            $parts[] = $this->copyForLocale($locale, 'Можно быстро внедрить:', 'Quick idea to apply:') . ' ' . $lesson->getTranslationAsString('title', $locale, 'en') . '.';
        }

        if ($preferences !== '') {
            $parts[] = $this->copyForLocale($locale, 'Учли ваш фокус:', 'Included your focus:') . ' ' . Str::limit($preferences, 90);
        }

        if (empty($parts)) {
            $parts[] = $this->copyForLocale(
                $locale,
                'Мы собрали короткую подборку материалов и идей, чтобы вам не приходилось искать важное вручную.',
                'We prepared a short list of posts and ideas, so you do not have to search everything manually.'
            );
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
            $lines[] = '* ' . trim(($item['title'] ?? '') . ': ' . ($item['action'] ?? 'Open'));
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

    protected function resolveSpecialty(Collection $services, string $locale): array
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
                'hint' => $this->copyForLocale(
                    $locale,
                    'Подборка подстроена под ваши услуги и похожие запросы клиентов.',
                    'This feed is adapted to your services and similar client needs.'
                ),
                'keywords' => $this->keywordBag([$category->name]),
                'is_specific' => true,
            ];
        }

        $serviceNames = $services->pluck('name')->filter()->take(3)->values()->all();
        $label = ! empty($serviceNames)
            ? implode(', ', $serviceNames)
            : $this->copyForLocale($locale, 'вашу специализацию', 'your speciality');

        return [
            'label' => $label,
            'hint' => $this->copyForLocale(
                $locale,
                'Показываем материалы, которые проще всего применить в работе уже сейчас.',
                'Showing posts that are easiest to apply in your work right now.'
            ),
            'keywords' => $this->keywordBag($serviceNames),
            // Without services there is nothing to have matched, and the page
            // printed the words «вашу специализацию» in quotes as if it were
            // the name of one.
            'is_specific' => ! empty($serviceNames),
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

    /**
     * @param  array<int, string>  $keywords
     * @param  array<int, string>  $readAt  keyed by article id
     */
    protected function transformArticle(LearningArticle $article, string $locale, array $keywords = [], array $readAt = []): array
    {
        $action = $this->extractArticleAction($article, $locale);
        $category = $this->resolveArticleCategory($article, $locale);
        $content = $article->getTranslation('content', $locale, 'en');
        $blocks = $this->contentBlocks($content, $locale);

        return [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->getTranslationAsString('title', $locale, 'en'),
            'summary' => $article->getTranslationAsString('summary', $locale, 'en'),
            'topic' => $category['label'],
            'topic_key' => $category['key'],
            'category' => [
                'id' => $category['id'],
                'slug' => $category['key'],
                'name' => $category['label'],
            ],
            // Measured from the text rather than typed in beside it: three
            // bullet points were labelled «7 мин чтения».
            'reading_minutes' => $this->readingMinutes($blocks, $article),
            'source_url' => $article->source_url,
            'published_at' => optional($article->published_at)->toIso8601String(),
            'is_featured' => (bool) $article->is_featured,
            'is_read' => isset($readAt[$article->id]),
            'read_at' => $readAt[$article->id] ?? null,
            // The feed is already ordered by how well a post fits the master's
            // own services; saying so is what makes the order trustworthy.
            'matches_specialty' => $this->matchesSpecialty($article, $category, $locale, $keywords),
            'content' => $content,
            'content_blocks' => $blocks,
            'action' => $action,
        ];
    }

    /**
     * Turn the stored content into titled blocks.
     *
     * The keys of the content object used to be printed as headings, in English
     * and in capitals, so a Russian article opened with the word «SECTIONS».
     *
     * @return array<int, array{key: string, title: string, items: array<int, string>}>
     */
    protected function contentBlocks(mixed $content, string $locale): array
    {
        if (is_string($content) && trim($content) !== '') {
            return [[
                'key' => 'body',
                'title' => '',
                'items' => [trim($content)],
            ]];
        }

        if (! is_array($content)) {
            return [];
        }

        $titles = [
            'sections' => ['ru' => 'Что внутри', 'en' => "What's inside"],
            'steps' => ['ru' => 'Как сделать', 'en' => 'How to do it'],
            'ideas' => ['ru' => 'Идеи', 'en' => 'Ideas'],
            'structure' => ['ru' => 'Из чего собрать', 'en' => 'What to include'],
            'checklist' => ['ru' => 'Чек-лист', 'en' => 'Checklist'],
            'body' => ['ru' => '', 'en' => ''],
        ];

        $blocks = [];

        foreach ($content as $key => $value) {
            $items = collect(is_array($value) ? $value : [$value])
                ->map(fn ($item) => is_scalar($item) ? trim((string) $item) : '')
                ->filter()
                ->values()
                ->all();

            if ($items === []) {
                continue;
            }

            $title = $titles[$key][$locale] ?? $titles[$key]['ru'] ?? null;

            $blocks[] = [
                'key' => (string) $key,
                // An unknown key is not a heading anybody should read.
                'title' => $title ?? $this->copyForLocale($locale, 'Ещё', 'More'),
                'items' => $items,
            ];
        }

        return $blocks;
    }

    /**
     * @param  array<int, array{items: array<int, string>}>  $blocks
     */
    protected function readingMinutes(array $blocks, LearningArticle $article): int
    {
        $words = collect($blocks)
            ->flatMap(fn (array $block) => $block['items'])
            ->sum(fn (string $item) => count(preg_split('/\s+/u', trim($item)) ?: []));

        if ($words < 1) {
            return max(1, (int) $article->reading_time_minutes);
        }

        return max(1, (int) round($words / 140));
    }

    /**
     * @param  array{label: string}  $category
     * @param  array<int, string>  $keywords
     */
    protected function matchesSpecialty(LearningArticle $article, array $category, string $locale, array $keywords): bool
    {
        if ($keywords === []) {
            return false;
        }

        $haystack = mb_strtolower(implode(' ', array_filter([
            $article->getTranslationAsString('title', $locale, 'en'),
            $article->getTranslationAsString('summary', $locale, 'en'),
            $category['label'],
        ])));

        foreach ($keywords as $keyword) {
            if (Str::contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function readMarks(User $user): array
    {
        return DB::table('useful_article_reads')
            ->where('user_id', $user->id)
            ->pluck('read_at', 'learning_article_id')
            ->map(fn ($value) => (string) $value)
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array>  $posts
     * @param  array{search?: ?string, category?: ?string, unread?: bool}  $filters
     * @return \Illuminate\Support\Collection<int, array>
     */
    protected function applyFilters(Collection $posts, array $filters): Collection
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $category = $filters['category'] ?? null;

        return $posts
            ->when($category && $category !== 'all', fn (Collection $items) => $items->where('topic_key', $category))
            ->when(! empty($filters['unread']), fn (Collection $items) => $items->reject(fn (array $post) => $post['is_read']))
            ->when($search !== '', function (Collection $items) use ($search) {
                $needle = mb_strtolower($search);

                return $items->filter(function (array $post) use ($needle) {
                    $haystack = mb_strtolower(implode(' ', [
                        $post['title'],
                        $post['summary'],
                        $post['topic'],
                    ]));

                    return Str::contains($haystack, $needle);
                });
            })
            ->values();
    }

    protected function extractArticleAction(LearningArticle $article, string $locale): array
    {
        $action = $article->getTranslation('action', $locale, 'en');
        $defaultLabel = $this->copyForLocale($locale, 'Открыть материал', 'Open post');

        if (is_array($action)) {
            return [
                'label' => (string) ($action['label'] ?? $defaultLabel),
                'url' => $action['url'] ?? $article->source_url,
            ];
        }

        return [
            'label' => is_string($action) && $action !== '' ? $action : $defaultLabel,
            'url' => $article->source_url,
        ];
    }

    protected function buildTopicFilters(Collection $posts, string $locale): array
    {
        $filters = $posts
            ->map(function (array $post) {
                return [
                    'key' => $post['topic_key'],
                    'label' => $post['topic'],
                ];
            })
            ->unique('key')
            ->take(6)
            ->values();

        return $filters
            ->prepend([
                'key' => 'all',
                'label' => $this->copyForLocale($locale, 'Все', 'All'),
            ])
            ->all();
    }

    protected function resolveArticleCategory(LearningArticle $article, string $locale): array
    {
        $category = $article->usefulCategory;
        if ($category instanceof UsefulCategory) {
            return [
                'id' => $category->id,
                'key' => $category->slug,
                'label' => $category->getTranslationAsString('name', $locale, 'en'),
            ];
        }

        $fallbackKey = Str::slug((string) ($article->topic ?: 'general'));

        return [
            'id' => null,
            'key' => $fallbackKey !== '' ? $fallbackKey : 'general',
            'label' => $this->legacyTopicLabel((string) $article->topic, $locale),
        ];
    }

    protected function legacyTopicLabel(string $topic, string $locale): string
    {
        $normalized = Str::of($topic)->trim()->lower()->replace('_', '-')->value();

        return match ($normalized) {
            'legal', 'tax', 'taxes', 'compliance' => $this->copyForLocale($locale, 'Налоги и право', 'Taxes & legal'),
            'loyalty' => $this->copyForLocale($locale, 'Лояльность', 'Loyalty'),
            'retention' => $this->copyForLocale($locale, 'Возврат клиентов', 'Client retention'),
            'marketing', 'content', 'promotion' => $this->copyForLocale($locale, 'Маркетинг', 'Marketing'),
            'clients', 'client-care' => $this->copyForLocale($locale, 'Клиенты', 'Clients'),
            'business', 'finance' => $this->copyForLocale($locale, 'Бизнес', 'Business'),
            'general', '' => $this->copyForLocale($locale, 'Полезное', 'Useful'),
            default => Str::headline(str_replace(['_', '-'], ' ', $topic)),
        };
    }

    protected function copyForLocale(string $locale, string $ru, string $en): string
    {
        return Str::startsWith(Str::lower($locale), 'en') ? $en : $ru;
    }

    protected function userHasProAccess(User $user): bool
    {
        return $user->hasProAccess();
    }
}
