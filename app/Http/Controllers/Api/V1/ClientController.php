<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientFilterRequest;
use App\Http\Requests\ClientFormRequest;
use App\Models\Client;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
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
            ],
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

        if ($client->email) {
            $channels[] = ['key' => 'email', 'label' => 'Email'];
        }

        return $channels;
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
        $ordersQuery = Order::where('master_id', $client->user_id)
            ->where('client_id', $client->id);

        $completedQuery = (clone $ordersQuery)->where('status', 'completed');
        $upcomingQuery = (clone $ordersQuery)->where('scheduled_at', '>', Carbon::now())->orderBy('scheduled_at');
        $lastVisitOrder = (clone $ordersQuery)->orderByDesc('scheduled_at')->first();

        $totalOrders = $ordersQuery->count();
        $completedOrders = $completedQuery->count();
        $totalRevenue = (float) ((clone $completedQuery)->sum('total_price'));
        $upcomingOrder = $upcomingQuery->first();

        return [
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'lifetime_value' => $totalRevenue,
            'upcoming_visit_at' => $upcomingOrder?->scheduled_at?->toIso8601String(),
            'upcoming_visit_formatted' => $upcomingOrder?->scheduled_at?->format('d.m.Y H:i'),
            'last_visit_from_orders' => $lastVisitOrder?->scheduled_at?->toIso8601String(),
            'last_visit_from_orders_formatted' => $lastVisitOrder?->scheduled_at?->format('d.m.Y H:i'),
        ];
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
}
