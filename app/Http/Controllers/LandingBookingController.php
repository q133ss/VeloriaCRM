<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLandingClient;
use App\Http\Requests\LandingBookRequest;
use App\Models\Landing;
use App\Models\LandingRequest;
use App\Models\Order;
use App\Models\Service;
use App\Services\Booking\ClientBookingService;
use App\Services\Booking\SlotUnavailableException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Public booking from a landing page: the visitor sees the master's free
 * windows (from her schedule in settings, minus what is already booked) and
 * takes one; the booking lands in the master's calendar like any other.
 */
class LandingBookingController extends Controller
{
    /** Bookings one phone may hold at once on one master; a guard against a form being spammed. */
    private const MAX_UPCOMING_PER_PHONE = 3;

    public function __construct(private readonly ClientBookingService $booking)
    {
    }

    use ResolvesLandingClient;

    public function availability(Request $request, string $slug): JsonResponse
    {
        $landing = $this->activeLanding($slug);
        $data = $request->validate([
            'service_id' => ['nullable', 'integer'],
            'days' => ['nullable', 'integer', 'min:1', 'max:21'],
        ]);

        $service = $this->resolveOfferedService($landing, $data['service_id'] ?? null);

        return response()->json([
            'data' => [
                'timezone' => $this->booking->masterTimezone($landing->user_id),
                'duration_min' => $this->booking->durationFor($service),
                'days' => $this->booking->upcomingDays($landing->user_id, $service, (int) ($data['days'] ?? 14)),
            ],
        ]);
    }

    public function book(LandingBookRequest $request, string $slug): JsonResponse
    {
        $landing = $this->activeLanding($slug);
        $validated = $request->validated();
        $service = $this->resolveOfferedService($landing, $validated['service_id'] ?? null);

        $client = $this->resolveLandingClient($landing, $validated);

        $this->guardPhoneLimit($landing, $client);

        try {
            $booked = $this->booking->book(
                $landing->user_id,
                $client,
                $service,
                $validated['date'],
                $validated['time'],
                $validated['message'] ?? null,
                'landing',
            );
        } catch (SlotUnavailableException) {
            throw ValidationException::withMessages(['time' => __('landings.booking.slot_taken')]);
        }

        // Kept with the landing's other requests so its counters and the
        // "requests" list on the edit page include people who booked outright.
        LandingRequest::query()->create([
            'landing_id' => $landing->id,
            'user_id' => $landing->user_id,
            'client_id' => $client->id,
            'service_id' => $service?->id,
            'client_name' => $validated['client_name'],
            'client_phone' => $validated['client_phone'],
            'preferred_date' => $validated['date'],
            'message' => $validated['message'] ?? null,
            'status' => 'booked',
            'meta' => [
                'landing_title' => $landing->title,
                'service_name' => $service?->name,
                'source' => 'public_landing',
                'order_id' => $booked['order']->id,
                'appointment_id' => $booked['appointment']->id,
                'time' => $validated['time'],
            ],
        ]);

        $start = $booked['starts_at_local'];

        return response()->json([
            'message' => __('landings.booking.booked'),
            'data' => [
                'kind' => 'booked',
                'date' => $start->toDateString(),
                'date_label' => $start->translatedFormat('j F, l'),
                'time' => $start->format('H:i'),
                'service' => $service?->name,
                'address' => data_get($landing->settings, 'address'),
            ],
        ], 201);
    }

    private function activeLanding(string $slug): Landing
    {
        return Landing::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
    }

    /** A service the visitor asks for must be one this landing offers; none is fine. */
    private function resolveOfferedService(Landing $landing, mixed $serviceId): ?Service
    {
        $id = is_numeric($serviceId) ? (int) $serviceId : 0;

        if ($id <= 0) {
            return null;
        }

        if (! $landing->offersService($id)) {
            throw ValidationException::withMessages(['service_id' => __('landings.validation.service_exists')]);
        }

        return Service::query()->where('user_id', $landing->user_id)->find($id);
    }

    private function guardPhoneLimit(Landing $landing, $client): void
    {
        $userId = $client->client_user_id;

        if (! $userId) {
            return;
        }

        $upcoming = Order::query()
            ->where('master_id', $landing->user_id)
            ->where('client_id', $userId)
            ->where('source', 'landing')
            ->where('scheduled_at', '>=', now())
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();

        if ($upcoming >= self::MAX_UPCOMING_PER_PHONE) {
            throw ValidationException::withMessages(['client_phone' => __('landings.booking.too_many')]);
        }
    }
}
