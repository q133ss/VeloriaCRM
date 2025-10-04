<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkOrderActionRequest;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\OrderFilterRequest;
use App\Http\Requests\OrderFormRequest;
use App\Http\Requests\QuickOrderRequest;
use App\Http\Requests\RescheduleOrderRequest;
use App\Models\Client;
use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\OpenAIService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private readonly OpenAIService $openAI,
        private readonly OrderService $orderService,
    ) {
    }
    public function index(OrderFilterRequest $request): JsonResponse
    {
        $userId = $this->currentUserId();
        $filters = $request->validated();
        $filters['period'] = $filters['period'] ?? 'this_week';
        $filters['status'] = $filters['status'] ?? 'all';
        $perPage = (int) ($filters['per_page'] ?? 12);
        $perPage = max(1, min($perPage, 50));

        $orders = Order::with(['client', 'master'])
            ->where('master_id', $userId)
            ->withFilter($filters)
            ->orderByDesc('scheduled_at')
            ->paginate($perPage);

        $orders->getCollection()->transform(fn (Order $order) => $this->transformOrder($order));

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ],
                'filters' => [
                    'period' => $filters['period'],
                    'status' => $filters['status'],
                    'search' => $filters['search'] ?? null,
                ],
                'period_options' => Order::periodOptions(),
                'status_options' => ['all' => 'Все статусы'] + Order::statusLabels(),
                'reminder_message' => optional($this->resolveUserSettings())->reminder_message,
            ],
            'links' => [
                'first' => $orders->url(1),
                'last' => $orders->url($orders->lastPage()),
                'prev' => $orders->previousPageUrl(),
                'next' => $orders->nextPageUrl(),
            ],
        ]);
    }

    public function store(OrderFormRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $masterId = $this->currentUserId();

        $order = DB::transaction(function () use ($validated, $masterId) {
            $client = $this->resolveClient(
                $validated['client_phone'],
                Arr::get($validated, 'client_name'),
                Arr::get($validated, 'client_email')
            );

            $services = $this->collectServices(Arr::get($validated, 'services', []));

            $servicePayload = $services->map(function (Service $service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => (float) $service->base_price,
                    'duration' => (int) $service->duration_min,
                ];
            })->values()->all();

            $totalPrice = Arr::get($validated, 'total_price');
            if ($totalPrice === null) {
                $totalPrice = $services->sum('base_price');
            }

            $recommended = $this->buildRecommendedServices($client, $this->getUserServices());

            return Order::create([
                'master_id' => $masterId,
                'client_id' => $client->id,
                'services' => $servicePayload,
                'scheduled_at' => Carbon::parse($validated['scheduled_at']),
                'note' => Arr::get($validated, 'note'),
                'duration_forecast' => $services->sum('duration_min') ?: null,
                'total_price' => $totalPrice ?? 0,
                'status' => $validated['status'],
                'source' => Arr::get($validated, 'source', 'manual'),
                'recommended_services' => $this->serializeRecommendations($recommended),
            ]);
        });

        $order->load(['client', 'master']);

        $this->orderService->scheduleStartReminder($order);

        return response()->json([
            'data' => $this->decorateOrder($order),
            'message' => 'Запись успешно создана.',
        ], 201);
    }

    public function show(Order $order): JsonResponse
    {
        $this->ensureOrderBelongsToCurrentUser($order);
        $order->loadMissing(['client', 'master']);

        $settings = $this->resolveUserSettings();

        return response()->json([
            'data' => $this->decorateOrder($order),
            'meta' => [
                'has_pro_access' => $this->userHasProAccess(),
                'reminder_message' => optional($settings)->reminder_message,
            ],
        ]);
    }

    public function update(OrderFormRequest $request, Order $order): JsonResponse
    {
        $this->ensureOrderBelongsToCurrentUser($order);
        $validated = $request->validated();
        $masterId = $this->currentUserId();

        $scheduledChanged = false;

        DB::transaction(function () use ($validated, $order, $masterId, &$scheduledChanged) {
            $client = $this->resolveClient(
                $validated['client_phone'],
                Arr::get($validated, 'client_name'),
                Arr::get($validated, 'client_email')
            );

            $services = $this->collectServices(Arr::get($validated, 'services', []));

            $servicePayload = $services->map(function (Service $service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => (float) $service->base_price,
                    'duration' => (int) $service->duration_min,
                ];
            })->values()->all();

            $totalPrice = Arr::get($validated, 'total_price');
            if ($totalPrice === null) {
                $totalPrice = $services->sum('base_price');
            }

            $recommended = $this->buildRecommendedServices($client, $this->getUserServices());

            $newScheduledAt = Carbon::parse($validated['scheduled_at']);
            $scheduledChanged = !$order->scheduled_at || !$order->scheduled_at->equalTo($newScheduledAt);

            $payload = [
                'master_id' => $masterId,
                'client_id' => $client->id,
                'services' => $servicePayload,
                'scheduled_at' => $newScheduledAt,
                'note' => Arr::get($validated, 'note'),
                'duration_forecast' => $services->sum('duration_min') ?: null,
                'total_price' => $totalPrice ?? 0,
                'status' => $validated['status'],
                'recommended_services' => $this->serializeRecommendations($recommended),
            ];

            if ($scheduledChanged) {
                $payload['start_confirmation_notified_at'] = null;
            }

            $order->update($payload);
        });

        $order->refresh()->loadMissing(['client', 'master']);

        if ($scheduledChanged) {
            $this->orderService->scheduleStartReminder($order);
        }

        return response()->json([
            'data' => $this->decorateOrder($order),
            'message' => 'Запись обновлена.',
        ]);
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->ensureOrderBelongsToCurrentUser($order);
        $order->delete();

        return response()->json([
            'message' => 'Запись удалена.',
        ]);
    }

    public function bulk(BulkOrderActionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->currentUserId();
        $settings = $this->resolveUserSettings();
        $reminderMessage = optional($settings)->reminder_message;

        if ($validated['action'] === 'remind' && empty($reminderMessage)) {
            return response()->json([
                'error' => [
                    'code' => 'reminder_template_missing',
                    'message' => 'Добавьте текст автонапоминания в настройках, чтобы отправлять напоминания.',
                ],
            ], 422);
        }

        $orders = Order::where('master_id', $userId)
            ->whereIn('id', $validated['orders'])
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'error' => [
                    'code' => 'orders_not_found',
                    'message' => 'Выбранные записи не найдены.',
                ],
            ], 404);
        }

        $now = Carbon::now();

        DB::transaction(function () use ($orders, $validated, $now) {
            foreach ($orders as $order) {
                switch ($validated['action']) {
                    case 'confirm':
                        $order->update([
                            'status' => 'confirmed',
                            'confirmed_at' => $now,
                        ]);
                        break;
                    case 'remind':
                        $order->update([
                            'reminded_at' => $now,
                            'is_reminder_sent' => true,
                        ]);
                        break;
                    case 'cancel':
                        $order->update([
                            'status' => 'cancelled',
                            'cancelled_at' => $now,
                        ]);
                        break;
                }
            }
        });

        $messages = [
            'confirm' => 'Выбранные записи подтверждены.',
            'remind' => 'Напоминания отмечены как отправленные.',
            'cancel' => 'Выбранные записи отменены.',
        ];

        $response = [
            'message' => $messages[$validated['action']],
            'updated_ids' => $orders->pluck('id'),
        ];

        if ($validated['action'] === 'remind') {
            $response['reminder_text'] = $reminderMessage;
        }

        return response()->json($response);
    }

    public function quickStore(QuickOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $masterId = $this->currentUserId();

        $order = DB::transaction(function () use ($validated, $masterId) {
            $client = $this->resolveClient(
                $validated['client_phone'],
                Arr::get($validated, 'client_name'),
                Arr::get($validated, 'client_email')
            );

            $services = $this->collectServices(Arr::get($validated, 'services', []));

            $servicePayload = $services->map(function (Service $service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => (float) $service->base_price,
                    'duration' => (int) $service->duration_min,
                ];
            })->values()->all();

            $totalPrice = Arr::get($validated, 'total_price');
            if ($totalPrice === null) {
                $totalPrice = $services->sum('base_price');
            }

            $recommended = $this->buildRecommendedServices($client, $this->getUserServices());

            return Order::create([
                'master_id' => $masterId,
                'client_id' => $client->id,
                'services' => $servicePayload,
                'scheduled_at' => Carbon::parse($validated['scheduled_at']),
                'note' => Arr::get($validated, 'note'),
                'total_price' => $totalPrice ?? 0,
                'status' => 'new',
                'source' => 'quick_modal',
                'recommended_services' => $this->serializeRecommendations($recommended),
            ]);
        });

        $order->load(['client', 'master']);

        $this->orderService->scheduleStartReminder($order);

        return response()->json([
            'data' => $this->decorateOrder($order),
            'message' => 'Запись создана через быстрое создание.',
        ], 201);
    }

    public function complete(Order $order): JsonResponse
    {
        $this->ensureOrderBelongsToCurrentUser($order);
        $now = Carbon::now();
        $duration = null;

        if ($order->actual_started_at) {
            $duration = $order->actual_started_at->diffInMinutes($now);
        }

        $order->update([
            'status' => 'completed',
            'actual_finished_at' => $now,
            'duration' => $duration,
        ]);

        $order->refresh();

        return response()->json([
            'data' => $this->decorateOrder($order),
            'message' => 'Запись завершена.',
        ]);
    }

    public function start(Order $order, Request $request): JsonResponse
    {
        $this->ensureOrderBelongsToCurrentUser($order);
        $now = Carbon::now();

        $startedAt = $request->input('started_at');
        $actualStartedAt = $now;

        if ($startedAt) {
            try {
                $actualStartedAt = Carbon::parse($startedAt);
            } catch (Throwable $exception) {
                return response()->json([
                    'error' => [
                        'code' => 'invalid_started_at',
                        'message' => 'Некорректный формат времени начала процедуры.',
                    ],
                ], 422);
            }
        }

        $order->update([
            'status' => 'in_progress',
            'actual_started_at' => $actualStartedAt,
        ]);

        $order->refresh();

        return response()->json([
            'data' => $this->decorateOrder($order),
            'message' => 'Начало работы зафиксировано.',
        ]);
    }

    public function remind(Order $order): JsonResponse
    {
        $this->ensureOrderBelongsToCurrentUser($order);
        $settings = $this->resolveUserSettings();
        $reminderMessage = optional($settings)->reminder_message;

        if (empty($reminderMessage)) {
            return response()->json([
                'error' => [
                    'code' => 'reminder_template_missing',
                    'message' => 'Добавьте текст автонапоминания в настройках, чтобы отправить напоминание.',
                ],
            ], 422);
        }

        $now = Carbon::now();

        $order->update([
            'reminded_at' => $now,
            'is_reminder_sent' => true,
        ]);

        $order->refresh();

        return response()->json([
            'data' => $this->decorateOrder($order),
            'message' => 'Напоминание отмечено. Не забудьте отправить клиенту сообщение!',
            'reminder_text' => $reminderMessage,
        ]);
    }

    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        $this->ensureOrderBelongsToCurrentUser($order);
        $validated = $request->validated();

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
            'cancellation_reason' => Arr::get($validated, 'reason'),
        ]);

        $order->refresh();

        return response()->json([
            'data' => $this->decorateOrder($order),
            'message' => 'Запись отменена.',
        ]);
    }

    public function reschedule(RescheduleOrderRequest $request, Order $order): JsonResponse
    {
        $this->ensureOrderBelongsToCurrentUser($order);
        $validated = $request->validated();

        $previousDate = $order->scheduled_at;
        $order->update([
            'rescheduled_from' => $previousDate && !$order->rescheduled_from ? $previousDate : $order->rescheduled_from,
            'scheduled_at' => Carbon::parse($validated['scheduled_at']),
            'reschedule_count' => ($order->reschedule_count ?? 0) + 1,
            'start_confirmation_notified_at' => null,
        ]);

        $order->refresh();

        $this->orderService->scheduleStartReminder($order);

        return response()->json([
            'data' => $this->decorateOrder($order),
            'message' => 'Запись перенесена.',
        ]);
    }

    public function analytics(Order $order): JsonResponse
    {
        $this->ensureOrderBelongsToCurrentUser($order);
        $order->loadMissing(['client']);

        if (! $this->userHasProAccess()) {
            return response()->json([
                'error' => [
                    'code' => 'feature_unavailable',
                    'message' => 'Аналитика доступна только в тарифах PRO и Elite.',
                ],
            ], 403);
        }

        if (! $order->client) {
            return response()->json([
                'error' => [
                    'code' => 'client_not_found',
                    'message' => 'Для этой записи не найден клиент.',
                ],
            ], 404);
        }

        $clientProfile = Client::where('user_id', $order->client_id)->first();

        $history = Order::where('master_id', $order->master_id)
            ->where('client_id', $order->client_id)
            ->orderByDesc('scheduled_at')
            ->get();

        $totalVisits = $history->count();
        $completedVisits = $history->filter(fn (Order $item) => $item->status === 'completed')->count();

        if ($totalVisits < 2 || $completedVisits === 0) {
            return response()->json([
                'error' => [
                    'code' => 'not_enough_data',
                    'message' => 'Недостаточно данных по визитам клиента для построения аналитики.',
                ],
            ], 422);
        }

        $cacheKey = $this->analyticsCacheKey($order, $history, $clientProfile);

        $payload = Cache::remember($cacheKey, now()->addHours(12), function () use ($order, $clientProfile, $history) {
            $metrics = $this->buildClientAnalyticsMetrics($history);

            if ($clientProfile && $clientProfile->loyalty_level) {
                $metrics['loyalty_level'] = $clientProfile->loyalty_level;
            }

            $insights = $this->generateClientAnalyticsInsights($order->client, $clientProfile, $metrics, $history);

            return [
                'client' => [
                    'id' => $order->client->id,
                    'name' => $order->client->name,
                    'phone' => $order->client->phone,
                    'email' => $order->client->email,
                    'profile' => $clientProfile ? [
                        'loyalty_level' => $clientProfile->loyalty_level,
                        'tags' => $clientProfile->tags ?? [],
                        'allergies' => $clientProfile->allergies ?? [],
                        'preferences' => $clientProfile->preferences ?? [],
                        'notes' => $clientProfile->notes,
                    ] : null,
                ],
                'metrics' => $metrics,
                'insights' => $insights,
            ];
        });

        return response()->json($payload);
    }

    public function options(Request $request): JsonResponse
    {
        $services = $this->getUserServices();
        $client = null;
        $clientProfile = null;
        $suggestions = collect();

        if ($request->filled('client_id')) {
            $client = User::find($request->input('client_id'));
        } elseif ($request->filled('client_phone')) {
            $client = User::where('phone', $this->normalizePhone($request->input('client_phone')))->first();
            $suggestions = $this->buildClientSuggestions($request->input('client_phone'));
        }

        if ($client) {
            $clientProfile = Client::where('user_id', $client->id)->first();
        }

        $recommended = $this->buildRecommendedServices($client, $services);

        return response()->json([
            'services' => $services->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'price' => (float) $service->base_price,
                'duration' => (int) $service->duration_min,
            ])->values(),
            'status_options' => Order::statusLabels(),
            'default_status' => 'new',
            'recommended_services' => $this->serializeRecommendations($recommended),
            'client' => $client ? [
                'id' => $client->id,
                'name' => $clientProfile?->name ?? $client->name,
                'phone' => $client->phone,
                'email' => $clientProfile?->email ?? $client->email,
            ] : null,
            'suggestions' => $suggestions->values(),
        ]);
    }

    protected function buildClientSuggestions(?string $phone): Collection
    {
        if ($phone === null || $phone === '') {
            return collect();
        }

        $digits = preg_replace('/[^0-9]+/', '', $phone);

        if (strlen($digits) < 3) {
            return collect();
        }

        $normalizedPrefix = $this->normalizePhone($phone);
        $pattern = $normalizedPrefix . '%';
        $userId = $this->currentUserId();

        $suggestions = collect();

        $clientProfiles = Client::query()
            ->where('user_id', $userId)
            ->where('phone', 'like', $pattern)
            ->orderBy('name')
            ->limit(5)
            ->get(['name', 'phone']);

        foreach ($clientProfiles as $profile) {
            if (! $profile->phone) {
                continue;
            }

            $suggestions->put($profile->phone, [
                'name' => $profile->name,
                'phone' => $profile->phone,
            ]);
        }

        if ($suggestions->count() < 5) {
            $orders = Order::query()
                ->with('client')
                ->where('master_id', $userId)
                ->whereHas('client', function ($query) use ($pattern) {
                    $query->where('phone', 'like', $pattern);
                })
                ->latest('scheduled_at')
                ->limit(10)
                ->get();

            foreach ($orders as $order) {
                $orderClient = $order->client;

                if (! $orderClient || ! $orderClient->phone) {
                    continue;
                }

                if (! $suggestions->has($orderClient->phone)) {
                    $suggestions->put($orderClient->phone, [
                        'name' => $orderClient->name ?? 'Без имени',
                        'phone' => $orderClient->phone,
                    ]);
                }

                if ($suggestions->count() >= 5) {
                    break;
                }
            }
        }

        return $suggestions->values();
    }

    protected function collectServices(array $serviceIds)
    {
        if (empty($serviceIds)) {
            return collect();
        }

        return Service::where('user_id', $this->currentUserId())
            ->whereIn('id', $serviceIds)
            ->get();
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

    protected function resolveClient(string $phone, ?string $name = null, ?string $email = null): User
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $email = $email !== null ? trim($email) : null;
        if ($email === '') {
            $email = null;
        }

        $user = User::where('phone', $normalizedPhone)->first();

        if (! $user && $email) {
            $user = User::where('email', $email)->first();
        }

        if (! $user) {
            $user = User::create([
                'name' => $name ?: 'Клиент ' . Str::substr($normalizedPhone, -4),
                'phone' => $normalizedPhone,
                'email' => $email,
                'password' => Str::random(16),
            ]);
        } else {
            $user->forceFill([
                'name' => $name ?: $user->name,
                'email' => $email ?: $user->email,
                'phone' => $normalizedPhone,
            ])->save();
        }

        Client::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $name ?: $user->name,
                'phone' => $normalizedPhone,
                'email' => $email ?: $user->email,
            ]
        );

        return $user;
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]+/', '', $phone);

        if (! $digits) {
            return trim($phone);
        }

        if (strlen($digits) === 10) {
            $digits = '7' . $digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            $digits = '7' . substr($digits, 1);
        }

        if (! str_starts_with($digits, '7') && ! str_starts_with($digits, '8')) {
            $digits = '7' . $digits;
        }

        return '+' . $digits;
    }

    protected function aiAvailable(): bool
    {
        return $this->userHasProAccess() && filled(config('openai.api_key'));
    }

    protected function buildRecommendedServices(?User $client, $services)
    {
        $serviceCollection = $services instanceof Collection ? $services : collect($services);
        $clientProfile = $client ? Client::where('user_id', $client->id)->first() : null;
        $history = $this->fetchClientHistory($client, 10);

        if (! $client || $history->isEmpty() || ! $this->aiAvailable()) {
            return $this->fallbackClientRecommendations($client, $history, $clientProfile, $serviceCollection);
        }

        $cacheKey = $this->recommendationCacheKey($client, $serviceCollection, $clientProfile, $history);

        if ($cacheKey && Cache::has($cacheKey)) {
            $cached = collect(Cache::get($cacheKey));
            return $this->finalizeRecommendations($cached, $serviceCollection);
        }

        try {
            $context = $this->buildRecommendationContext($client, $clientProfile, $serviceCollection, $history);

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
                return $this->fallbackClientRecommendations($client, $history, $clientProfile, $serviceCollection);
            }

            if ($cacheKey) {
                $cached = $this->finalizeRecommendations($recommendations->take(3), $serviceCollection);
                Cache::put($cacheKey, $cached->toArray(), now()->addHours(6));
                return $cached;
            }

            return $this->finalizeRecommendations($recommendations->take(3), $serviceCollection);
        } catch (Throwable $exception) {
            Log::warning('Failed to build AI recommendations.', [
                'user_id' => $this->currentUserId(),
                'client_id' => $client?->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        return $this->fallbackClientRecommendations($client, $history, $clientProfile, $serviceCollection);
    }

    protected function recommendationCacheKey(?User $client, Collection $services, ?Client $clientProfile, Collection $history): ?string
    {
        if (! $client) {
            return null;
        }

        $serviceSignature = $services->map(function (Service $service) {
            return $service->id . ':' . optional($service->updated_at)->timestamp;
        })->join('|');

        $historySignature = $history->map(function (Order $order) {
            return $order->id . ':' . optional($order->updated_at)->timestamp . ':' . optional($order->scheduled_at)->timestamp;
        })->join('|');

        $profileUpdated = optional($clientProfile?->updated_at)->timestamp ?? 0;

        return 'orders:ai:recommendations:' . $this->currentUserId() . ':' . $client->id . ':' . sha1($serviceSignature . '|' . $historySignature . '|' . $profileUpdated);
    }

    protected function fetchClientHistory(?User $client, int $limit = 10): Collection
    {
        if (! $client) {
            return collect();
        }

        return Order::where('master_id', $this->currentUserId())
            ->where('client_id', $client->id)
            ->orderByDesc('scheduled_at')
            ->limit($limit)
            ->get();
    }

    protected function fallbackClientRecommendations(?User $client, Collection $history, ?Client $clientProfile, Collection $services): Collection
    {
        $serviceCollection = $services->filter(fn ($service) => $service instanceof Service)->values();

        if ($serviceCollection->isEmpty()) {
            $name = $client?->name ?? 'клиента';

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

        $name = $client?->name ?? 'клиента';
        $suggestions = collect();

        $serviceCounts = [];
        $serviceVisits = [];

        foreach ($history as $historyOrder) {
            $scheduledAt = $historyOrder->scheduled_at ? $historyOrder->scheduled_at->copy() : null;

            foreach ((array) ($historyOrder->services ?? []) as $serviceData) {
                $serviceModel = $this->resolveServiceFromHistory($serviceData, $serviceCollection);

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

        if ($topService && isset($serviceVisits[$topService->id]) && count($serviceVisits[$topService->id]) > 1) {
            $dates = $serviceVisits[$topService->id];
            usort($dates, function ($a, $b) {
                if ($a && $b) {
                    if ($a->lt($b)) {
                        return -1;
                    }

                    if ($a->gt($b)) {
                        return 1;
                    }
                }

                return 0;
            });

            $intervals = [];

            for ($i = 1; $i < count($dates); $i++) {
                $previous = $dates[$i - 1];
                $current = $dates[$i];

                if ($previous && $current) {
                    $intervals[] = $previous->diffInDays($current);
                }
            }

            if (! empty($intervals)) {
                $avgInterval = (int) round(array_sum($intervals) / count($intervals));
            }
        }

        $upsellService = null;
        $upsellBase = null;

        foreach ($serviceCountsCollection->keys() as $serviceId) {
            $base = $serviceCollection->firstWhere('id', $serviceId);

            if (! $base) {
                continue;
            }

            foreach ((array) ($base->upsell_suggestions ?? []) as $candidateId) {
                $candidate = $serviceCollection->firstWhere('id', $candidateId);

                if ($candidate && $candidate->id !== $base->id) {
                    $upsellService = $candidate;
                    $upsellBase = $base;
                    break 2;
                }
            }
        }

        if ($upsellService) {
            $insight = "$name регулярно выбирает {$upsellBase->name}. Дополнительный сервис поможет закрепить результат.";

            if ($avgInterval !== null) {
                $insight .= " Средний интервал между визитами — {$avgInterval} дней.";
            }

            $action = "Предложите {$upsellService->name} в комплекте с {$upsellBase->name}, чтобы клиент почувствовал заботу и увидел мгновенный эффект.";

            $suggestions->push($this->makeServiceRecommendation($upsellService, $insight, $action));
        }

        if ($topService && ! $suggestions->pluck('service_id')->contains($topService->id)) {
            $insight = "$name чаще всего выбирает {$topService->name}.";

            if ($avgInterval !== null) {
                if ($avgInterval < 30) {
                    $insight .= " Клиент возвращается примерно каждые {$avgInterval} дней.";
                    $action = 'Обсудите, как улучшить стойкость результата, и предложите укрепляющий уход.';
                } elseif ($avgInterval > 45) {
                    $insight .= " Пауза между визитами составляет около {$avgInterval} дней.";
                    $action = 'Предложите зафиксировать следующую дату заранее и подготовьте бонус за раннюю запись.';
                } else {
                    $action = 'Поддержите интерес клиента мини-бонусом и обсудите план последующего ухода.';
                }
            } else {
                $action = 'Предложите небольшое улучшение для любимой услуги: спа-уход, дополнительное покрытие или расширенный контроль качества.';
            }

            $suggestions->push($this->makeServiceRecommendation($topService, $insight, $action));
        }

        $unusedServices = $serviceCollection->filter(fn (Service $service) => ! array_key_exists($service->id, $serviceCounts));

        if ($unusedServices->isNotEmpty()) {
            $freshService = $unusedServices->sortByDesc('base_price')->first();

            if ($freshService && ! $suggestions->pluck('service_id')->contains($freshService->id)) {
                $insight = "$name ещё не пробовал {$freshService->name}. Это разнообразит впечатления от сервиса.";

                if (! empty($clientProfile?->preferences)) {
                    $insight .= ' В профиле отмечены предпочтения: ' . implode(', ', (array) $clientProfile->preferences) . '.';
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

            if (! empty($clientProfile?->preferences)) {
                $insight .= ' Учитывайте предпочтения: ' . implode(', ', (array) $clientProfile->preferences) . '.';
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

    protected function buildRecommendationContext(User $client, ?Client $clientProfile, Collection $services, Collection $history): array
    {
        $payload = [
            'available_services' => $services->map(function (Service $service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'base_price' => (float) $service->base_price,
                    'duration_min' => (int) $service->duration_min,
                    'upsell_suggestions' => $service->upsell_suggestions ?? [],
                ];
            })->values()->toArray(),
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
                'email' => $client->email,
            ],
        ];

        if ($clientProfile) {
            $payload['client_profile'] = [
                'tags' => $clientProfile->tags ?? [],
                'allergies' => $clientProfile->allergies ?? [],
                'preferences' => $clientProfile->preferences ?? [],
                'notes' => $clientProfile->notes,
                'last_visit_at' => optional($clientProfile->last_visit_at)->toIso8601String(),
                'loyalty_level' => $clientProfile->loyalty_level,
            ];
        }

        $payload['order_history'] = $history->map(function (Order $order) {
            return [
                'scheduled_at' => optional($order->scheduled_at)->toIso8601String(),
                'status' => $order->status,
                'total_price' => $order->total_price !== null ? (float) $order->total_price : null,
                'services' => collect($order->services ?? [])->map(function ($service) {
                    return Arr::only($service, ['id', 'name', 'price', 'duration']);
                })->values()->toArray(),
                'note' => $order->note,
            ];
        })->values()->toArray();

        return $payload;
    }

    protected function analyticsCacheKey(Order $order, Collection $history, ?Client $clientProfile): string
    {
        $historySignature = $history->map(function (Order $item) {
            return $item->id . ':' . optional($item->updated_at)->timestamp . ':' . optional($item->scheduled_at)->timestamp;
        })->join('|');

        $profileUpdated = optional($clientProfile?->updated_at)->timestamp ?? 0;
        $orderUpdated = optional($order->updated_at)->timestamp ?? 0;

        return 'orders:ai:analytics:' . $order->master_id . ':' . $order->client_id . ':' . sha1($historySignature . '|' . $profileUpdated . '|' . $orderUpdated);
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

    protected function generateClientAnalyticsInsights(User $client, ?Client $clientProfile, array $metrics, Collection $orders): array
    {
        $insights = $this->fallbackAnalyticsInsights($metrics);

        if (! $this->aiAvailable()) {
            return $insights;
        }

        try {
            $context = [
                'client' => [
                    'name' => $client->name,
                    'loyalty_level' => $clientProfile?->loyalty_level,
                    'tags' => $clientProfile?->tags ?? [],
                    'preferences' => $clientProfile?->preferences ?? [],
                    'allergies' => $clientProfile?->allergies ?? [],
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

            if ($clientProfile?->notes) {
                $context['client_notes'] = Str::limit($clientProfile->notes, 500);
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
            Log::warning('Failed to generate AI analytics insights.', [
                'user_id' => $this->currentUserId(),
                'client_id' => $client->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        return $insights;
    }

    protected function ensureOrderBelongsToCurrentUser(Order $order): void
    {
        if ($order->master_id !== $this->currentUserId()) {
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

    protected function transformOrder(Order $order): array
    {
        $order->loadMissing(['client', 'master']);

        $services = collect($order->services ?? [])->map(function ($service) {
            return [
                'id' => $service['id'] ?? null,
                'name' => $service['name'] ?? null,
                'price' => isset($service['price']) ? (float) $service['price'] : null,
                'duration' => isset($service['duration']) ? (int) $service['duration'] : null,
            ];
        })->values();

        $recommended = collect($order->recommended_services ?? [])->map(function ($item) {
            $title = Arr::get($item, 'title') ?? Arr::get($item, 'name') ?? 'Рекомендация';
            $insight = Arr::get($item, 'insight') ?? Arr::get($item, 'description');
            $action = Arr::get($item, 'action');
            $confidence = Arr::get($item, 'confidence');
            $confidence = is_numeric($confidence) ? (float) $confidence : null;

            $serviceData = Arr::get($item, 'service');
            $service = null;

            if (is_array($serviceData)) {
                $price = Arr::get($serviceData, 'price');
                $duration = Arr::get($serviceData, 'duration');

                $service = [
                    'id' => Arr::get($serviceData, 'id'),
                    'name' => Arr::get($serviceData, 'name'),
                    'price' => is_numeric($price) ? (float) $price : null,
                    'duration' => is_numeric($duration) ? (int) $duration : null,
                ];
                $title = $title ?? ($service['name'] ?? 'Рекомендация');
            }

            return [
                'title' => $title,
                'insight' => $insight,
                'action' => $action,
                'confidence' => $confidence,
                'service' => $service,
            ];
        })->values();

        return [
            'id' => $order->id,
            'master' => [
                'id' => $order->master_id,
                'name' => $order->master?->name,
            ],
            'client' => [
                'id' => $order->client?->id,
                'name' => $order->client?->name,
                'phone' => $order->client?->phone,
                'email' => $order->client?->email,
            ],
            'services' => $services,
            'scheduled_at' => optional($order->scheduled_at)->toIso8601String(),
            'scheduled_at_formatted' => optional($order->scheduled_at)->format('d.m.Y H:i'),
            'actual_started_at' => optional($order->actual_started_at)->toIso8601String(),
            'actual_finished_at' => optional($order->actual_finished_at)->toIso8601String(),
            'duration' => $order->duration,
            'duration_forecast' => $order->duration_forecast,
            'total_price' => $order->total_price !== null ? (float) $order->total_price : null,
            'note' => $order->note,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'status_class' => $order->status_class,
            'rescheduled_from' => optional($order->rescheduled_from)->toIso8601String(),
            'reschedule_count' => $order->reschedule_count,
            'confirmed_at' => optional($order->confirmed_at)->toIso8601String(),
            'cancelled_at' => optional($order->cancelled_at)->toIso8601String(),
            'reminded_at' => optional($order->reminded_at)->toIso8601String(),
            'is_reminder_sent' => (bool) $order->is_reminder_sent,
            'recommended_services' => $recommended,
            'source' => $order->source,
            'created_at' => optional($order->created_at)->toIso8601String(),
            'updated_at' => optional($order->updated_at)->toIso8601String(),
        ];
    }

    protected function buildHistory(Order $order): array
    {
        $events = [
            [
                'label' => 'Создано',
                'time' => optional($order->created_at)->format('d.m.Y H:i'),
                'description' => 'Запись создана вручную.',
            ],
        ];

        if ($order->confirmed_at) {
            $events[] = [
                'label' => 'Подтверждено',
                'time' => $order->confirmed_at->format('d.m.Y H:i'),
                'description' => 'Клиент подтвердил визит.',
            ];
        }

        if ($order->reminded_at) {
            $events[] = [
                'label' => 'Напоминание',
                'time' => $order->reminded_at->format('d.m.Y H:i'),
                'description' => 'Отправлено автоматическое напоминание.',
            ];
        }

        if ($order->actual_started_at) {
            $events[] = [
                'label' => 'Начало работы',
                'time' => $order->actual_started_at->format('d.m.Y H:i'),
                'description' => 'Мастер начал обслуживание.',
            ];
        }

        if ($order->actual_finished_at) {
            $events[] = [
                'label' => 'Завершено',
                'time' => $order->actual_finished_at->format('d.m.Y H:i'),
                'description' => 'Визит завершён.',
            ];
        }

        if ($order->cancelled_at) {
            $events[] = [
                'label' => 'Отменено',
                'time' => $order->cancelled_at->format('d.m.Y H:i'),
                'description' => $order->cancellation_reason ?: 'Отменено мастером.',
            ];
        }

        if ($order->reschedule_count > 0 && $order->rescheduled_from) {
            $events[] = [
                'label' => 'Перенос',
                'time' => $order->rescheduled_from->format('d.m.Y H:i'),
                'description' => 'Запись была перенесена.',
            ];
        }

        return $events;
    }

    protected function buildActionAvailability(Order $order): array
    {
        $now = Carbon::now();
        $scheduledAt = $order->scheduled_at;
        $isToday = $scheduledAt ? $scheduledAt->isSameDay($now) : false;
        $startsSoon = $scheduledAt ? $scheduledAt->greaterThan($now) : false;
        $hoursDiff = $scheduledAt ? $now->diffInHours($scheduledAt, false) : null;

        return [
            'can_start_now' => $isToday,
            'start_warning' => $startsSoon && $hoursDiff !== null && $hoursDiff > 1,
            'can_complete' => in_array($order->status, ['in_progress', 'confirmed']),
            'can_reschedule' => !in_array($order->status, ['completed', 'cancelled']),
            'can_cancel' => !in_array($order->status, ['completed', 'cancelled']),
        ];
    }

    protected function decorateOrder(Order $order): array
    {
        return $this->transformOrder($order) + [
            'history' => $this->buildHistory($order),
            'actions' => $this->buildActionAvailability($order),
        ];
    }
}
