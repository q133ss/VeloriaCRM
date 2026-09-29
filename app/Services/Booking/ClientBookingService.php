<?php

namespace App\Services\Booking;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\ClientNotificationService;
use App\Services\NotificationService;
use App\Services\OrderService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Turns "this client wants this time" into the master's real booking: an order
 * (what the calendar shows) plus an appointment (what the client sees), with the
 * availability and conflict checks in front of it.
 *
 * Shared by the client app (Api\V1\Client\BookingController) and the public
 * landing pages, so both create bookings the same way.
 */
class ClientBookingService
{
    public const DEFAULT_DURATION_MINUTES = 60;

    public const UNSPECIFIED_SERVICE_LABEL = 'Услуга уточняется';

    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly BookingConflictService $conflicts,
        private readonly NotificationService $notifications,
        private readonly ClientNotificationService $clientNotifications,
        private readonly OrderService $orderService,
    ) {
    }

    public function masterTimezone(int $masterId): string
    {
        return User::query()->find($masterId)?->timezone ?: config('app.timezone');
    }

    public function durationFor(?Service $service): int
    {
        return (int) ($service?->duration_min ?: self::DEFAULT_DURATION_MINUTES);
    }

    /**
     * @return array<int, string> free HH:MM slots on the date, in the master's timezone
     */
    public function slots(int $masterId, ?Service $service, string $date): array
    {
        return $this->availability->availableSlotsForDate(
            $masterId,
            $service,
            $date,
            Setting::query()->where('user_id', $masterId)->first(),
            $this->masterTimezone($masterId),
            $this->durationFor($service),
        );
    }

    /**
     * Free slots for the next days, only the days that have any.
     *
     * @return array<int, array{date: string, slots: array<int, string>}>
     */
    public function upcomingDays(int $masterId, ?Service $service, int $days = 14): array
    {
        $timezone = $this->masterTimezone($masterId);
        $setting = Setting::query()->where('user_id', $masterId)->first();
        $duration = $this->durationFor($service);
        $today = Carbon::now($timezone)->startOfDay();
        $result = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $today->copy()->addDays($i)->toDateString();
            $slots = $this->availability->availableSlotsForDate($masterId, $service, $date, $setting, $timezone, $duration);

            if ($slots !== []) {
                $result[] = ['date' => $date, 'slots' => $slots];
            }
        }

        return $result;
    }

    /**
     * @throws SlotUnavailableException when the time is not free any more
     *
     * @return array{order: Order, appointment: Appointment, starts_at_local: Carbon, service_label: string}
     */
    public function book(
        int $masterId,
        Client $client,
        ?Service $service,
        string $date,
        string $time,
        ?string $note,
        string $source,
    ): array {
        $timezone = $this->masterTimezone($masterId);
        $duration = $this->durationFor($service);
        $serviceLabel = $service?->name ?: self::UNSPECIFIED_SERVICE_LABEL;

        if (! in_array($time, $this->slots($masterId, $service, $date), true)) {
            throw new SlotUnavailableException();
        }

        $startsAtLocal = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time, $timezone);
        $startsAt = $startsAtLocal->copy()->timezone(config('app.timezone'));

        if ($this->conflicts->detectConflict($masterId, $startsAt, $duration) !== null) {
            throw new SlotUnavailableException();
        }

        $clientUser = $this->resolveOrCreateClientUser($client);

        // The order is what the master sees in the calendar and orders list.
        $order = Order::query()->create([
            'master_id' => $masterId,
            'client_id' => $clientUser->id,
            'services' => $this->orderServicePayload($service, $duration),
            'scheduled_at' => $startsAtLocal->copy()->timezone(config('app.timezone')),
            'duration_forecast' => $duration,
            'total_price' => $service ? (float) ($service->base_price ?? 0) : 0,
            'status' => 'new',
            'note' => $note,
            'source' => $source,
        ]);

        $this->orderService->scheduleStartReminder($order);

        $appointment = Appointment::query()->create([
            'user_id' => $masterId,
            'client_id' => $client->id,
            'service_ids' => $service ? [$service->id] : [],
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes($duration),
            'status' => 'scheduled',
            'meta' => [
                'source' => $source,
                'note' => $note,
                'order_id' => $order->id,
                'service_label' => $serviceLabel,
                'service_specified' => $service !== null,
            ],
        ]);

        $this->notifyMaster($masterId, $client, $serviceLabel, $startsAtLocal);
        $this->clientNotifications->notifyBookingConfirmed($client, $serviceLabel, $startsAtLocal);

        return [
            'order' => $order,
            'appointment' => $appointment,
            'starts_at_local' => $startsAtLocal,
            'service_label' => $serviceLabel,
        ];
    }

    public function resolveOrCreateClientUser(Client $client): User
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

        // Record which account this card belongs to, so the master's dashboard
        // and analytics can join her cards to the orders booked against them.
        if ($client->client_user_id !== $user->id) {
            $client->forceFill(['client_user_id' => $user->id])->save();
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

    /**
     * A booking with no service chosen keeps an empty snapshot, the same as one
     * the master makes by hand for a client who has not decided.
     *
     * It used to write a stand-in service called «Услуга уточняется», which read
     * well on screen and lied to everything counting a client's history: the
     * return-message draft picked it as her usual service and wrote «в прошлый
     * раз делали "Услуга уточняется"» to her. The fact that no service was named
     * lives in appointments.meta, where it belongs.
     *
     * @return array<int, array{id:int,name:string,price:float,duration:int}>
     */
    private function orderServicePayload(?Service $service, int $durationMinutes): array
    {
        if (! $service) {
            return [];
        }

        return [[
            'id' => $service->id,
            'name' => $service->name,
            'price' => (float) ($service->base_price ?? 0),
            'duration' => $durationMinutes,
        ]];
    }

    private function notifyMaster(int $masterId, Client $client, string $serviceLabel, Carbon $startsAtLocal): void
    {
        $this->notifications->send(
            $masterId,
            __('client_portal.booking.master_notification_title'),
            __('client_portal.booking.master_notification_message', [
                'client' => $client->name ?: __('calendar.unnamed_client'),
                'service' => $serviceLabel,
                'datetime' => $startsAtLocal->translatedFormat('d.m.Y H:i'),
            ]),
            '/calendar',
        );
    }
}
