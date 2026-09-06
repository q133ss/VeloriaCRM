<?php

namespace App\Services;

use App\Jobs\SendOrderStartPromptJob;
use App\Jobs\SendOrderStartReminderJob;
use App\Models\Order;
use Illuminate\Support\Carbon;

class OrderService
{
    /** How long before a booking the master is asked to start the timer. */
    private const START_PROMPT_LEAD_SECONDS = 600;

    /** How long after it she is asked whether the visit began at all. */
    private const START_REMINDER_DELAY_SECONDS = 300;

    public function scheduleStartReminder(Order $order): void
    {
        if (! $this->shouldScheduleStartReminder($order)) {
            return;
        }

        $scheduledAt = $order->scheduled_at->copy();
        $scheduledTimestamp = $scheduledAt->getTimestamp();
        $nowTimestamp = Carbon::now()->getTimestamp();

        // Ten minutes before: "your next client is due, start the timer?"
        SendOrderStartPromptJob::dispatch(
            $order->id,
            $scheduledTimestamp,
        )->delay(max(0, $scheduledTimestamp - self::START_PROMPT_LEAD_SECONDS - $nowTimestamp));

        // Ten minutes after: the safety net for a visit nobody started.
        SendOrderStartReminderJob::dispatch(
            $order->id,
            $scheduledTimestamp,
        )->delay(max(0, $scheduledTimestamp + self::START_REMINDER_DELAY_SECONDS - $nowTimestamp));
    }

    private function shouldScheduleStartReminder(Order $order): bool
    {
        return (bool) ($order->scheduled_at
            && $order->master_id
            && in_array($order->status, ['new', 'confirmed'], true)
            && ! $order->actual_started_at);
    }
}
