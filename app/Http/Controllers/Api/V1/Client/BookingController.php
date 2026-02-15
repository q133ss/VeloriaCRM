<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientPortalBookAppointmentRequest;
use App\Http\Requests\ClientPortalServicesRequest;
use App\Http\Requests\ClientPortalSlotsRequest;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Services\Booking\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class BookingController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability)
    {
    }

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

        $slots = $this->availability->availableSlotsForDate($masterId, $service, $date, $setting);

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

        $available = $this->availability->availableSlotsForDate($masterId, $service, $date, $setting);
        if (! in_array($time, $available, true)) {
            return response()->json([
                'error' => [
                    'code' => 'slot_unavailable',
                    'message' => __('client_portal.booking.slot_unavailable'),
                ],
            ], 422);
        }

        $timezone = config('app.timezone');
        $startsAt = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time, $timezone);
        $endsAt = $startsAt->copy()->addMinutes((int) ($service->duration_min ?? 60));

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
            ],
        ]);

        return response()->json([
            'data' => [
                'appointment' => $appointment,
            ],
        ], 201);
    }
}

