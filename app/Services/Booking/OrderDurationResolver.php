<?php

namespace App\Services\Booking;

use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * How long a booking occupies the calendar. One rule, one place.
 *
 * Three answers to this question used to live in the codebase and they
 * disagreed: the conflict checker preferred the services snapshot, the
 * availability calculation looked at nothing else, and the month grid read the
 * same fields in the opposite order. The same booking was therefore drawn,
 * checked for overlaps and subtracted from free time as three different lengths.
 *
 * `duration_forecast` wins because it is the one field a master can correct.
 * The services snapshot only ever holds the price-list defaults, so leaving it
 * in front would silently discard a manual override everywhere except the input
 * she typed it into. For every row the app has written the two are equal, so
 * the order matters only where someone deliberately changed it.
 */
class OrderDurationResolver
{
    public const FALLBACK_MINUTES = 60;

    public function resolve(Order $order): int
    {
        $forecast = (int) ($order->duration_forecast ?? 0);

        if ($forecast > 0) {
            return $forecast;
        }

        $fromServices = collect($order->services ?? [])
            ->sum(fn ($service) => (int) Arr::get($service, 'duration', 0));

        if ($fromServices > 0) {
            return (int) $fromServices;
        }

        $measured = (int) ($order->duration ?? 0);

        return $measured > 0 ? $measured : self::FALLBACK_MINUTES;
    }

    /**
     * The occupied interval, or null for a booking with no time on it.
     *
     * @return array{start: CarbonInterface, end: CarbonInterface}|null
     */
    public function interval(Order $order, ?string $timezone = null): ?array
    {
        if (! $order->scheduled_at) {
            return null;
        }

        $start = $order->scheduled_at->copy();

        if ($timezone) {
            $start = $start->timezone($timezone);
        }

        return [
            'start' => $start,
            'end' => $start->copy()->addMinutes($this->resolve($order)),
        ];
    }

    /**
     * Busy intervals for a set of bookings, merged so overlapping ones read as
     * one block. Cancellations and no-shows free the time up again.
     *
     * @param  iterable<Order>  $orders
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    public function mergeIntervals(iterable $orders, ?string $timezone = null): array
    {
        $intervals = [];

        foreach ($orders as $order) {
            $interval = $this->interval($order, $timezone);

            if ($interval) {
                $intervals[] = $interval;
            }
        }

        return $this->merge($intervals);
    }

    /**
     * @param  array<int, array{start: CarbonInterface, end: CarbonInterface}>  $intervals
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    public function merge(array $intervals): array
    {
        usort($intervals, fn ($a, $b) => $a['start']->getTimestamp() <=> $b['start']->getTimestamp());

        $merged = [];

        foreach ($intervals as $interval) {
            $last = $merged === [] ? null : $merged[count($merged) - 1];

            if ($last && $interval['start']->lessThanOrEqualTo($last['end'])) {
                if ($interval['end']->greaterThan($last['end'])) {
                    $merged[count($merged) - 1]['end'] = $interval['end']->copy();
                }

                continue;
            }

            $merged[] = [
                'start' => $interval['start']->copy(),
                'end' => $interval['end']->copy(),
            ];
        }

        return $merged;
    }
}
