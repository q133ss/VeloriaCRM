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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
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
                'recommended_services' => $recommended->map(fn ($service) => Arr::only($service, ['id', 'name', 'description']))->all(),
            ]);
        });

        $order->load(['client', 'master']);

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

        DB::transaction(function () use ($validated, $order, $masterId) {
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

            $order->update([
                'master_id' => $masterId,
                'client_id' => $client->id,
                'services' => $servicePayload,
                'scheduled_at' => Carbon::parse($validated['scheduled_at']),
                'note' => Arr::get($validated, 'note'),
                'duration_forecast' => $services->sum('duration_min') ?: null,
                'total_price' => $totalPrice ?? 0,
                'status' => $validated['status'],
                'recommended_services' => $recommended->map(fn ($service) => Arr::only($service, ['id', 'name', 'description']))->all(),
            ]);
        });

        $order->refresh()->loadMissing(['client', 'master']);

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
            $client = $this->resolveClient($validated['client_phone'], Arr::get($validated, 'client_name'));
            $services = $this->getUserServices(3);
            $recommended = $this->buildRecommendedServices($client, $services);

            return Order::create([
                'master_id' => $masterId,
                'client_id' => $client->id,
                'services' => [],
                'scheduled_at' => Carbon::parse($validated['scheduled_at']),
                'note' => Arr::get($validated, 'note'),
                'total_price' => 0,
                'status' => 'new',
                'recommended_services' => $recommended->map(fn ($item) => Arr::only($item, ['id', 'name', 'description']))->all(),
            ]);
        });

        $order->load(['client', 'master']);

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

    public function start(Order $order): JsonResponse
    {
        $this->ensureOrderBelongsToCurrentUser($order);
        $now = Carbon::now();

        $order->update([
            'status' => 'in_progress',
            'actual_started_at' => $now,
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
        ]);

        $order->refresh();

        return response()->json([
            'data' => $this->decorateOrder($order),
            'message' => 'Запись перенесена.',
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $services = $this->getUserServices();
        $client = null;

        if ($request->filled('client_id')) {
            $client = User::find($request->input('client_id'));
        } elseif ($request->filled('client_phone')) {
            $client = User::where('phone', $this->normalizePhone($request->input('client_phone')))->first();
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
            'recommended_services' => $recommended->map(function ($service) {
                return [
                    'id' => $service['id'] ?? null,
                    'name' => $service['name'] ?? 'Услуга',
                    'description' => $service['description'] ?? null,
                ];
            })->values(),
        ]);
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

        $user = User::where('phone', $normalizedPhone)->first();

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
                'email' => $email,
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

    protected function buildRecommendedServices(?User $client, $services)
    {
        $suggestions = $services->take(3);

        if ($suggestions->isEmpty()) {
            return collect([
                [
                    'id' => null,
                    'name' => 'Персонализированная консультация',
                    'description' => 'ИИ предложит услугу исходя из предпочтений клиента (заглушка).',
                ],
            ]);
        }

        return $suggestions->map(function (Service $service) use ($client) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'description' => 'Заглушка рекомендации ИИ на основе предыдущих визитов ' . ($client?->name ?? 'клиента') . '.',
            ];
        });
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

        return $user->plans()->whereIn('name', ['PRO', 'Pro', 'Elite', 'ELITE'])->exists();
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

        $recommended = collect($order->recommended_services ?? [])->map(function ($service) {
            return [
                'id' => $service['id'] ?? null,
                'name' => $service['name'] ?? null,
                'description' => $service['description'] ?? null,
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
