<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientPortalBookAppointmentRequest;
use App\Http\Requests\ClientPortalServicesRequest;
use App\Http\Requests\ClientPortalSlotsRequest;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\User;
use App\Services\Booking\AvailabilityService;
use App\Services\NotificationService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly NotificationService $notifications,
        private readonly OrderService $orderService,
    ) {}

    public function categories(): JsonResponse
    {
        /** @var Client $client */
        $client = request()->user();
        $masterId = (int) $client->user_id;

        $categories = ServiceCategory::query()
            ->where('user_id', $masterId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'data' => [
                'categories' => $categories,
            ],
        ]);
    }

    public function services(ClientPortalServicesRequest $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();
        $masterId = (int) $client->user_id;
        $validated = $request->validated();

        $query = Service::query()
            ->where('user_id', $masterId)
            ->orderBy('name');

        if (! empty($validated['category_id'])) {
            $query->where('category_id', (int) $validated['category_id']);
        }

        if (! empty($validated['search'])) {
            $search = trim((string) $validated['search']);
            $query->where('name', 'like', '%' . $search . '%');
        }

        $services = $query->get(['id', 'category_id', 'name', 'base_price', 'duration_min']);

        return response()->json([
            'data' => [
                'services' => $services,
            ],
        ]);
    }

    public function slots(Service $service, ClientPortalSlotsRequest $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();
        $masterId = (int) $client->user_id;
        $masterTimezone = $this->resolveMasterTimezone($masterId);

        if ((int) $service->user_id !== $masterId) {
            return response()->json([
                'error' => [
                    'code' => 'forbidden',
                    'message' => __('client_portal.auth.unauthorized'),
                ],
            ], 403);
        }

        $date = (string) $request->validated()['date'];
        $setting = Setting::query()->where('user_id', $masterId)->first();

        $slots = $this->availability->availableSlotsForDate($masterId, $service, $date, $setting, $masterTimezone);

        return response()->json([
            'data' => [
                'date' => $date,
                'service_id' => $service->id,
                'slots' => $slots,
            ],
        ]);
    }

    public function book(ClientPortalBookAppointmentRequest $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();
        $masterId = (int) $client->user_id;
        $masterTimezone = $this->resolveMasterTimezone($masterId);
        $validated = $request->validated();

        $service = Service::query()->findOrFail((int) $validated['service_id']);

        if ((int) $service->user_id !== $masterId) {
            return response()->json([
                'error' => [
                    'code' => 'forbidden',
                    'message' => __('client_portal.auth.unauthorized'),
                ],
            ], 403);
        }

        $date = (string) $validated['date'];
        $time = (string) $validated['time'];
        $setting = Setting::query()->where('user_id', $masterId)->first();

        $available = $this->availability->availableSlotsForDate($masterId, $service, $date, $setting, $masterTimezone);
        if (! in_array($time, $available, true)) {
            return response()->json([
                'error' => [
                    'code' => 'slot_unavailable',
                    'message' => __('client_portal.booking.slot_unavailable'),
                ],
            ], 422);
        }

        $startsAtLocal = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time, $masterTimezone);
        $startsAt = $startsAtLocal->copy()->timezone(config('app.timezone'));
        $endsAt = $startsAt->copy()->addMinutes((int) ($service->duration_min ?? 60));

        $clientUser = $this->resolveOrCreateClientUser($client);

        // Create order so master sees it in calendar/orders UI (Telegram bot uses orders as canonical bookings).
        $order = Order::query()->create([
            'master_id' => $masterId,
            'client_id' => $clientUser->id,
            'services' => [[
                'id' => $service->id,
                'name' => $service->name,
                'price' => (float) ($service->base_price ?? 0),
                'duration' => (int) ($service->duration_min ?? 60),
            ]],
            'scheduled_at' => $startsAtLocal->copy()->timezone(config('app.timezone')),
            'duration_forecast' => (int) ($service->duration_min ?? 60),
            'total_price' => (float) ($service->base_price ?? 0),
            'status' => 'new',
            'note' => $validated['note'] ?? null,
            'source' => 'client_portal',
        ]);

        $this->orderService->scheduleStartReminder($order);

        $appointment = Appointment::query()->create([
            'user_id' => $masterId,
            'client_id' => $client->id,
            'service_ids' => [$service->id],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'scheduled',
            'meta' => [
                'source' => 'client_portal',
                'note' => $validated['note'] ?? null,
                'order_id' => $order->id,
            ],
        ]);

        $this->notifyMaster($masterId, $client, $service, $startsAtLocal);

        return response()->json([
            'data' => [
                'appointment' => $appointment,
            ],
        ], 201);
    }

    private function resolveMasterTimezone(int $masterId): string
    {
        $master = User::query()->find($masterId);
        return $master?->timezone ?: config('app.timezone');
    }

    private function resolveOrCreateClientUser(Client $client): User
    {
        $email = $client->email ? trim((string) $client->email) : null;
        if ($email === '') {
            $email = null;
        }

        $normalizedPhone = $this->normalizePhoneForUser((string) $client->phone);

        $user = null;

        if ($email) {
            $user = User::query()->where('email', $email)->first();
        }

        if (! $user && $normalizedPhone !== '') {
            $user = User::query()->where('phone', $normalizedPhone)->first();
        }

        if (! $user) {
            $user = User::query()->create([
                'name' => $client->name ?: 'Client ' . Str::substr($normalizedPhone, -4),
                'email' => $email,
                'phone' => $normalizedPhone !== '' ? $normalizedPhone : null,
                'password' => Str::random(24),
            ]);
        } else {
            $user->forceFill([
                'name' => $client->name ?: $user->name,
                'email' => $email ?: $user->email,
                'phone' => $normalizedPhone !== '' ? $normalizedPhone : $user->phone,
            ])->save();
        }

        return $user;
    }

    private function normalizePhoneForUser(string $phone): string
    {
        // Keep consistent with OrderController normalization (RU numbers stored as +7...).
        $digits = preg_replace('/[^0-9]+/', '', $phone);
        $digits = is_string($digits) ? $digits : '';

        if ($digits === '') {
            return '';
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

    private function notifyMaster(int $masterId, Client $client, Service $service, Carbon $startsAtLocal): void
    {
        $datetime = $startsAtLocal->translatedFormat('d.m.Y H:i');

        $this->notifications->send(
            $masterId,
            __('client_portal.booking.master_notification_title'),
            __('client_portal.booking.master_notification_message', [
                'client' => $client->name ?: __('calendar.unnamed_client'),
                'service' => $service->name,
                'datetime' => $datetime,
            ]),
            '/calendar',
        );
    }
}
