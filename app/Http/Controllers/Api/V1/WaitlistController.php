<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WaitlistMatchRequest;
use App\Http\Requests\WaitlistStoreRequest;
use App\Http\Requests\WaitlistUpdateRequest;
use App\Models\Client;
use App\Models\Service;
use App\Models\WaitlistEntry;
use App\Services\WaitlistMatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class WaitlistController extends Controller
{
    public function __construct(
        private readonly WaitlistMatchService $matches,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = WaitlistEntry::query()
            ->with(['client', 'clientUser', 'service'])
            ->where('user_id', $this->currentUserId())
            ->orderByDesc('priority_manual')
            ->latest();

        if ($request->filled('date')) {
            $query->whereJsonContains('preferred_dates', Carbon::parse((string) $request->query('date'))->toDateString());
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }

        return response()->json([
            'data' => $query->get()->map(fn (WaitlistEntry $entry) => $this->transformEntry($entry))->values(),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $masterId = $this->currentUserId();
        $query = trim((string) $request->query('client_search', $request->query('client_phone', '')));

        $services = Service::query()
            ->where('user_id', $masterId)
            ->orderBy('name')
            ->get(['id', 'name', 'base_price', 'duration_min']);

        $clients = Client::query()->where('user_id', $masterId);
        if ($query !== '') {
            $digits = preg_replace('/[^0-9]+/', '', $query);
            $clients->where(function ($builder) use ($query, $digits) {
                $builder->where('name', 'like', '%' . $query . '%')
                    ->orWhere('phone', 'like', '%' . $query . '%');

                if ($digits !== '') {
                    $builder->orWhere('phone', 'like', '%' . $digits . '%');
                }
            });
        }

        $recentClients = Client::query()
            ->where('user_id', $masterId)
            ->latest('last_visit_at')
            ->limit(6)
            ->get();

        return response()->json([
            'services' => $services->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'price' => (float) $service->base_price,
                'duration' => (int) $service->duration_min,
            ])->values(),
            'recent_clients' => $recentClients->map(fn (Client $client) => $this->transformClient($client))->values(),
            'suggestions' => $clients->orderBy('name')->limit(8)->get()->map(fn (Client $client) => $this->transformClient($client))->values(),
        ]);
    }

    public function store(WaitlistStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $client = $this->resolveClientProfile($validated);

        $entry = WaitlistEntry::query()->create([
            'user_id' => $this->currentUserId(),
            'client_id' => $client->id,
            'client_user_id' => $validated['client_user_id'] ?? null,
            'service_id' => (int) $validated['service_id'],
            'preferred_slots' => $validated['preferred_slots'] ?? [],
            'preferred_dates' => collect($validated['preferred_dates'])->map(fn ($date) => Carbon::parse($date)->toDateString())->values()->all(),
            'preferred_time_windows' => $validated['preferred_time_windows'] ?? [],
            'flexibility_days' => (int) ($validated['flexibility_days'] ?? 0),
            'priority' => (int) ($validated['priority_manual'] ?? 0),
            'priority_manual' => (int) ($validated['priority_manual'] ?? 0),
            'status' => 'pending',
            'source' => $validated['source'] ?? 'manual',
            'notes' => $validated['notes'] ?? null,
        ]);

        $entry->load(['client', 'clientUser', 'service']);

        return response()->json([
            'data' => $this->transformEntry($entry),
            'message' => __('waitlist.messages.created'),
        ], 201);
    }

    public function update(WaitlistUpdateRequest $request, WaitlistEntry $waitlist): JsonResponse
    {
        $this->ensureBelongsToCurrentUser($waitlist);
        $payload = $request->validated();

        if (array_key_exists('preferred_dates', $payload)) {
            $payload['preferred_dates'] = collect($payload['preferred_dates'])
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->values()
                ->all();
        }

        if (array_key_exists('priority_manual', $payload)) {
            $payload['priority'] = (int) $payload['priority_manual'];
        }

        $waitlist->update($payload);
        $waitlist->refresh()->load(['client', 'clientUser', 'service']);

        return response()->json([
            'data' => $this->transformEntry($waitlist),
            'message' => __('waitlist.messages.updated'),
        ]);
    }

    public function destroy(WaitlistEntry $waitlist): JsonResponse
    {
        $this->ensureBelongsToCurrentUser($waitlist);
        $waitlist->delete();

        return response()->json([
            'message' => __('waitlist.messages.deleted'),
        ]);
    }

    public function matches(WaitlistMatchRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $slotStart = Carbon::parse($validated['date'] . ' ' . ($validated['time'] ?? '12:00'));

        return response()->json([
            'data' => [
                'slot' => [
                    'date' => Carbon::parse($validated['date'])->toDateString(),
                    'time' => $validated['time'] ?? null,
                    'duration' => (int) ($validated['duration'] ?? 60),
                ],
                'matches' => $this->matches->rankForSlot(
                    $this->currentUserId(),
                    $slotStart,
                    (int) ($validated['duration'] ?? 60),
                    isset($validated['service_id']) ? (int) $validated['service_id'] : null,
                )->values(),
            ],
        ]);
    }

    private function transformEntry(WaitlistEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'status' => $entry->status,
            'source' => $entry->source,
            'notes' => $entry->notes,
            'preferred_dates' => $entry->preferred_dates ?? [],
            'preferred_slots' => $entry->preferred_slots ?? [],
            'preferred_time_windows' => $entry->preferred_time_windows ?? [],
            'flexibility_days' => (int) ($entry->flexibility_days ?? 0),
            'priority_manual' => (int) ($entry->priority_manual ?? 0),
            'match_score' => $entry->match_score !== null ? (float) $entry->match_score : null,
            'match_reasons' => $entry->match_reasons ?? [],
            'client' => $entry->client ? $this->transformClient($entry->client) : null,
            'client_user' => $entry->clientUser ? [
                'id' => $entry->clientUser->id,
                'name' => $entry->clientUser->name,
                'phone' => $entry->clientUser->phone,
                'email' => $entry->clientUser->email,
            ] : null,
            'service' => $entry->service ? [
                'id' => $entry->service->id,
                'name' => $entry->service->name,
                'duration' => (int) $entry->service->duration_min,
                'price' => (float) $entry->service->base_price,
            ] : null,
            'created_at' => optional($entry->created_at)->toIso8601String(),
        ];
    }

    private function transformClient(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
            'email' => $client->email,
            'loyalty_level' => $client->loyalty_level,
            'last_visit_at' => optional($client->last_visit_at)->toIso8601String(),
            'last_visit_at_formatted' => optional($client->last_visit_at)->format('d.m.Y H:i'),
        ];
    }

    private function resolveClientProfile(array $validated): Client
    {
        $masterId = $this->currentUserId();

        if (! empty($validated['client_id'])) {
            return Client::query()
                ->where('user_id', $masterId)
                ->findOrFail((int) $validated['client_id']);
        }

        $phone = trim((string) ($validated['client_phone'] ?? ''));
        $email = trim((string) ($validated['client_email'] ?? ''));

        $client = Client::query()
            ->where('user_id', $masterId)
            ->when($phone !== '', fn ($query) => $query->where('phone', $phone))
            ->when($phone === '' && $email !== '', fn ($query) => $query->where('email', $email))
            ->first();

        if ($client) {
            $client->forceFill([
                'name' => $validated['client_name'] ?? $client->name,
                'phone' => $phone !== '' ? $phone : $client->phone,
                'email' => $email !== '' ? $email : $client->email,
            ])->save();

            return $client;
        }

        return Client::query()->create([
            'user_id' => $masterId,
            'name' => $validated['client_name'] ?: __('calendar.unnamed_client'),
            'phone' => $phone,
            'email' => $email !== '' ? $email : null,
        ]);
    }

    private function ensureBelongsToCurrentUser(WaitlistEntry $waitlist): void
    {
        if ((int) $waitlist->user_id !== $this->currentUserId()) {
            abort(403);
        }
    }

    private function currentUserId(): int
    {
        $userId = Auth::guard('sanctum')->id();

        if (! $userId) {
            abort(403);
        }

        return $userId;
    }
}
