<?php

namespace App\Services;

use App\Jobs\SendOrderStartReminderJob;
use App\Models\Order;
use Illuminate\Support\Carbon;

class OrderService
{
    public function scheduleStartReminder(Order $order): void
    {
        if (! $this->shouldScheduleStartReminder($order)) {
            return;
        }

        $scheduledAt = $order->scheduled_at->copy();
        $scheduledTimestamp = $scheduledAt->getTimestamp();
        $runAtTimestamp = $scheduledTimestamp + 600; // +10 минут
        $nowTimestamp = Carbon::now()->getTimestamp();
        $delaySeconds = max(0, $runAtTimestamp - $nowTimestamp);

        SendOrderStartReminderJob::dispatch(
            $order->id,
            $scheduledTimestamp,
        )->delay($delaySeconds);
    }

    private function shouldScheduleStartReminder(Order $order): bool
    {
        return (bool) ($order->scheduled_at
            && $order->master_id
            && in_array($order->status, ['new', 'confirmed'], true)
            && ! $order->actual_started_at);
    }
}
