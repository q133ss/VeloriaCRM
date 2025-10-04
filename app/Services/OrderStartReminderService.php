<?php

namespace App\Services;

use App\Jobs\SendOrderStartReminderJob;
use App\Models\Order;
use Illuminate\Support\Carbon;

class OrderStartReminderService
{
    public function schedule(Order $order): void
    {
        if (! $order->scheduled_at) {
            return;
        }

        if (! $order->master_id) {
            return;
        }

        if (! in_array($order->status, ['new', 'confirmed'], true)) {
            return;
        }

        if ($order->actual_started_at) {
            return;
        }

        $runAt = $order->scheduled_at->copy()->addMinutes(10);
        $delayUntil = $runAt->isFuture() ? $runAt : Carbon::now();

        SendOrderStartReminderJob::dispatch(
            $order->id,
            $order->scheduled_at->toIso8601String(),
        )->delay($delayUntil);
    }
}
