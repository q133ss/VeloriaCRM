<?php

namespace App\Services\Booking;

use App\Models\Appointment;
use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Services\ScheduleService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Dead time between bookings.
 *
 * An empty stretch in the middle of a working day looks like rest in a calendar
 * grid, which is exactly why it goes unnoticed: nothing on the screen says that
 * three hours between an 11:00 and a 15:00 were hours nobody paid for.
 */
class DayScheduleService
{
    /** Below this a hole is not worth selling, whatever the price list says. */
    public const ABSOLUTE_MIN_MINUTES = 30;

    /** A gap starting sooner than this today cannot realistically be filled. */
    private const TODAY_LEAD_MINUTES = 15;

    /** Averaging a rate over fewer bookings than this produces a made-up number. */
    private const MIN_ORDERS_FOR_RATE = 10;

    public function __construct(
        private readonly ScheduleService $scheduleService,
        private readonly OrderDurationResolver $durations,
    ) {
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    public function busyIntervals(int $masterId, CarbonInterface $day, ?string $timezone = null): array
    {
        $timezone = $timezone ?: config('app.timezone');
        $dayStart = Carbon::parse($day->format('Y-m-d'), $timezone)->startOfDay();
        $rangeStart = $dayStart->copy()->timezone(config('app.timezone'));
        $rangeEnd = $dayStart->copy()->endOfDay()->timezone(config('app.timezone'));

        $orders = Order::query()
            ->where('master_id', $masterId)
            ->whereBetween('scheduled_at', [$rangeStart, $rangeEnd])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get(['id', 'scheduled_at', 'services', 'duration_forecast', 'duration']);

        $intervals = [];

        foreach ($orders as $order) {
            $interval = $this->durations->interval($order, $timezone);

            if ($interval) {
                $intervals[] = $interval;
            }
        }

        $appointments = Appointment::query()
            ->where('user_id', $masterId)
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->where('status', '!=', 'cancelled')
            ->get(['starts_at', 'ends_at']);

        foreach ($appointments as $appointment) {
            if ($appointment->starts_at && $appointment->ends_at) {
                $intervals[] = [
                    'start' => $appointment->starts_at->copy()->timezone($timezone),
                    'end' => $appointment->ends_at->copy()->timezone($timezone),
                ];
            }
        }

        return $this->durations->merge($intervals);
    }

    /**
     * @return array<int, array{
     *     start: string, end: string, starts_at: string,
     *     minutes: int, slots: array<int, string>, estimated_value: float|null
     * }>
     */
    public function gapsForDate(
        int $masterId,
        CarbonInterface $day,
        ?Setting $setting = null,
        ?string $timezone = null,
    ): array {
        $timezone = $timezone ?: config('app.timezone');
        $dayStart = Carbon::parse($day->format('Y-m-d'), $timezone)->startOfDay();
        $now = Carbon::now($timezone);

        // Nothing to sell in a day that is over.
        if ($dayStart->copy()->endOfDay()->lessThan($now)) {
            return [];
        }

        $setting = $setting ?: Setting::query()->where('user_id', $masterId)->first();
        $anchors = collect($this->scheduleService->resolveSlotsForDate($setting, $dayStart, $timezone));

        if ($anchors->isEmpty()) {
            return [];
        }

        $busy = $this->busyIntervals($masterId, $dayStart, $timezone);

        // A day with no bookings is an empty day, not dead time between work.
        // The free-time block already covers it, and calling it a gap would put a
        // loss notice on every quiet day in the calendar.
        if ($busy === []) {
            return [];
        }

        $windowStart = $this->anchorToCarbon($dayStart, $anchors->first());
        $windowEnd = $busy[count($busy) - 1]['end']->copy();

        // The schedule stores opening anchors and no closing time, so the evening
        // after the last booking cannot be measured and is deliberately left out.
        if ($windowStart->greaterThanOrEqualTo($windowEnd)) {
            return [];
        }

        $minMinutes = $this->minimumGapMinutes($masterId);
        $rate = $this->hourlyRate($masterId);
        $gaps = [];
        $cursor = $windowStart->copy();

        foreach ($busy as $interval) {
            if ($interval['start']->greaterThan($cursor)) {
                $gap = $this->buildGap($cursor, $interval['start'], $dayStart, $anchors, $now, $minMinutes, $rate);

                if ($gap) {
                    $gaps[] = $gap;
                }
            }

            if ($interval['end']->greaterThan($cursor)) {
                $cursor = $interval['end']->copy();
            }
        }

        return $gaps;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $anchors
     * @return array<string, mixed>|null
     */
    private function buildGap(
        Carbon $start,
        Carbon $end,
        Carbon $dayStart,
        $anchors,
        Carbon $now,
        int $minMinutes,
        ?float $rate,
    ): ?array {
        $start = $start->copy();

        // Today's morning is already spent; only offer what is still ahead.
        $earliest = $now->copy()->addMinutes(self::TODAY_LEAD_MINUTES);

        if ($start->lessThan($earliest)) {
            $start = $earliest->copy();
        }

        if ($start->greaterThanOrEqualTo($end)) {
            return null;
        }

        $minutes = (int) $start->diffInMinutes($end);

        if ($minutes < $minMinutes) {
            return null;
        }

        $slots = $anchors
            ->filter(function (string $anchor) use ($dayStart, $start, $end) {
                $moment = $this->anchorToCarbon($dayStart, $anchor);

                return $moment->greaterThanOrEqualTo($start) && $moment->lessThan($end);
            })
            ->values()
            ->all();

        return [
            'start' => $start->format('H:i'),
            'end' => $end->format('H:i'),
            'starts_at' => $start->toIso8601String(),
            'minutes' => $minutes,
            'slots' => $slots,
            'estimated_value' => $rate === null ? null : round($rate * $minutes / 60),
        ];
    }

    private function anchorToCarbon(Carbon $dayStart, string $anchor): Carbon
    {
        [$hours, $minutes] = array_map('intval', explode(':', $anchor));

        return $dayStart->copy()->setTime($hours, $minutes);
    }

    /**
     * A 45-minute hole is money if the master offers a 30-minute service and
     * noise if her shortest takes an hour and a half.
     */
    private function minimumGapMinutes(int $masterId): int
    {
        $shortest = (int) Service::query()
            ->where('user_id', $masterId)
            ->where('duration_min', '>', 0)
            ->min('duration_min');

        return max(self::ABSOLUTE_MIN_MINUTES, $shortest);
    }

    /** Average revenue per hour of work, or null when there is too little history. */
    private function hourlyRate(int $masterId): ?float
    {
        $orders = Order::query()
            ->where('master_id', $masterId)
            ->where('status', 'completed')
            ->whereNotNull('total_price')
            ->get(['id', 'services', 'duration_forecast', 'duration', 'total_price']);

        if ($orders->count() < self::MIN_ORDERS_FOR_RATE) {
            return null;
        }

        $minutes = $orders->sum(fn (Order $order) => $this->durations->resolve($order));
        $revenue = (float) $orders->sum(fn (Order $order) => (float) $order->total_price);

        if ($minutes <= 0 || $revenue <= 0) {
            return null;
        }

        return $revenue / ($minutes / 60);
    }
}
