<?php

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Support\Carbon;

/**
 * What a master may do with a booking right now.
 *
 * This used to be a protected method on OrderController, which meant the day
 * panel in the calendar could not ask the same question and would have had to
 * answer it a second time. Two copies of "can this be started" drift apart.
 */
class OrderActionPolicy
{
    /**
     * How far from the booked time the timer may be started without asking.
     *
     * Starting a 15:00 appointment at 13:00 records two hours that never
     * happened, and a booking from this morning started now is just as wrong.
     * Around the appointed minute, though, confirming would only be in the way.
     */
    public const START_GRACE_MINUTES = 15;

    /**
     * @return array<string, bool>
     */
    public function for(Order $order): array
    {
        $now = Carbon::now();
        $scheduledAt = $order->scheduled_at;
        $isToday = $scheduledAt ? $scheduledAt->isSameDay($now) : false;
        $startsSoon = $scheduledAt ? $scheduledAt->greaterThan($now) : false;
        $hoursDiff = $scheduledAt ? $now->diffInHours($scheduledAt, false) : null;

        return [
            'can_start_now' => $isToday,
            // can_start_now only answers "is it today"; a finished or cancelled
            // booking is still today and must not offer a Start button.
            'can_start' => $isToday && in_array($order->status, ['new', 'confirmed'], true),
            'start_warning' => $startsSoon && $hoursDiff !== null && $hoursDiff > 1,
            'start_needs_confirm' => $this->startNeedsConfirm($order, $now),
            // A booking is confirmed once, out of `new`. Pressing Confirm on
            // something already confirmed, closed or cancelled used to silently
            // rewrite its status and its confirmed_at.
            'can_confirm' => $order->status === 'new',
            // Reminding someone about a visit that is over, cancelled or missed
            // is the one message a master can never take back.
            'can_remind' => ! in_array($order->status, ['completed', 'cancelled', 'no_show'], true),
            'can_complete' => in_array($order->status, ['in_progress', 'confirmed'], true),
            'can_reschedule' => ! in_array($order->status, ['completed', 'cancelled'], true),
            'can_cancel' => ! in_array($order->status, ['completed', 'cancelled'], true),
            'can_mark_no_show' => $this->canMarkNoShow($order, $now),
        ];
    }

    private function startNeedsConfirm(Order $order, Carbon $now): bool
    {
        if (! $order->scheduled_at) {
            return true;
        }

        return abs($now->diffInMinutes($order->scheduled_at, false)) > self::START_GRACE_MINUTES;
    }

    /**
     * Only offered once the visit is over and still unresolved. Marking someone
     * a no-show before their appointment has passed makes no sense, and there is
     * currently no other way to record one at all.
     */
    private function canMarkNoShow(Order $order, Carbon $now): bool
    {
        if (! in_array($order->status, ['new', 'confirmed'], true)) {
            return false;
        }

        return $order->scheduled_at !== null && $order->scheduled_at->lessThan($now);
    }
}
