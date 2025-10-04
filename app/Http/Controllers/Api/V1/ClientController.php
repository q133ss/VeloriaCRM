<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientFilterRequest;
use App\Http\Requests\ClientFormRequest;
use App\Http\Requests\SendReminderRequest;
use App\Models\Client;
use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Services\OpenAIService;
use App\Services\ReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ClientController extends Controller
{
    public function __construct(
        private readonly OpenAIService $openAI,
        private readonly ReminderService $reminders,
    ) {
    }

    public function index(ClientFilterRequest $request): JsonResponse
    {
        $userId = $this->currentUserId();
        $filters = $request->validated();

        $perPage = (int) ($filters['per_page'] ?? 12);
        $perPage = max(1, min($perPage, 100));
        $page = (int) ($filters['page'] ?? 1);
        $search = trim((string) ($filters['search'] ?? ''));
        $loyalty = $filters['loyalty'] ?? null;
        $sort = $filters['sort'] ?? 'name';
        $direction = strtolower($filters['direction'] ?? 'asc');

        $allowedSorts = ['name', 'last_visit_at', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $query = Client::where('user_id', $userId);

        if ($search !== '') {
            $digits = preg_replace('/[^0-9]+/', '', $search);

            $query->where(function ($builder) use ($search, $digits) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");

                if ($digits) {
                    $normalized = '+' . ltrim($digits, '+');
                    $builder->orWhere('phone', 'like', "%{$normalized}%");
                }
            });
        }

        if ($loyalty) {
            $query->where('loyalty_level', $loyalty);
        }

        $query->orderBy($sort, $direction);

        $clients = $query->paginate($perPage, ['*'], 'page', $page);
        $clients->getCollection()->transform(fn (Client $client) => $this->transformClient($client));

        $settings = $this->resolveUserSettings();

        return response()->json([
            'data' => $clients->items(),
            'meta' => [
                'pagination' => [
                    'current_page' => $clients->currentPage(),
                    'per_page' => $clients->perPage(),
                    'total' => $clients->total(),
                    'last_page' => $clients->lastPage(),
                ],
                'filters' => [
                    'search' => $search !== '' ? $search : null,
                    'loyalty' => $loyalty,
                    'sort' => $sort,
                    'direction' => $direction,
                ],
                'loyalty_options' => ['' => 'Все уровни'] + Client::loyaltyLevels(),
                'reminder_message' => optional($settings)->reminder_message,
                'integrations' => $this->reminderIntegrations($settings),
            ],
            'links' => [
                'first' => $clients->url(1),
                'last' => $clients->url($clients->lastPage()),
                'prev' => $clients->previousPageUrl(),
                'next' => $clients->nextPageUrl(),
            ],
        ]);
    }

    public function store(ClientFormRequest $request): JsonResponse
    {
        $validated = $this->normalizePayload($request->validated());
        $userId = $this->currentUserId();

        $client = Client::create(array_merge($validated, [
            'user_id' => $userId,
        ]));

        return response()->json([
            'data' => $this->transformClient($client->refresh()),
            'message' => 'Клиент успешно создан.',
        ], 201);
    }

    public function show(Client $client): JsonResponse
    {
        $this->ensureClientBelongsToCurrentUser($client);

        $client->refresh();
        $settings = $this->resolveUserSettings();

        return response()->json([
            'data' => $this->transformClient($client),
            'meta' => [
                'reminder_message' => optional($settings)->reminder_message,
                'loyalty_levels' => Client::loyaltyLevels(),
                'statistics' => $this->buildClientStatistics($client),
                'has_pro_access' => $this->userHasProAccess(),
                'risk' => $this->calculateNoShowRisk($client),
                'integrations' => $this->reminderIntegrations($settings),
            ],
        ]);
    }

    public function sendReminder(
        SendReminderRequest $request,
        Client $client,
    ): JsonResponse {
        $this->ensureClientBelongsToCurrentUser($client);

        $data = $request->validated();
        $settings = $this->resolveUserSettings();

        if (! $this->reminders->isChannelConfigured($data['channel'], $settings)) {
            $message = $data['channel'] === 'sms'
                ? 'Укажите API для SMS в настройках.'
                : 'Укажите API для WhatsApp в настройках.';

            return response()->json([
                'error' => [
                    'message' => $message,
                ],
            ], 422);
        }

        try {
            $this->reminders->send($data['channel'], $data['message'], $client, $settings);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'error' => [
                    'message' => $exception->getMessage(),
                ],
            ], 422);
        } catch (Throwable $exception) {
            Log::error('Не удалось отправить напоминание клиенту.', [
                'client_id' => $client->id,
                'channel' => $data['channel'],
                'exception' => $exception,
            ]);

            return response()->json([
                'error' => [
                    'message' => 'Не удалось отправить напоминание. Попробуйте позже.',
                ],
            ], 500);
        }

        return response()->json([
            'message' => 'Напоминание отправлено.',
        ]);
    }

    public function analytics(Client $client): JsonResponse
    {
        $this->ensureClientBelongsToCurrentUser($client);

        if (! $this->userHasProAccess()) {
            return response()->json([
                'error' => [
                    'code' => 'feature_unavailable',
                    'message' => 'Аналитика доступна только в тарифах PRO и Elite.',
                ],
            ], 403);
        }

        $history = $this->fetchClientOrders($client);

        $totalVisits = $history->count();
        $completedVisits = $history->filter(fn (Order $order) => $order->status === 'completed')->count();

        if ($totalVisits < 2 || $completedVisits === 0) {
            return response()->json([
                'error' => [
                    'code' => 'not_enough_data',
                    'message' => 'Недостаточно данных по визитам клиента для построения аналитики.',
                ],
            ], 422);
        }

        $cacheKey = $this->clientAnalyticsCacheKey($client, $history);

        $payload = Cache::remember($cacheKey, now()->addHours(12), function () use ($client, $history) {
            $metrics = $this->buildClientAnalyticsMetrics($history);

            if ($client->loyalty_level) {
                $metrics['loyalty_level'] = $client->loyalty_level;
            }

            $insights = $this->generateClientAnalyticsInsights($client, $metrics, $history);

            return [
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'phone' => $client->phone,
                    'email' => $client->email,
                    'profile' => [
                        'loyalty_level' => $client->loyalty_level,
                        'tags' => $client->tags ?? [],
                        'allergies' => $client->allergies ?? [],
                        'preferences' => $client->preferences ?? [],
                        'notes' => $client->notes,
                    ],
                ],
                'metrics' => $metrics,
                'insights' => $insights,
            ];
        });

        return response()->json($payload);
    }

    public function recommendations(Client $client): JsonResponse
    {
        $this->ensureClientBelongsToCurrentUser($client);

        if (! $this->userHasProAccess()) {
            return response()->json([
                'error' => [
                    'code' => 'feature_unavailable',
                    'message' => 'Рекомендации доступны только в тарифах PRO и Elite.',
                ],
            ], 403);
        }

        $services = $this->getUserServices();
        $recommendations = $this->buildRecommendedServices($client, $services);

        return response()->json([
            'recommendations' => $this->serializeRecommendations($recommendations),
        ]);
    }

    public function update(ClientFormRequest $request, Client $client): JsonResponse
    {
        $this->ensureClientBelongsToCurrentUser($client);

        $validated = $this->normalizePayload($request->validated(), $client);
        $client->update($validated);

        return response()->json([
            'data' => $this->transformClient($client->refresh()),
            'message' => 'Данные клиента обновлены.',
        ]);
    }

    public function destroy(Client $client): JsonResponse
    {
        $this->ensureClientBelongsToCurrentUser($client);
        $client->delete();

        return response()->json([
            'message' => 'Клиент удалён.',
        ]);
    }

    public function options(): JsonResponse
    {
        $userId = $this->currentUserId();

        $collection = Client::where('user_id', $userId)->get(['tags', 'allergies', 'preferences']);

        $tags = $this->collectSuggestions($collection, 'tags');
        $allergies = $this->collectSuggestions($collection, 'allergies');
        $preferenceKeys = $collection->flatMap(function (Client $client) {
            $preferences = $client->preferences ?? [];

            if (! is_array($preferences)) {
                return [];
            }

            if (array_is_list($preferences)) {
                return collect($preferences)
                    ->map(fn ($item) => is_string($item) ? trim($item) : null)
                    ->filter()
                    ->values();
            }

            return collect($preferences)
                ->map(function ($value, $key) {
                    if (! is_string($key)) {
                        return null;
                    }

                    $trimmed = trim($key);

                    return $trimmed !== '' ? $trimmed : null;
                })
                ->filter()
                ->values();
        })->unique()->values()->all();

        return response()->json([
            'loyalty_levels' => Client::loyaltyLevels(),
            'tag_suggestions' => $tags,
            'allergy_suggestions' => $allergies,
            'preference_suggestions' => $preferenceKeys,
        ]);
    }

    protected function normalizePayload(array $payload, ?Client $client = null): array
    {
        $normalized = $payload;

        $normalized['tags'] = $this->normalizeList(Arr::get($payload, 'tags'));
        $normalized['allergies'] = $this->normalizeList(Arr::get($payload, 'allergies'));
        $normalized['preferences'] = $this->normalizePreferences(Arr::get($payload, 'preferences'));

        $normalized['birthday'] = Arr::has($payload, 'birthday') && $payload['birthday']
            ? Carbon::parse($payload['birthday'])->toDateString()
            : null;

        $normalized['last_visit_at'] = Arr::has($payload, 'last_visit_at') && $payload['last_visit_at']
            ? Carbon::parse($payload['last_visit_at'])
            : null;

        foreach (['email', 'loyalty_level', 'notes'] as $field) {
            if (Arr::has($payload, $field)) {
                $value = trim((string) ($payload[$field] ?? ''));
                $normalized[$field] = $value !== '' ? $value : null;
            }
        }

        $normalized['phone'] = trim((string) ($payload['phone'] ?? ''));
        if ($normalized['phone'] === '') {
            $normalized['phone'] = $client?->phone ?? '';
        }

        return $normalized;
    }

    protected function normalizeList(null|array|string $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $items = preg_split('/[\n,]+/', $value) ?: [];

            $normalized = collect($items)
                ->map(fn ($item) => trim($item))
                ->filter()
                ->values()
                ->all();

            return $normalized ?: null;
        }

        if (is_array($value)) {
            $normalized = collect($value)
                ->map(function ($item) {
                    if (is_string($item)) {
                        $trimmed = trim($item);

                        return $trimmed !== '' ? $trimmed : null;
                    }

                    return null;
                })
                ->filter()
                ->values()
                ->all();

            return $normalized ?: null;
        }

        return null;
    }

    protected function normalizePreferences(null|array|string $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $lines = preg_split('/[\n]+/', $value) ?: [];

            $preferences = collect($lines)
                ->map(function ($line) {
                    $line = trim($line);

                    if ($line === '') {
                        return null;
                    }

                    $parts = array_map('trim', explode(':', $line, 2));

                    if (count($parts) === 2 && $parts[0] !== '') {
                        return [$parts[0] => $parts[1]];
                    }

                    return $line;
                })
                ->filter()
                ->values();

            if ($preferences->isEmpty()) {
                return null;
            }

            if ($preferences->every(fn ($item) => is_array($item) && ! array_is_list($item))) {
                return $preferences->reduce(function ($carry, $item) {
                    return array_merge($carry, $item);
                }, []);
            }

            return $preferences
                ->map(fn ($item) => is_string($item) ? $item : null)
                ->filter()
                ->values()
                ->all();
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                $normalized = collect($value)
                    ->map(fn ($item) => is_string($item) ? trim($item) : null)
                    ->filter()
                    ->values()
                    ->all();

                return $normalized ?: null;
            }

            $normalized = collect($value)
                ->mapWithKeys(function ($val, $key) {
                    if (! is_string($key)) {
                        return [];
                    }

                    $trimmedKey = trim($key);
                    $trimmedVal = is_string($val) ? trim($val) : $val;

                    if ($trimmedKey === '') {
                        return [];
                    }

                    return [$trimmedKey => $trimmedVal];
                })
                ->all();

            return $normalized ?: null;
        }

        return null;
    }

    protected function transformClient(Client $client): array
    {
        $client->refresh();

        $birthday = $client->birthday ? $client->birthday->copy() : null;
        $lastVisit = $client->last_visit_at ? $client->last_visit_at->copy() : null;

        return [
            'id' => $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
            'email' => $client->email,
            'birthday' => $birthday?->toDateString(),
            'birthday_formatted' => $birthday?->isoFormat('D MMMM YYYY'),
            'tags' => $client->tags ?? [],
            'allergies' => $client->allergies ?? [],
            'preferences' => $client->preferences ?? [],
            'notes' => $client->notes,
            'last_visit_at' => $lastVisit?->toIso8601String(),
            'last_visit_at_formatted' => $lastVisit?->format('d.m.Y H:i'),
            'last_visit_at_local' => $lastVisit?->format('Y-m-d\TH:i'),
            'loyalty_level' => $client->loyalty_level,
            'loyalty_label' => $client->loyalty_level
                ? (Client::loyaltyLevels()[$client->loyalty_level] ?? ucfirst($client->loyalty_level))
                : null,
            'created_at' => $client->created_at?->toIso8601String(),
            'created_at_formatted' => $client->created_at?->format('d.m.Y H:i'),
            'updated_at' => $client->updated_at?->toIso8601String(),
            'available_channels' => $this->availableChannels($client),
        ];
    }

    protected function availableChannels(Client $client): array
    {
        $channels = [];

        if ($client->phone) {
            $channels[] = ['key' => 'sms', 'label' => 'SMS'];
            $channels[] = ['key' => 'whatsapp', 'label' => 'WhatsApp'];
        }

        return $channels;
    }

    protected function reminderIntegrations(?Setting $settings): array
    {
        return [
            'sms' => $this->reminders->isSmsConfigured($settings),
            'whatsapp' => $this->reminders->isWhatsappConfigured($settings),
        ];
    }

    protected function collectSuggestions(Collection $collection, string $field): array
    {
        return $collection->flatMap(function (Client $client) use ($field) {
            $values = $client->{$field} ?? [];

            if (! is_array($values)) {
                return [];
            }

            return collect($values)
                ->map(function ($value) {
                    if (is_string($value)) {
                        $trimmed = trim($value);

                        return $trimmed !== '' ? $trimmed : null;
                    }

                    return null;
                })
                ->filter()
                ->values();
        })->unique()->values()->all();
    }

    protected function buildClientStatistics(Client $client): array
    {
        $orders = $this->fetchClientOrders($client);
        $metrics = $orders->isEmpty() ? [
            'total_visits' => 0,
            'completed_visits' => 0,
            'upcoming_visits' => 0,
            'cancelled_visits' => 0,
            'no_show_visits' => 0,
            'lifetime_value' => 0.0,
            'average_check' => 0.0,
            'average_visit_interval_days' => null,
            'last_visit_at' => null,
            'next_visit_at' => null,
            'favorite_services' => [],
        ] : $this->buildClientAnalyticsMetrics($orders);

        $masterId = $this->currentUserId();

        $upcomingOrder = Order::where('master_id', $masterId)
            ->where('client_id', $client->id)
            ->where('scheduled_at', '>', Carbon::now())
            ->orderBy('scheduled_at')
            ->first();

        $lastVisitOrder = $orders->sortByDesc(fn (Order $order) => $order->scheduled_at)->first();

        $recentServices = $orders
            ->take(6)
            ->flatMap(function (Order $order) {
                $scheduled = optional($order->scheduled_at)?->format('d.m.Y H:i');

                return collect($order->services ?? [])->map(function ($service) use ($scheduled) {
                    return [
                        'name' => $service['name'] ?? 'Услуга',
                        'price' => isset($service['price']) ? (float) $service['price'] : null,
                        'performed_at' => $scheduled,
                    ];
                });
            })
            ->take(6)
            ->values();

        $lastNinetyDaysRevenue = $orders
            ->filter(fn (Order $order) => $order->status === 'completed' && $order->scheduled_at && $order->scheduled_at->gt(Carbon::now()->subDays(90)))
            ->sum(fn (Order $order) => (float) ($order->total_price ?? 0));

        $averageDuration = $orders
            ->filter(fn (Order $order) => $order->status === 'completed' && $order->duration)
            ->avg(fn (Order $order) => (int) $order->duration);

        $retentionScore = 0;
        if ($metrics['total_visits'] > 0) {
            $retentionScore = round(min(100, (
                ($metrics['completed_visits'] / max(1, $metrics['total_visits'])) * 60 +
                max(0, 40 - (($metrics['average_visit_interval_days'] ?? 30) - 30))
            )));
        }

        $favoriteServices = $metrics['favorite_services'] ?? [];

        return [
            'total_orders' => $metrics['total_visits'] ?? 0,
            'completed_orders' => $metrics['completed_visits'] ?? 0,
            'no_show_orders' => $metrics['no_show_visits'] ?? 0,
            'cancelled_orders' => $metrics['cancelled_visits'] ?? 0,
            'lifetime_value' => $metrics['lifetime_value'] ?? 0.0,
            'average_check' => $metrics['average_check'] ?? 0.0,
            'average_interval_days' => $metrics['average_visit_interval_days'],
            'average_duration' => $averageDuration ? round($averageDuration) : null,
            'retention_score' => $retentionScore,
            'upcoming_visit_at' => $upcomingOrder?->scheduled_at?->toIso8601String(),
            'upcoming_visit_formatted' => $upcomingOrder?->scheduled_at?->format('d.m.Y H:i'),
            'last_visit_from_orders' => optional($lastVisitOrder?->scheduled_at)?->toIso8601String(),
            'last_visit_from_orders_formatted' => optional($lastVisitOrder?->scheduled_at)?->format('d.m.Y H:i'),
            'favorite_services' => $favoriteServices,
            'recent_services' => $recentServices->toArray(),
            'spend_last_90_days' => round((float) $lastNinetyDaysRevenue, 2),
        ];
    }

    protected function calculateNoShowRisk(Client $client): array
    {
        $orders = $this->fetchClientOrders($client);

        if ($orders->isEmpty()) {
            return [
                'level' => 'low',
                'label' => 'Низкий риск',
                'score' => 10,
                'signals' => ['Недостаточно данных, используйте напоминания для повышения удержания.'],
                'suggestions' => ['Настройте автонапоминания хотя бы за сутки до визита.'],
            ];
        }

        $total = $orders->count();
        $noShows = $orders->where('status', 'no_show');
        $cancelled = $orders->where('status', 'cancelled');
        $rescheduled = $orders->sum(fn (Order $order) => (int) ($order->reschedule_count ?? 0));

        $score = 0;
        $signals = [];
        $suggestions = [];

        if ($noShows->isNotEmpty()) {
            $ratio = $noShows->count() / $total;
            $score += min(40, round($ratio * 100));
            $signals[] = 'Были пропуски визитов без предупреждения.';
            $suggestions[] = 'Запросите предоплату или подтверждение за день до визита.';
        }

        if ($cancelled->isNotEmpty()) {
            $ratio = $cancelled->count() / $total;
            $score += min(25, round($ratio * 80));
            $signals[] = 'Частые отмены записей.';
            $suggestions[] = 'Позвоните клиенту и уточните, что мешает приходить вовремя.';
        }

        if ($rescheduled > 0) {
            $score += min(15, $rescheduled * 3);
            $signals[] = 'Переносит встречи (общее количество переносов: ' . $rescheduled . ').';
            $suggestions[] = 'Предложите гибкие слоты и напомните о политике переносов.';
        }

        $lastVisit = $orders
            ->filter(fn (Order $order) => $order->status === 'completed' && $order->scheduled_at)
            ->sortByDesc(fn (Order $order) => $order->scheduled_at)
            ->first();

        if ($lastVisit && $lastVisit->scheduled_at) {
            $daysAgo = $lastVisit->scheduled_at->diffInDays(Carbon::now());

            if ($daysAgo > 120) {
                $score += 20;
                $signals[] = 'Давно не было завершённых визитов (более 4 месяцев).';
                $suggestions[] = 'Пришлите персональное предложение на повторное посещение.';
            } elseif ($daysAgo > 75) {
                $score += 12;
                $signals[] = 'Перерыв между посещениями превышает норму.';
                $suggestions[] = 'Напомните о регулярности и предложите бонус за возврат.';
            }
        }

        if ($client->loyalty_level && in_array($client->loyalty_level, ['gold', 'platinum', 'vip', 'ambassador'], true)) {
            $score -= 10;
            $signals[] = 'Высокий уровень лояльности помогает удержанию.';
        }

        $score = max(0, min(100, $score));

        if ($score <= 30) {
            $level = 'low';
            $label = 'Низкий риск';
        } elseif ($score <= 60) {
            $level = 'medium';
            $label = 'Средний риск';
        } else {
            $level = 'high';
            $label = 'Высокий риск';
        }

        $signals = array_values(array_unique($signals));
        $suggestions = array_values(array_unique($suggestions));

        return [
            'level' => $level,
            'label' => $label,
            'score' => $score,
            'signals' => $signals,
            'suggestions' => $suggestions,
        ];
    }

    protected function fetchClientOrders(Client $client, ?int $limit = null): Collection
    {
        $query = Order::where('master_id', $this->currentUserId())
            ->where('client_id', $client->id)
            ->orderByDesc('scheduled_at');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    protected function clientAnalyticsCacheKey(Client $client, Collection $history): string
    {
        $historySignature = $history->map(function (Order $order) {
            return $order->id . ':' . optional($order->updated_at)->timestamp . ':' . optional($order->scheduled_at)->timestamp;
        })->join('|');

        $profileUpdated = optional($client->updated_at)->timestamp ?? 0;

        return 'clients:analytics:' . $this->currentUserId() . ':' . $client->id . ':' . sha1($historySignature . '|' . $profileUpdated);
    }

    protected function buildClientAnalyticsMetrics(Collection $orders): array
    {
        $totalVisits = $orders->count();
        $completedOrders = $orders->filter(fn (Order $order) => $order->status === 'completed');
        $upcomingOrders = $orders->filter(fn (Order $order) => in_array($order->status, ['new', 'confirmed', 'in_progress']));
        $cancelledOrders = $orders->filter(fn (Order $order) => $order->status === 'cancelled');
        $noShowOrders = $orders->filter(fn (Order $order) => $order->status === 'no_show');

        $totalRevenue = $completedOrders->reduce(function ($carry, Order $order) {
            return $carry + (float) ($order->total_price ?? 0);
        }, 0.0);

        $averageCheck = $completedOrders->count() > 0
            ? round($totalRevenue / $completedOrders->count(), 2)
            : 0.0;

        $completedWithDates = $completedOrders
            ->filter(fn (Order $order) => $order->scheduled_at)
            ->sortBy(fn (Order $order) => $order->scheduled_at)
            ->values();

        $intervals = [];
        for ($i = 1; $i < $completedWithDates->count(); $i++) {
            $previous = $completedWithDates->get($i - 1)->scheduled_at;
            $current = $completedWithDates->get($i)->scheduled_at;

            if ($previous && $current) {
                $intervals[] = $previous->diffInDays($current);
            }
        }

        $averageInterval = ! empty($intervals)
            ? round(array_sum($intervals) / count($intervals), 1)
            : null;

        $lastVisit = $completedOrders->sortByDesc(fn (Order $order) => $order->scheduled_at)->first();
        $nextVisit = $upcomingOrders->sortBy(fn (Order $order) => $order->scheduled_at)->first();

        $serviceStats = [];

        foreach ($orders as $historyOrder) {
            foreach ((array) ($historyOrder->services ?? []) as $service) {
                $name = $service['name'] ?? ('Услуга #' . ($service['id'] ?? '?'));

                if (! isset($serviceStats[$name])) {
                    $serviceStats[$name] = [
                        'name' => $name,
                        'count' => 0,
                        'total_spent' => 0.0,
                    ];
                }

                $serviceStats[$name]['count']++;
                $serviceStats[$name]['total_spent'] += isset($service['price']) ? (float) $service['price'] : 0.0;
            }
        }

        $favoriteServices = collect($serviceStats)
            ->map(function (array $stat) {
                $count = max(1, $stat['count']);

                return [
                    'name' => $stat['name'],
                    'count' => $stat['count'],
                    'average_price' => round($stat['total_spent'] / $count, 2),
                ];
            })
            ->sort(function ($a, $b) {
                if ($a['count'] === $b['count']) {
                    return $b['average_price'] <=> $a['average_price'];
                }

                return $b['count'] <=> $a['count'];
            })
            ->values()
            ->take(3)
            ->toArray();

        return [
            'total_visits' => $totalVisits,
            'completed_visits' => $completedOrders->count(),
            'upcoming_visits' => $upcomingOrders->count(),
            'cancelled_visits' => $cancelledOrders->count(),
            'no_show_visits' => $noShowOrders->count(),
            'lifetime_value' => round($totalRevenue, 2),
            'average_check' => $averageCheck,
            'average_visit_interval_days' => $averageInterval,
            'last_visit_at' => optional($lastVisit?->scheduled_at)->toIso8601String(),
            'next_visit_at' => optional($nextVisit?->scheduled_at)->toIso8601String(),
            'favorite_services' => $favoriteServices,
        ];
    }

    protected function fallbackAnalyticsInsights(array $metrics): array
    {
        $parts = [
            'Всего визитов: ' . ($metrics['total_visits'] ?? 0),
            'Завершено: ' . ($metrics['completed_visits'] ?? 0),
        ];

        if (! empty($metrics['average_check'])) {
            $parts[] = 'Средний чек: ' . number_format((float) $metrics['average_check'], 0, ',', ' ') . ' ₽';
        }

        if (! empty($metrics['last_visit_at'])) {
            try {
                $parts[] = 'Последний визит: ' . Carbon::parse($metrics['last_visit_at'])->format('d.m.Y');
            } catch (Throwable) {
                // ignore invalid date
            }
        }

        $summary = implode('. ', $parts) . '.';

        $riskFlags = [];

        if (($metrics['cancelled_visits'] ?? 0) > 0) {
            $riskFlags[] = 'Были отмены — уточните причину и подтвердите следующий визит.';
        }

        if (($metrics['no_show_visits'] ?? 0) > 0) {
            $riskFlags[] = 'Бывали пропуски визитов. Усильте напоминания или попросите предоплату.';
        }

        if (($metrics['average_visit_interval_days'] ?? 0) && $metrics['average_visit_interval_days'] > 45) {
            $riskFlags[] = 'Интервал между визитами увеличивается — клиент может уйти к конкурентам.';
        }

        $recommendations = [];

        if (($metrics['upcoming_visits'] ?? 0) === 0) {
            $recommendations[] = [
                'title' => 'Запланируйте следующий визит',
                'action' => 'Свяжитесь с клиентом и предложите время, пока впечатление от услуги свежее.',
            ];
        }

        if (! empty($metrics['favorite_services'][0]['name'])) {
            $recommendations[] = [
                'title' => 'Подчеркните любимые услуги',
                'action' => 'Напомните о ' . $metrics['favorite_services'][0]['name'] . ' и предложите комплиментарный уход.',
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'title' => 'Поддерживайте контакт',
                'action' => 'Отправьте персональный совет по уходу или бонус к следующему визиту.',
            ];
        }

        return [
            'summary' => $summary,
            'risk_flags' => array_values(array_unique($riskFlags)),
            'recommendations' => $recommendations,
        ];
    }

    protected function generateClientAnalyticsInsights(Client $client, array $metrics, Collection $orders): array
    {
        $insights = $this->fallbackAnalyticsInsights($metrics);

        if (! $this->aiAvailable()) {
            return $insights;
        }

        try {
            $context = [
                'client' => [
                    'name' => $client->name,
                    'loyalty_level' => $client->loyalty_level,
                    'tags' => $client->tags ?? [],
                    'preferences' => $client->preferences ?? [],
                    'allergies' => $client->allergies ?? [],
                ],
                'metrics' => $metrics,
                'order_history' => $orders->take(12)->map(function (Order $order) {
                    return [
                        'scheduled_at' => optional($order->scheduled_at)->toIso8601String(),
                        'status' => $order->status,
                        'total_price' => $order->total_price !== null ? (float) $order->total_price : null,
                        'services' => collect($order->services ?? [])->map(function ($service) {
                            return Arr::only($service, ['id', 'name', 'price', 'duration']);
                        })->values()->toArray(),
                    ];
                })->values()->toArray(),
            ];

            if ($client->notes) {
                $context['client_notes'] = Str::limit($client->notes, 500);
            }

            $prompt = <<<'PROMPT'
Вы — аналитик в CRM для бьюти-мастера. Используйте метрики и историю визитов, чтобы кратко описать состояние клиента,
пометить риски (если они есть) и предложить конкретные следующие шаги для удержания и увеличения выручки. Пишите по-русски,
с фокусом на заботу о клиенте и бизнес-задачи мастера.
PROMPT;

            $response = $this->openAI->respond($prompt, $context, [
                'temperature' => 0.3,
                'max_tokens' => 750,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'client_analytics',
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'summary' => ['type' => 'string'],
                                'risk_flags' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                                'recommendations' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'title' => ['type' => 'string'],
                                            'action' => ['type' => 'string'],
                                        ],
                                        'required' => ['title', 'action'],
                                    ],
                                ],
                            ],
                            'required' => ['summary'],
                        ],
                    ],
                ],
            ]);

            $payload = json_decode($response['content'] ?? '', true);

            if (is_array($payload)) {
                if (! empty($payload['summary']) && is_string($payload['summary'])) {
                    $insights['summary'] = trim($payload['summary']);
                }

                if (! empty($payload['risk_flags']) && is_array($payload['risk_flags'])) {
                    $insights['risk_flags'] = collect($payload['risk_flags'])
                        ->filter(fn ($flag) => is_string($flag) && $flag !== '')
                        ->values()
                        ->all();
                }

                if (! empty($payload['recommendations']) && is_array($payload['recommendations'])) {
                    $insights['recommendations'] = collect($payload['recommendations'])
                        ->map(function ($item) {
                            if (! is_array($item)) {
                                return null;
                            }

                            $title = Arr::get($item, 'title');
                            $action = Arr::get($item, 'action');

                            if (! is_string($title) || ! is_string($action) || $title === '' || $action === '') {
                                return null;
                            }

                            return [
                                'title' => trim($title),
                                'action' => trim($action),
                            ];
                        })
                        ->filter()
                        ->values()
                        ->all();
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Failed to generate client analytics insights.', [
                'user_id' => $this->currentUserId(),
                'client_id' => $client->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        return $insights;
    }

    protected function buildRecommendationContext(Client $client, Collection $services, Collection $history): array
    {
        return [
            'client' => [
                'name' => $client->name,
                'loyalty_level' => $client->loyalty_level,
                'tags' => $client->tags ?? [],
                'preferences' => $client->preferences ?? [],
                'allergies' => $client->allergies ?? [],
            ],
            'available_services' => $services->map(function (Service $service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => (float) $service->base_price,
                    'duration' => (int) $service->duration_min,
                ];
            })->values()->toArray(),
            'history' => $history->take(12)->map(function (Order $order) {
                return [
                    'status' => $order->status,
                    'scheduled_at' => optional($order->scheduled_at)->toIso8601String(),
                    'total_price' => $order->total_price !== null ? (float) $order->total_price : null,
                    'services' => collect($order->services ?? [])->map(function ($service) {
                        return Arr::only($service, ['id', 'name', 'price', 'duration']);
                    })->values()->toArray(),
                ];
            })->values()->toArray(),
        ];
    }

    protected function buildRecommendedServices(Client $client, $services)
    {
        $serviceCollection = $services instanceof Collection ? $services : collect($services);
        $history = $this->fetchClientOrders($client, 10);

        if ($history->isEmpty() || ! $this->aiAvailable()) {
            return $this->fallbackClientRecommendations($client, $history, $serviceCollection);
        }

        $cacheKey = $this->recommendationCacheKey($client, $serviceCollection, $history);

        if ($cacheKey && Cache::has($cacheKey)) {
            $cached = collect(Cache::get($cacheKey));
            return $this->finalizeRecommendations($cached, $serviceCollection);
        }

        try {
            $context = $this->buildRecommendationContext($client, $serviceCollection, $history);

            $prompt = <<<'PROMPT'
Вы — ИИ-ассистент бьюти-мастера. Используя список доступных услуг, историю клиента и его профиль, подберите до трёх услуг, которые стоит предложить клиенту дополнительно.
Для каждой рекомендации обязательно укажите:
- service_id из available_services, на которую вы ссылаетесь,
- короткий заголовок предложения,
- insight — почему услуга актуальна,
- action — как предложить её клиенту,
- confidence от 0 до 1 (или null, если оценка невозможна).
Фокусируйтесь на практических улучшениях сервиса и росте выручки мастера.
PROMPT;

            $response = $this->openAI->respond($prompt, $context, [
                'temperature' => 0.25,
                'max_tokens' => 700,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'client_recommendations',
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'recommendations' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'service_id' => [
                                                'oneOf' => [
                                                    ['type' => 'integer'],
                                                    ['type' => 'string'],
                                                ],
                                            ],
                                            'title' => ['type' => 'string'],
                                            'insight' => ['type' => 'string'],
                                            'action' => ['type' => 'string'],
                                            'confidence' => [
                                                'oneOf' => [
                                                    ['type' => 'number'],
                                                    ['type' => 'null'],
                                                ],
                                            ],
                                        ],
                                        'required' => ['service_id', 'title', 'insight', 'action'],
                                    ],
                                ],
                            ],
                            'required' => ['recommendations'],
                        ],
                    ],
                ],
            ]);

            $payload = json_decode($response['content'] ?? '', true);

            if (! is_array($payload)) {
                throw new \UnexpectedValueException('Invalid response payload for recommendations.');
            }

            $recommendations = collect($payload['recommendations'] ?? [])
                ->map(function ($item) {
                    if (! is_array($item)) {
                        return null;
                    }

                    $title = Arr::get($item, 'title');
                    $insight = Arr::get($item, 'insight');
                    $action = Arr::get($item, 'action');
                    $serviceId = Arr::get($item, 'service_id');
                    $serviceName = Arr::get($item, 'service_name');

                    if (! is_string($title) || ! is_string($insight) || ! is_string($action)) {
                        return null;
                    }

                    $confidence = Arr::has($item, 'confidence') && Arr::get($item, 'confidence') !== null
                        ? (float) Arr::get($item, 'confidence')
                        : null;

                    if (is_string($serviceId) && is_numeric($serviceId)) {
                        $serviceId = (int) $serviceId;
                    } elseif (is_int($serviceId) || is_float($serviceId)) {
                        $serviceId = (int) $serviceId;
                    } else {
                        $serviceId = null;
                    }

                    $serviceName = is_string($serviceName) ? trim($serviceName) : null;

                    return [
                        'title' => trim($title),
                        'insight' => trim($insight),
                        'action' => trim($action),
                        'confidence' => $confidence,
                        'service_id' => $serviceId,
                        'service_name' => $serviceName,
                    ];
                })
                ->filter()
                ->values();

            if ($recommendations->isEmpty()) {
                return $this->fallbackClientRecommendations($client, $history, $serviceCollection);
            }

            if ($cacheKey) {
                $cached = $this->finalizeRecommendations($recommendations->take(3), $serviceCollection);
                Cache::put($cacheKey, $cached->toArray(), now()->addHours(6));
                return $cached;
            }

            return $this->finalizeRecommendations($recommendations->take(3), $serviceCollection);
        } catch (Throwable $exception) {
            Log::warning('Failed to build client AI recommendations.', [
                'user_id' => $this->currentUserId(),
                'client_id' => $client->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        return $this->fallbackClientRecommendations($client, $history, $serviceCollection);
    }

    protected function recommendationCacheKey(Client $client, Collection $services, Collection $history): ?string
    {
        if ($services->isEmpty()) {
            return null;
        }

        $serviceSignature = $services->map(function (Service $service) {
            return $service->id . ':' . optional($service->updated_at)->timestamp;
        })->join('|');

        $historySignature = $history->map(function (Order $order) {
            return $order->id . ':' . optional($order->updated_at)->timestamp . ':' . optional($order->scheduled_at)->timestamp;
        })->join('|');

        $profileUpdated = optional($client->updated_at)->timestamp ?? 0;

        return 'clients:recommendations:' . $this->currentUserId() . ':' . $client->id . ':' . sha1($serviceSignature . '|' . $historySignature . '|' . $profileUpdated);
    }

    protected function fallbackClientRecommendations(Client $client, Collection $history, Collection $services): Collection
    {
        $serviceCollection = $services->filter(fn ($service) => $service instanceof Service)->values();

        if ($serviceCollection->isEmpty()) {
            $name = $client->name ?? 'клиента';

            return collect([
                [
                    'title' => 'Поддерживать связь',
                    'insight' => "Персональные советы по уходу помогают удерживать $name дольше.",
                    'action' => 'Напомните о преимуществах сервиса и поделитесь мини-гайдом по домашнему уходу.',
                    'confidence' => null,
                ],
                [
                    'title' => 'Запланировать следующий шаг',
                    'insight' => 'Чёткий план следующего визита снимает вопросы у клиента.',
                    'action' => 'Предложите несколько окон для записи и зафиксируйте подходящее время прямо сейчас.',
                    'confidence' => null,
                ],
            ])->take(3);
        }

        $name = $client->name ?? 'клиента';
        $suggestions = collect();

        $serviceCounts = [];
        $serviceVisits = [];

        foreach ($history as $historyOrder) {
            $scheduledAt = $historyOrder->scheduled_at ? $historyOrder->scheduled_at->copy() : null;

            foreach ((array) ($historyOrder->services ?? []) as $serviceData) {
                $serviceModel = $this->resolveServiceFromHistory((array) $serviceData, $serviceCollection);

                if (! $serviceModel) {
                    continue;
                }

                $serviceId = $serviceModel->id;
                $serviceCounts[$serviceId] = ($serviceCounts[$serviceId] ?? 0) + 1;

                if ($scheduledAt) {
                    $serviceVisits[$serviceId] ??= [];
                    $serviceVisits[$serviceId][] = $scheduledAt;
                }
            }
        }

        $serviceCountsCollection = collect($serviceCounts)->sortDesc();
        $topServiceId = $serviceCountsCollection->keys()->first();
        $topService = $topServiceId ? $serviceCollection->firstWhere('id', $topServiceId) : null;

        $avgInterval = null;

        if ($topServiceId && ! empty($serviceVisits[$topServiceId]) && count($serviceVisits[$topServiceId]) > 1) {
            $visits = collect($serviceVisits[$topServiceId])->sort()->values();
            $diffs = [];

            for ($i = 1; $i < $visits->count(); $i++) {
                $diffs[] = $visits[$i - 1]->diffInDays($visits[$i]);
            }

            if (! empty($diffs)) {
                $avgInterval = round(array_sum($diffs) / count($diffs));
            }
        }

        if ($topService) {
            $insight = "$name чаще всего выбирает {$topService->name}.";

            if ($avgInterval) {
                $insight .= " Интервал между визитами ~{$avgInterval} дней.";
            }

            if (! empty($client->preferences)) {
                $insight .= ' Учитывайте предпочтения: ' . implode(', ', (array) $client->preferences) . '.';
            }

            $action = 'Запланируйте повтор заранее и предложите комплимент за своевременный визит.';

            $suggestions->push($this->makeServiceRecommendation($topService, $insight, $action));
        }

        $unusedServices = $serviceCollection->filter(fn (Service $service) => ! array_key_exists($service->id, $serviceCounts));

        if ($unusedServices->isNotEmpty()) {
            $freshService = $unusedServices->sortByDesc('base_price')->first();

            if ($freshService && ! $suggestions->pluck('service_id')->contains($freshService->id)) {
                $insight = "$name ещё не пробовал {$freshService->name}. Это разнообразит впечатления от сервиса.";

                if (! empty($client->preferences)) {
                    $insight .= ' В профиле отмечены предпочтения: ' . implode(', ', (array) $client->preferences) . '.';
                }

                $action = 'Расскажите, какой результат даёт эта процедура, и предложите протестировать её со скидкой или бонусом.';

                $suggestions->push($this->makeServiceRecommendation($freshService, $insight, $action));
            }
        }

        $usedIds = $suggestions->pluck('service_id')->filter()->all();

        $remaining = $serviceCollection
            ->sortByDesc('base_price')
            ->filter(fn (Service $service) => ! in_array($service->id, $usedIds, true));

        foreach ($remaining as $serviceOption) {
            if ($suggestions->count() >= 3) {
                break;
            }

            $insight = "{$serviceOption->name} поможет подчеркнуть результат и повысить средний чек.";

            if (! empty($client->preferences)) {
                $insight .= ' Учитывайте предпочтения: ' . implode(', ', (array) $client->preferences) . '.';
            }

            $action = 'Предложите услугу как дополнение после основного визита и уточните, сколько времени потребуется.';

            $suggestions->push($this->makeServiceRecommendation($serviceOption, $insight, $action));
        }

        if ($suggestions->isEmpty() && $serviceCollection->isNotEmpty()) {
            $defaultService = $serviceCollection->first();

            $suggestions->push($this->makeServiceRecommendation(
                $defaultService,
                "Порекомендуйте {$defaultService->name}, чтобы расширить привычный сценарий визитов $name.",
                'Сформулируйте выгоды и предложите забронировать время прямо сейчас.'
            ));
        }

        return $this->finalizeRecommendations($suggestions->take(3), $serviceCollection);
    }

    protected function makeServiceRecommendation(Service $service, string $insight, string $action, ?float $confidence = null): array
    {
        return [
            'title' => $service->name,
            'insight' => $insight,
            'action' => $action,
            'confidence' => $confidence,
            'service_id' => $service->id,
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'price' => (float) $service->base_price,
                'duration' => (int) $service->duration_min,
            ],
        ];
    }

    protected function resolveServiceFromHistory(array $serviceData, Collection $services): ?Service
    {
        $serviceId = Arr::get($serviceData, 'id');

        if ($serviceId !== null) {
            $service = $services->firstWhere('id', $serviceId);

            if ($service instanceof Service) {
                return $service;
            }
        }

        $serviceName = Arr::get($serviceData, 'name');

        if (is_string($serviceName) && $serviceName !== '') {
            $normalized = Str::lower(trim($serviceName));

            return $services->first(function (Service $service) use ($normalized) {
                return Str::lower($service->name) === $normalized;
            });
        }

        return null;
    }

    protected function finalizeRecommendations(Collection $recommendations, Collection $services): Collection
    {
        $serviceModels = $services->filter(fn ($service) => $service instanceof Service);
        $servicesById = $serviceModels->keyBy('id');
        $servicesByName = $serviceModels->mapWithKeys(function (Service $service) {
            return [Str::lower($service->name) => $service];
        });

        return $recommendations->map(function ($item) use ($servicesById, $servicesByName) {
            if (! is_array($item)) {
                return null;
            }

            $serviceData = Arr::get($item, 'service');
            $serviceId = Arr::get($item, 'service_id');
            $serviceName = Arr::get($item, 'service_name');

            if (is_array($serviceData)) {
                $serviceId ??= Arr::get($serviceData, 'id');
                $serviceName ??= Arr::get($serviceData, 'name');
            }

            $matchedService = null;

            if ($serviceId !== null && $servicesById->has((int) $serviceId)) {
                $matchedService = $servicesById->get((int) $serviceId);
            } elseif (is_string($serviceName) && $serviceName !== '') {
                $lookup = Str::lower(trim($serviceName));

                if ($servicesByName->has($lookup)) {
                    $matchedService = $servicesByName->get($lookup);
                }
            }

            if ($matchedService instanceof Service) {
                $servicePayload = [
                    'id' => $matchedService->id,
                    'name' => $matchedService->name,
                    'price' => (float) $matchedService->base_price,
                    'duration' => (int) $matchedService->duration_min,
                ];
            } elseif (is_array($serviceData)) {
                $price = Arr::get($serviceData, 'price');
                $duration = Arr::get($serviceData, 'duration');

                $servicePayload = [
                    'id' => Arr::get($serviceData, 'id'),
                    'name' => Arr::get($serviceData, 'name'),
                    'price' => is_numeric($price) ? (float) $price : null,
                    'duration' => is_numeric($duration) ? (int) $duration : null,
                ];
            } else {
                $servicePayload = null;
            }

            $confidence = Arr::get($item, 'confidence');
            $confidence = is_numeric($confidence) ? (float) $confidence : null;

            return [
                'title' => Arr::get($item, 'title', $servicePayload['name'] ?? 'Рекомендация'),
                'insight' => Arr::get($item, 'insight'),
                'action' => Arr::get($item, 'action'),
                'confidence' => $confidence,
                'service' => $servicePayload,
            ];
        })->filter()->values();
    }

    protected function serializeRecommendations(Collection $recommendations): array
    {
        return $recommendations->map(function ($item) {
            if (! is_array($item)) {
                return null;
            }

            $service = Arr::get($item, 'service');
            $servicePayload = null;

            if (is_array($service)) {
                $price = Arr::get($service, 'price');
                $duration = Arr::get($service, 'duration');

                $servicePayload = [
                    'id' => Arr::get($service, 'id'),
                    'name' => Arr::get($service, 'name'),
                    'price' => is_numeric($price) ? (float) $price : null,
                    'duration' => is_numeric($duration) ? (int) $duration : null,
                ];
            }

            $confidence = Arr::get($item, 'confidence');
            $confidence = is_numeric($confidence) ? (float) $confidence : null;

            return [
                'title' => Arr::get($item, 'title', $servicePayload['name'] ?? 'Рекомендация'),
                'insight' => Arr::get($item, 'insight'),
                'action' => Arr::get($item, 'action'),
                'confidence' => $confidence,
                'service' => $servicePayload,
            ];
        })->filter()->values()->all();
    }

    protected function getUserServices(?int $limit = null)
    {
        $query = Service::where('user_id', $this->currentUserId())
            ->orderBy('name');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    protected function aiAvailable(): bool
    {
        return $this->userHasProAccess() && filled(config('openai.api_key'));
    }

    protected function ensureClientBelongsToCurrentUser(Client $client): void
    {
        if ($client->user_id !== $this->currentUserId()) {
            abort(403);
        }
    }

    protected function currentUserId(): int
    {
        $userId = Auth::guard('sanctum')->id();

        if (! $userId) {
            abort(403);
        }

        return $userId;
    }

    protected function resolveUserSettings(): ?Setting
    {
        $userId = Auth::guard('sanctum')->id();

        if (! $userId) {
            return null;
        }

        return Setting::where('user_id', $userId)->first();
    }

    protected function userHasProAccess(): bool
    {
        $user = Auth::guard('sanctum')->user();

        if (! $user) {
            return false;
        }

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
