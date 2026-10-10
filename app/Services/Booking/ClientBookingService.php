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
use App\Models\Payment;
use App\Services\OrderService;
use App\Services\Prepayment\PrepaymentPolicyService;
use App\Services\YooKassaService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        private readonly PrepaymentPolicyService $prepayments,
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
     * When the master's rules ask for a prepayment the booking is held, unpaid,
     * for the hold time: it blocks the slot like any order, but nobody is told
     * about it until completePaidBooking() runs. `$returnUrl` may contain
     * `{token}`, replaced by an unguessable token for the payment.
     *
     * @throws SlotUnavailableException when the time is not free any more
     * @throws PrepaymentFailedException when ЮKassa refuses the payment (nothing is kept)
     *
     * @return array{order: Order, appointment: Appointment, starts_at_local: Carbon, service_label: string, payment: ?array{id:int,token:string,amount:float,confirmation_url:?string,expires_at:Carbon}}
     */
    public function book(
        int $masterId,
        Client $client,
        ?Service $service,
        string $date,
        string $time,
        ?string $note,
        string $source,
        ?string $returnUrl = null,
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
        $totalPrice = $service ? (float) ($service->base_price ?? 0) : 0.0;

        $requirement = $this->prepayments->resolve(
            $masterId,
            $client->prepay_override,
            $clientUser->id,
            $service,
            $startsAtLocal,
            $totalPrice,
        );

        // Order, appointment and payment stand or fall together: if ЮKassa
        // refuses, the slot must not stay held by a booking nobody can pay.
        $result = DB::transaction(function () use (
            $masterId, $client, $clientUser, $service, $startsAt, $startsAtLocal, $duration,
            $totalPrice, $note, $source, $serviceLabel, $requirement, $returnUrl,
        ) {
            // The order is what the master sees in the calendar and orders list.
            $order = Order::query()->create([
                'master_id' => $masterId,
                'client_id' => $clientUser->id,
                'services' => $this->orderServicePayload($service, $duration),
                'scheduled_at' => $startsAtLocal->copy()->timezone(config('app.timezone')),
                'duration_forecast' => $duration,
                'total_price' => $totalPrice,
                'status' => 'new',
                'note' => $note,
                'source' => $source,
                'payment_status' => $requirement ? 'awaiting' : null,
                'prepay_expires_at' => $requirement ? now()->addMinutes($requirement->holdMinutes) : null,
                'prepay_rule' => $requirement?->rule,
            ]);

            $appointment = Appointment::query()->create([
                'user_id' => $masterId,
                'client_id' => $client->id,
                'service_ids' => $service ? [$service->id] : [],
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMinutes($duration),
                'status' => 'scheduled',
                'deposit_amount' => $requirement?->amount ?? 0,
                'meta' => [
                    'source' => $source,
                    'note' => $note,
                    'order_id' => $order->id,
                    'service_label' => $serviceLabel,
                    'service_specified' => $service !== null,
                    'awaiting_payment' => $requirement !== null,
                ],
            ]);

            $payment = $requirement
                ? $this->createPrepayment($order, $appointment, $client, $requirement->amount, $serviceLabel, $startsAtLocal, $returnUrl)
                : null;

            return [$order, $appointment, $payment];
        });

        [$order, $appointment, $payment] = $result;

        // An unpaid booking is announced only once it is paid.
        if (! $payment) {
            $this->orderService->scheduleStartReminder($order);
            $this->notifyMaster($masterId, $client, $serviceLabel, $startsAtLocal);
            $this->clientNotifications->notifyBookingConfirmed($client, $serviceLabel, $startsAtLocal);
        }

        return [
            'order' => $order,
            'appointment' => $appointment,
            'starts_at_local' => $startsAtLocal,
            'service_label' => $serviceLabel,
            'payment' => $payment ? [
                'id' => $payment->id,
                'token' => $payment->return_token,
                'amount' => (float) $payment->amount,
                'confirmation_url' => $payment->confirmation_url,
                'expires_at' => $order->prepay_expires_at,
            ] : null,
        ];
    }

    /**
     * The booking has been paid: from here it is an ordinary booking, so the
     * start reminder is scheduled and the master and the client are told.
     */
    public function completePaidBooking(Payment $payment): void
    {
        $order = $payment->order;
        $meta = (array) $payment->metadata;
        $client = Client::query()->find($meta['client_card_id'] ?? 0);

        if (! $order || ! $client) {
            return;
        }

        $startsAtLocal = $order->scheduled_at->copy()->timezone($this->masterTimezone($order->master_id));
        $label = (string) ($meta['service_label'] ?? self::UNSPECIFIED_SERVICE_LABEL);

        $this->orderService->scheduleStartReminder($order);
        $this->notifyMaster($order->master_id, $client, $label, $startsAtLocal);
        $this->clientNotifications->notifyBookingConfirmed($client, $label, $startsAtLocal);
    }

    /**
     * The booking's appointment (what the client sees, and what the slot
     * search counts as busy) goes with its order: without this a cancelled
     * order kept the time blocked.
     */
    public function cancelAppointmentFor(Order $order): void
    {
        $times = array_filter([$order->scheduled_at, $order->rescheduled_from]);

        if ($times === []) {
            return;
        }

        Appointment::query()
            ->where('user_id', $order->master_id)
            ->whereIn('starts_at', $times)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->filter(fn (Appointment $appointment) => (int) ($appointment->meta['order_id'] ?? 0) === (int) $order->id)
            ->each(fn (Appointment $appointment) => $appointment->update(['status' => 'cancelled']));
    }

    private function createPrepayment(
        Order $order,
        Appointment $appointment,
        Client $client,
        float $amount,
        string $serviceLabel,
        Carbon $startsAtLocal,
        ?string $returnUrl,
    ): Payment {
        $token = Str::random(40);
        $returnUrl = str_replace('{token}', $token, $returnUrl ?? url('/pay/return/{token}'));

        try {
            $created = YooKassaService::forMaster(Setting::query()->where('user_id', $order->master_id)->first())
                ->createBookingPayment(
                    $order,
                    $amount,
                    $returnUrl,
                    __('prepayment.payment_description', [
                        'service' => $serviceLabel,
                        'datetime' => $startsAtLocal->translatedFormat('d.m.Y H:i'),
                    ]),
                );
        } catch (\Throwable $exception) {
            Log::warning('Could not create a booking prepayment', [
                'order_id' => $order->id,
                'master_id' => $order->master_id,
                'exception' => $exception->getMessage(),
            ]);

            throw new PrepaymentFailedException($exception->getMessage(), 0, $exception);
        }

        return Payment::query()->create([
            'user_id' => $order->master_id,
            'order_id' => $order->id,
            'provider' => 'yookassa',
            'provider_payment_id' => $created['id'],
            'amount' => $amount,
            'status' => $created['status'] ?: Payment::STATUS_PENDING,
            'confirmation_url' => $created['confirmation_url'],
            'return_token' => $token,
            'metadata' => [
                'client_card_id' => $client->id,
                'appointment_id' => $appointment->id,
                'service_label' => $serviceLabel,
            ],
        ]);
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
