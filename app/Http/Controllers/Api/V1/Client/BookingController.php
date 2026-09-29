<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientPortalWaitlistRequest;
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
use App\Services\Booking\ClientBookingService;
use App\Services\Booking\SlotUnavailableException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    private const DEFAULT_BOOKING_DURATION_MINUTES = ClientBookingService::DEFAULT_DURATION_MINUTES;

    private const UNSPECIFIED_SERVICE_LABEL = ClientBookingService::UNSPECIFIED_SERVICE_LABEL;

    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly ClientBookingService $booking,
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

    public function genericSlots(ClientPortalSlotsRequest $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();
        $masterId = (int) $client->user_id;
        $masterTimezone = $this->resolveMasterTimezone($masterId);
        $date = (string) $request->validated()['date'];
        $setting = Setting::query()->where('user_id', $masterId)->first();

        $slots = $this->availability->availableSlotsForDate(
            $masterId,
            null,
            $date,
            $setting,
            $masterTimezone,
            self::DEFAULT_BOOKING_DURATION_MINUTES,
        );

        return response()->json([
            'data' => [
                'date' => $date,
                'service_id' => null,
                'duration_min' => self::DEFAULT_BOOKING_DURATION_MINUTES,
                'slots' => $slots,
            ],
        ]);
    }

    /**
     * Newest-scheduled first, in the master's own timezone — the same one
     * `book()`'s slots are already quoted in, so a client sees the same
     * 10:00 they picked, not a UTC-shifted time.
     */
    public function appointments(): JsonResponse
    {
        /** @var Client $client */
        $client = request()->user();
        $masterId = (int) $client->user_id;
        $masterTimezone = $this->resolveMasterTimezone($masterId);
        $now = Carbon::now($masterTimezone);

        $appointments = Appointment::query()
            ->where('user_id', $masterId)
            ->where('client_id', $client->id)
            ->orderByDesc('starts_at')
            ->get();

        $payload = $appointments->map(function (Appointment $appointment) use ($masterTimezone, $now) {
            $startsAt = $appointment->starts_at?->copy()->timezone($masterTimezone);

            return [
                'id' => $appointment->id,
                'status' => $appointment->status,
                'service_label' => Arr::get($appointment->meta, 'service_label', self::UNSPECIFIED_SERVICE_LABEL),
                'date' => $startsAt?->toDateString(),
                'time' => $startsAt?->format('H:i'),
                'is_upcoming' => $startsAt !== null && $startsAt->greaterThanOrEqualTo($now),
            ];
        });

        return response()->json([
            'data' => [
                'appointments' => $payload,
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
        $validated = $request->validated();
        $service = $this->resolveBookingService($masterId, Arr::get($validated, 'service_id'));

        try {
            $booked = $this->booking->book(
                $masterId,
                $client,
                $service,
                (string) $validated['date'],
                (string) $validated['time'],
                $validated['note'] ?? null,
                'client_portal',
            );
        } catch (SlotUnavailableException) {
            return response()->json([
                'error' => [
                    'code' => 'slot_unavailable',
                    'message' => __('client_portal.booking.slot_unavailable'),
                ],
            ], 422);
        }

        return response()->json([
            'data' => [
                'appointment' => $booked['appointment'],
            ],
        ], 201);
    }

    public function waitlist(ClientPortalWaitlistRequest $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();
        $masterId = (int) $client->user_id;
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

        $clientUser = $this->booking->resolveOrCreateClientUser($client);

        $entry = \App\Models\WaitlistEntry::query()->create([
            'user_id' => $masterId,
            'client_id' => $client->id,
            'client_user_id' => $clientUser->id,
            'service_id' => $service->id,
            'preferred_slots' => [],
            'preferred_dates' => collect($validated['preferred_dates'])->map(fn ($date) => Carbon::parse($date)->toDateString())->values()->all(),
            'preferred_time_windows' => $validated['preferred_time_windows'] ?? [],
            'flexibility_days' => (int) ($validated['flexibility_days'] ?? 0),
            'priority' => 0,
            'priority_manual' => 0,
            'status' => 'pending',
            'source' => 'client_portal',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'data' => [
                'waitlist_entry_id' => $entry->id,
            ],
            'message' => __('waitlist.messages.created'),
        ], 201);
    }

    private function resolveMasterTimezone(int $masterId): string
    {
        $master = User::query()->find($masterId);
        return $master?->timezone ?: config('app.timezone');
    }

    private function resolveBookingService(int $masterId, mixed $serviceId): ?Service
    {
        $serviceId = is_numeric($serviceId) ? (int) $serviceId : 0;

        if ($serviceId <= 0) {
            return null;
        }

        return Service::query()
            ->where('user_id', $masterId)
            ->findOrFail($serviceId);
    }
}
