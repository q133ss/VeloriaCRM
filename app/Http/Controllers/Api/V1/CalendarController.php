<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalendarDayRequest;
use App\Http\Requests\CalendarEventRequest;
use App\Models\Client;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\DayScheduleService;
use App\Services\Booking\OrderDurationResolver;
use App\Services\ClientAttendanceService;
use App\Services\ClientIdentityService;
use App\Services\Marketing\ClientChannelResolver;
use App\Services\Orders\OrderActionPolicy;
use App\Services\ScheduleService;
use App\Services\WaitlistMatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
        private readonly AvailabilityService $availability,
        private readonly OrderDurationResolver $durations,
        private readonly DayScheduleService $daySchedule,
        private readonly WaitlistMatchService $waitlist,
        private readonly ClientAttendanceService $attendance,
        private readonly OrderActionPolicy $actions,
        private readonly ClientIdentityService $identities,
        private readonly ClientChannelResolver $channels,
    ) {
    }

    public function events(CalendarEventRequest $request): JsonResponse
    {
        $userId = $this->currentUserId();
        $validated = $request->validated();

        $start = Carbon::parse($validated['start'])->startOfDay();
        $end = Carbon::parse($validated['end'])->endOfDay();

        $orders = Order::with('client')
            ->where('master_id', $userId)
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$start, $end])
            ->orderBy('scheduled_at')
            ->get();

        $events = $orders->map(fn (Order $order) => $this->mapOrderToEvent($order))->values();

        return response()->json([
            'data' => [
                'range' => [
                    'start' => $start->toDateString(),
                    'end' => $end->toDateString(),
                ],
                'events' => $events,
            ],
        ]);
    }

    public function day(CalendarDayRequest $request): JsonResponse
    {
        $userId = $this->currentUserId();
        $validated = $request->validated();
        $date = Carbon::parse($validated['date'])->startOfDay();

        $orders = Order::with('client')
            ->where('master_id', $userId)
            ->whereDate('scheduled_at', $date)
            ->orderBy('scheduled_at')
            ->get();

        // One grouped query for the whole day rather than one per booking.
        $noShowCounts = $this->attendance->noShowCountsFor(
            $userId,
            $orders->pluck('client_id')->filter()->map(fn ($id) => (int) $id)->all(),
        );

        $settings = $this->resolveUserSettings($userId);

        // Cards in one query, so the panel can offer "write to her" per booking.
        $cards = $this->identities->cardsForOrders($userId, $orders);

        $timezone = Auth::guard('sanctum')->user()?->timezone ?? config('app.timezone');
        $isWorkingDay = $this->scheduleService->hasWorkingSlots($settings, $date, $timezone);

        // Duration-aware: a slot is free only when nothing overlaps it.
        $availableSlots = collect($this->availability->availableSlotsForDate(
            $userId,
            null,
            $date->toDateString(),
            $settings,
            $timezone,
        ));

        return response()->json([
            'data' => [
                'date' => $date->toDateString(),
                'orders' => $orders
                    ->map(fn (Order $order) => $this->mapOrderToDetails($order, $noShowCounts, $cards, $settings))
                    ->values(),
                'available_slots' => $availableSlots,
                'is_working_day' => $isWorkingDay,
                'gaps' => $this->buildGaps($userId, $date, $settings, $timezone),
            ],
            'meta' => [
                'settings_notice' => $settings ? null : __('calendar.settings_missing'),
            ],
        ]);
    }

    /**
     * Unsold stretches between bookings, with the waiting-list clients who fit.
     *
     * Candidates are ranked for the three longest gaps only: the rest of the day
     * is rarely actionable, and every extra slot is more work for the matcher.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildGaps(int $userId, Carbon $date, ?Setting $settings, string $timezone): array
    {
        $gaps = $this->daySchedule->gapsForDate($userId, $date, $settings, $timezone);

        if ($gaps === []) {
            return [];
        }

        $ranked = collect($gaps)
            ->sortByDesc('minutes')
            ->take(3)
            ->keys()
            ->all();

        $slots = [];

        foreach ($ranked as $index) {
            $slots[(string) $index] = [
                'start' => Carbon::parse($gaps[$index]['starts_at']),
                'duration' => $gaps[$index]['minutes'],
                'service_id' => null,
            ];
        }

        $matches = $this->waitlist->rankForSlots($userId, $slots, 3, true);

        foreach ($gaps as $index => $gap) {
            $gaps[$index]['label'] = $this->humanDuration($gap['minutes']);
            $gaps[$index]['candidates'] = $matches->get((string) $index, collect())
                ->map(function (array $match) use ($settings) {
                    $account = Arr::get($match, 'client_user.id')
                        ? User::find(Arr::get($match, 'client_user.id'))
                        : null;
                    $card = Arr::get($match, 'client.id') ? Client::find(Arr::get($match, 'client.id')) : null;

                    $match['client']['channels'] = $this->channels->availableChannels($settings, $card, $account);

                    return $match;
                })
                ->values()
                ->all();
        }

        return $gaps;
    }

    private function humanDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        if ($hours === 0) {
            return trans_choice('calendar.day.gap_minutes', $rest, ['count' => $rest]);
        }

        $label = trans_choice('calendar.day.gap_hours', $hours, ['count' => $hours]);

        return $rest === 0
            ? $label
            : $label . ' ' . trans_choice('calendar.day.gap_minutes', $rest, ['count' => $rest]);
    }

    private function mapOrderToEvent(Order $order): array
    {
        $start = $order->scheduled_at ? $order->scheduled_at->copy() : null;
        $duration = $this->durations->resolve($order);
        $end = $start ? $start->copy()->addMinutes($duration) : null;

        $clientName = $order->client?->name;
        $title = $clientName ?: __('calendar.untitled_event');

        $serviceNames = collect($order->services ?? [])
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        return [
            'id' => $order->id,
            'title' => $title,
            'start' => $start?->toIso8601String(),
            'end' => $end?->toIso8601String(),
            'allDay' => false,
            'extendedProps' => [
                'status' => $order->status,
                'status_label' => $order->status_label,
                'client' => [
                    'id' => $order->client?->id,
                    'name' => $clientName,
                    'phone' => $order->client?->phone,
                ],
                'services' => $serviceNames,
                'scheduled_at_formatted' => $start?->format('d.m.Y H:i'),
            ],
        ];
    }

    /**
     * @param  array<int, int>  $noShowCounts
     * @param  \Illuminate\Support\Collection<int, \App\Models\Client>|null  $cards
     */
    private function mapOrderToDetails(
        Order $order,
        array $noShowCounts = [],
        $cards = null,
        ?Setting $settings = null,
    ): array {
        $services = collect($order->services ?? [])->map(function ($service) {
            $price = Arr::get($service, 'price');
            $duration = Arr::get($service, 'duration');

            return [
                'id' => Arr::get($service, 'id'),
                'name' => Arr::get($service, 'name'),
                'price' => is_numeric($price) ? (float) $price : null,
                'duration' => is_numeric($duration) ? (int) $duration : null,
            ];
        })->values();

        $duration = $this->durations->resolve($order);
        $actions = $this->actions->for($order);
        $card = $cards?->get($order->client_id);

        return [
            'id' => $order->id,
            'scheduled_at' => $order->scheduled_at?->toIso8601String(),
            'scheduled_at_formatted' => $order->scheduled_at?->format('H:i'),
            'ends_at_formatted' => $order->scheduled_at?->copy()->addMinutes($duration)->format('H:i'),
            'duration' => $duration,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'note' => $order->note,
            'total_price' => $order->total_price !== null ? (float) $order->total_price : null,
            'services' => $services,
            'client' => [
                'id' => $order->client?->id,
                'card_id' => $card?->id,
                'name' => $card?->name ?: ($order->client?->name ?? __('calendar.unnamed_client')),
                'phone' => $card?->phone ?: $order->client?->phone,
                'email' => $card?->email ?: $order->client?->email,
                'channels' => $card ? $this->channels->availableChannels($settings, $card, $order->client) : [],
            ],
            'attention' => $this->buildAttention($order, $noShowCounts),
            'can_start' => $actions['can_start'],
            'start_needs_confirm' => $actions['start_needs_confirm'],
            'can_complete' => $actions['can_complete'],
            'can_mark_no_show' => $actions['can_mark_no_show'],
        ];
    }

    /**
     * Something worth saying about this client before the visit, or nothing.
     *
     * Only ever shown for a booking still ahead: telling a master that someone
     * missed an appointment she has already had is noise, and it reads as a
     * verdict rather than a prompt to act.
     *
     * @param  array<int, int>  $noShowCounts
     * @return array<string, mixed>|null
     */
    private function buildAttention(Order $order, array $noShowCounts): ?array
    {
        if (! in_array($order->status, ['new', 'confirmed'], true)) {
            return null;
        }

        if (! $order->scheduled_at || $order->scheduled_at->lessThan(Carbon::now())) {
            return null;
        }

        $count = (int) ($noShowCounts[$order->client_id] ?? 0);
        $fact = $this->attendance->factFor($count);

        if (! $fact) {
            return null;
        }

        return $fact + ['action' => ['label' => __('calendar.day.attention.confirm')]];
    }

    private function currentUserId(): int
    {
        $userId = Auth::guard('sanctum')->id();

        if (! $userId) {
            abort(403);
        }

        return $userId;
    }

    private function resolveUserSettings(int $userId): ?Setting
    {
        return Setting::where('user_id', $userId)->first();
    }
}
