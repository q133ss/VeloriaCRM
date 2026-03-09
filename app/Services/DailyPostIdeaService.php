<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\Telegram\TelegramBotApiService;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class DailyPostIdeaService
{
    public function __construct(
        private readonly OpenAIService $openAI,
        private readonly NotificationService $notifications,
        private readonly TelegramBotApiService $telegram,
    ) {
    }

    /**
     * @return array{processed:int,sent:int,skipped:int,items:array<int, array<string, mixed>>}
     */
    public function dispatchEnabledIdeas(?int $userId = null): array
    {
        $query = User::query()
            ->with('setting')
            ->whereHas('plans', function ($planQuery) {
                $planQuery->whereIn('name', ['elite', 'Elite', 'ELITE']);
            })
            ->whereHas('setting', function ($settingQuery) {
                $settingQuery->where('daily_post_ideas_enabled', true);
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
                $result = $this->dispatchForUser($user);
                $items[] = $result;
                $sent++;
            } catch (Throwable $exception) {
                Log::warning('Daily post idea dispatch skipped.', [
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

    /**
     * @return array<string, mixed>
     */
    public function dispatchForUser(User $user): array
    {
        $setting = $user->setting;

        if (! $setting || ! $setting->daily_post_ideas_enabled) {
            throw new \RuntimeException('Daily ideas are disabled for this user.');
        }

        if (! $this->userHasEliteAccess($user)) {
            throw new \RuntimeException('Daily ideas are available only on Elite.');
        }

        $idea = $this->generateIdeaForUser($user, $setting);
        $delivery = $this->deliverIdea($user, $setting, $idea);

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'status' => 'sent',
            'delivery' => $delivery,
            'idea' => $idea,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function generateIdeaForUser(User $user, ?Setting $setting = null): array
    {
        $setting ??= $user->setting;

        $context = $this->buildContext($user, $setting);

        if (! $this->aiEnabled()) {
            return $this->fallbackIdea($context);
        }

        $prompt = <<<'PROMPT'
Ты помогаешь бьюти-специалисту придумывать одну ежедневную идею для контента.

Нужен не готовый длинный пост, а короткая сильная идея на сегодня:
- понятная тема
- что раскрыть в 2-4 предложениях
- для какого канала лучше подходит
- мягкий CTA

Учитывай бриф пользователя, его услуги и недавние записи. Идея должна быть практичной, без воды, без академичности и без слишком общего мотивационного текста. Ответ верни строго в JSON.
PROMPT;

        $schema = [
            'name' => 'daily_post_idea',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'idea' => ['type' => 'string'],
                    'channel' => ['type' => 'string'],
                    'cta' => ['type' => 'string'],
                ],
                'required' => ['title', 'idea', 'channel', 'cta'],
            ],
        ];

        try {
            $response = $this->openAI->respond($prompt, $context, [
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => $schema,
                ],
                'max_tokens' => 350,
            ]);

            $content = Arr::get($response, 'content');
            if (! $content) {
                return $this->fallbackIdea($context);
            }

            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            $title = trim((string) Arr::get($decoded, 'title', ''));
            $idea = trim((string) Arr::get($decoded, 'idea', ''));
            $channel = trim((string) Arr::get($decoded, 'channel', ''));
            $cta = trim((string) Arr::get($decoded, 'cta', ''));

            if ($title === '' || $idea === '' || $channel === '' || $cta === '') {
                return $this->fallbackIdea($context);
            }

            return [
                'title' => $title,
                'idea' => $idea,
                'channel' => $channel,
                'cta' => $cta,
            ];
        } catch (Throwable $exception) {
            Log::warning('Failed to generate daily post idea.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);

            return $this->fallbackIdea($context);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function deliverIdea(User $user, Setting $setting, array $idea): array
    {
        $channelPreference = (string) ($setting->daily_post_ideas_channel ?: 'both');
        $message = $this->formatIdeaMessage($idea);

        $deliveredTo = [];
        $fallbackToPlatform = false;

        if (in_array($channelPreference, ['platform', 'both'], true)) {
            $this->notifications->send($user->id, 'Идея для контента на сегодня', $message, '/notifications');
            $deliveredTo[] = 'platform';
        }

        if (in_array($channelPreference, ['telegram', 'both'], true)) {
            if ($user->telegram_id && $setting->telegram_bot_token) {
                $this->telegram->sendMessage(
                    $setting->telegram_bot_token,
                    $user->telegram_id,
                    "Идея для контента на сегодня\n\n" . $message
                );
                $deliveredTo[] = 'telegram';
            } else {
                $fallbackToPlatform = true;
            }
        }

        if ($fallbackToPlatform && ! in_array('platform', $deliveredTo, true)) {
            $this->notifications->send(
                $user->id,
                'Идея для контента на сегодня',
                $message . "\n\nTelegram не был настроен, поэтому идея сохранена внутри CRM.",
                '/notifications'
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

    protected function buildContext(User $user, ?Setting $setting): array
    {
        $services = Service::query()
            ->forUser($user->id)
            ->with('category:id,name')
            ->orderByDesc('base_price')
            ->limit(8)
            ->get()
            ->map(function (Service $service) {
                return [
                    'name' => $service->name,
                    'category' => $service->category?->name,
                    'price' => $service->base_price,
                    'duration_min' => $service->duration_min,
                ];
            })
            ->all();

        $recentOrders = Order::query()
            ->where('master_id', $user->id)
            ->where('scheduled_at', '>=', Carbon::now()->subDays(30))
            ->orderByDesc('scheduled_at')
            ->limit(12)
            ->get()
            ->map(function (Order $order) {
                $services = collect($order->services ?? [])
                    ->map(function ($item) {
                        if (is_array($item)) {
                            return Arr::get($item, 'name')
                                ?: Arr::get($item, 'title')
                                ?: Arr::get($item, 'service_name');
                        }

                        return is_string($item) ? $item : null;
                    })
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'scheduled_at' => optional($order->scheduled_at)?->toDateString(),
                    'status' => $order->status,
                    'services' => $services,
                    'total_price' => $order->total_price,
                ];
            })
            ->all();

        return [
            'date' => Carbon::now()->toDateString(),
            'user' => [
                'name' => $user->name,
                'timezone' => $user->timezone,
            ],
            'daily_idea_settings' => [
                'channel_preference' => $setting?->daily_post_ideas_channel ?: 'both',
                'brief' => $setting?->daily_post_ideas_preferences ?: 'Нет дополнительного брифа.',
            ],
            'services' => $services,
            'recent_orders' => $recentOrders,
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    protected function fallbackIdea(array $context): array
    {
        $services = collect($context['services'] ?? []);
        $topService = $services->first();
        $channel = (string) Arr::get($context, 'daily_idea_settings.channel_preference', 'both');
        $brief = trim((string) Arr::get($context, 'daily_idea_settings.brief', ''));

        $serviceName = Arr::get($topService, 'name', 'вашу популярную услугу');

        return [
            'title' => 'Разбор частого вопроса клиента',
            'idea' => sprintf(
                'Сделайте короткую идею о том, как клиенту понять, когда ему действительно нужна услуга «%s», и развейте один частый страх или миф. %s',
                $serviceName,
                $brief !== '' ? 'Сохраните ваш обычный тон: ' . $brief : ''
            ),
            'channel' => $channel === 'both' ? 'Telegram или платформа' : $channel,
            'cta' => 'В конце предложите написать вам или записаться на ближайшее свободное время.',
        ];
    }

    protected function formatIdeaMessage(array $idea): string
    {
        return trim(sprintf(
            "Тема: %s\nКуда: %s\nИдея: %s\nCTA: %s",
            $idea['title'] ?? '',
            $idea['channel'] ?? '',
            $idea['idea'] ?? '',
            $idea['cta'] ?? ''
        ));
    }

    protected function aiEnabled(): bool
    {
        return (bool) config('openai.api_key');
    }

    protected function userHasEliteAccess(User $user): bool
    {
        return $user->plans()
            ->whereIn('name', ['elite', 'Elite', 'ELITE'])
            ->where(function ($query) {
                $query
                    ->whereNull('plan_user.ends_at')
                    ->orWhere('plan_user.ends_at', '>', Carbon::now());
            })
            ->exists();
    }
}
