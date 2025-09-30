<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardAiService
{
    public function __construct(
        private readonly OpenAIService $openAI,
    ) {
    }

    public function suggestions(int $userId, CarbonInterface $date, array $context): array
    {
        $cacheKey = $this->cacheKey('suggestions', $userId, $date, $context);

        return Cache::remember($cacheKey, now()->addMinutes(45), function () use ($context) {
            if (! $this->aiEnabled()) {
                return $this->fallbackSuggestions($context);
            }

            $prompt = <<<'PROMPT'
Ты — персональный ассистент бьюти-специалиста. На основе контекста подскажи конкретные приоритетные действия на сегодня и завт
ра. Фокус на заполнении свободных слотов, работе с рисковыми клиентами и подготовке к сложным визитам. Ответ верни в JSON.
PROMPT;

            $schema = [
                'name' => 'dashboard_actions',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'suggestions' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'description' => ['type' => 'string'],
                                    'actions' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                        'minItems' => 0,
                                        'maxItems' => 3,
                                    ],
                                    'priority' => ['type' => 'string'],
                                ],
                                'required' => ['title', 'description'],
                            ],
                            'minItems' => 1,
                            'maxItems' => 6,
                        ],
                    ],
                    'required' => ['suggestions'],
                ],
            ];

            try {
                $response = $this->openAI->respond($prompt, $context, [
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => $schema,
                    ],
                    'max_tokens' => 700,
                ]);

                $content = Arr::get($response, 'content');
                if (! $content) {
                    return $this->fallbackSuggestions($context);
                }

                $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

                $suggestions = collect($decoded['suggestions'] ?? [])
                    ->map(function ($item) {
                        $title = trim((string) Arr::get($item, 'title', ''));
                        $description = trim((string) Arr::get($item, 'description', ''));
                        $actions = collect(Arr::get($item, 'actions', []))
                            ->map(fn ($action) => trim((string) $action))
                            ->filter()
                            ->values()
                            ->all();

                        if ($title === '' || $description === '') {
                            return null;
                        }

                        return [
                            'title' => $title,
                            'description' => $description,
                            'actions' => $actions,
                            'priority' => trim((string) Arr::get($item, 'priority', 'normal')),
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();

                if (empty($suggestions)) {
                    return $this->fallbackSuggestions($context);
                }

                return $suggestions;
            } catch (Throwable $exception) {
                Log::warning('Failed to generate dashboard AI suggestions.', [
                    'exception' => $exception->getMessage(),
                ]);

                return $this->fallbackSuggestions($context);
            }
        });
    }

    public function dailyTip(int $userId, CarbonInterface $date, array $context): array
    {
        $cacheKey = $this->cacheKey('tip', $userId, $date, $context);

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($context) {
            if (! $this->aiEnabled()) {
                return $this->fallbackTip($context);
            }

            $prompt = <<<'PROMPT'
Ты — коуч по развитию бьюти-бизнеса. На основе контекста предложи один короткий совет дня: как увеличить чек, удержать клиентов
 или использовать тренд. Ответ верни в JSON с полями text и source.
PROMPT;

            $schema = [
                'name' => 'dashboard_tip',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'text' => ['type' => 'string'],
                        'source' => ['type' => 'string'],
                    ],
                    'required' => ['text'],
                ],
            ];

            try {
                $response = $this->openAI->respond($prompt, $context, [
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => $schema,
                    ],
                    'max_tokens' => 250,
                ]);

                $content = Arr::get($response, 'content');
                if (! $content) {
                    return $this->fallbackTip($context);
                }

                $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

                $text = trim((string) Arr::get($decoded, 'text', ''));
                $source = trim((string) Arr::get($decoded, 'source', ''));

                if ($text === '') {
                    return $this->fallbackTip($context);
                }

                return [
                    'text' => $text,
                    'source' => $source !== '' ? $source : 'Veloria AI ассистент',
                ];
            } catch (Throwable $exception) {
                Log::warning('Failed to generate dashboard AI tip.', [
                    'exception' => $exception->getMessage(),
                ]);

                return $this->fallbackTip($context);
            }
        });
    }

    protected function fallbackSuggestions(array $context): array
    {
        $suggestions = [];

        $freeSlots = collect(Arr::get($context, 'signals.free_slots_tomorrow', []));
        $riskClients = collect(Arr::get($context, 'signals.high_risk_clients', []));
        $complexVisits = collect(Arr::get($context, 'signals.complex_visits', []));
        $birthdays = collect(Arr::get($context, 'signals.birthdays_tomorrow', []));
        $appointments = collect(Arr::get($context, 'appointments', []));

        if ($freeSlots->isNotEmpty()) {
            $firstSlot = $freeSlots->first();
            $targetClient = $appointments->sortByDesc(fn ($appt) => $appt['fit_score'] ?? 0)->first();

            $suggestions[] = [
                'title' => 'Свободные слоты на завтра',
                'description' => $targetClient
                    ? sprintf('У вас свободно %d слот(а) завтра. Предложите %s запись на %s.', $freeSlots->count(), $targetClient['client'] ?? 'лояльному клиенту', implode(', ', $targetClient['services'] ?? []))
                    : sprintf('У вас свободно %d слот(а) завтра. Наполните их персональными предложениями.', $freeSlots->count()),
                'actions' => [
                    $targetClient
                        ? sprintf('Отправить предложению клиенту %s на %s', $targetClient['client'], $firstSlot)
                        : sprintf('Создать акцию и отправить в %s', $firstSlot),
                ],
                'priority' => 'high',
            ];
        }

        if ($riskClients->isNotEmpty()) {
            $names = $riskClients->pluck('name')->implode(', ');
            $suggestions[] = [
                'title' => 'Клиенты в риске по неявке',
                'description' => sprintf('Обратите внимание на: %s. Усильте напоминание и предложите подтверждение.', $names),
                'actions' => ['Отправить двойное напоминание', 'Связаться по телефону'],
                'priority' => 'urgent',
            ];
        }

        if ($birthdays->isNotEmpty()) {
            $names = $birthdays->pluck('name')->implode(', ');
            $suggestions[] = [
                'title' => 'Готовимся к дням рождения',
                'description' => sprintf('Завтра день рождения у: %s. Подготовьте поздравление и персональный бонус.', $names),
                'actions' => ['Создать поздравительное предложение'],
                'priority' => 'normal',
            ];
        }

        if ($complexVisits->isNotEmpty()) {
            $complexNames = $complexVisits->pluck('client')->implode(', ');
            $suggestions[] = [
                'title' => 'Сложные визиты сегодня',
                'description' => sprintf('Подготовьте материалы для визитов: %s.', $complexNames),
                'actions' => ['Собрать чек-лист подготовки'],
                'priority' => 'high',
            ];
        }

        if (empty($suggestions)) {
            $metrics = Arr::get($context, 'metrics', []);
            $suggestions[] = [
                'title' => 'Проверьте базу клиентов',
                'description' => 'Нет срочных задач. Напомните о себе клиентам, которые давно не приходили, и обновите предложения.',
                'actions' => ['Запустить цепочку напоминаний', 'Обновить акции'],
                'priority' => 'normal',
            ];
        }

        return $suggestions;
    }

    protected function fallbackTip(array $context): array
    {
        $topService = collect(Arr::get($context, 'top_services', []))->first();
        if ($topService) {
            return [
                'text' => sprintf('Сегодня продвигайте услугу «%s» — её маржинальность сейчас самая высокая.', $topService['name'] ?? 'ваша ключевая услуга'),
                'source' => 'Анализ собственных показателей',
            ];
        }

        return [
            'text' => 'Напомните клиентам о дополнительных услугах или продуктах, чтобы повысить средний чек.',
            'source' => 'Veloria рекомендации',
        ];
    }

    protected function cacheKey(string $type, int $userId, CarbonInterface $date, array $context): string
    {
        return sprintf(
            'dashboard:ai:%s:%d:%s:%s',
            $type,
            $userId,
            $date->toDateString(),
            $this->contextHash($type, $context)
        );
    }

    protected function contextHash(string $type, array $context): string
    {
        $normalized = [
            'metrics' => Arr::only($context['metrics'] ?? [], [
                'revenue_today',
                'goal',
                'clients_booked',
                'clients_capacity',
                'average_ticket',
                'retention_rate',
            ]),
            'appointments' => collect($context['appointments'] ?? [])
                ->map(fn ($appt) => Arr::only($appt, ['client', 'time', 'indicator', 'status', 'risk_score']))
                ->values()
                ->all(),
            'signals' => [
                'free_slots' => collect(Arr::get($context, 'signals.free_slots_tomorrow', []))->values()->all(),
                'risk_clients' => collect(Arr::get($context, 'signals.high_risk_clients', []))
                    ->map(fn ($client) => Arr::only($client, ['id', 'name']))
                    ->values()
                    ->all(),
                'birthdays' => collect(Arr::get($context, 'signals.birthdays_tomorrow', []))
                    ->map(fn ($client) => Arr::only($client, ['id', 'name']))
                    ->values()
                    ->all(),
                'complex_visits' => collect(Arr::get($context, 'signals.complex_visits', []))
                    ->map(fn ($visit) => Arr::only($visit, ['client', 'time']))
                    ->values()
                    ->all(),
            ],
            'type' => $type,
        ];

        return md5(json_encode($normalized));
    }

    protected function aiEnabled(): bool
    {
        return (bool) config('openai.api_key');
    }
}
