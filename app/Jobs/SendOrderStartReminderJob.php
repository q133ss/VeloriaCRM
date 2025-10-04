<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SendOrderStartReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $orderId,
        private readonly string $scheduledAtIso,
    ) {
    }

    public function handle(NotificationService $notifications): void
    {
        $order = Order::query()
            ->with('master')
            ->find($this->orderId);

        if (! $order) {
            return;
        }

        if (! $order->master_id) {
            return;
        }

        if (! $order->scheduled_at) {
            return;
        }

        if (! $order->scheduled_at->equalTo(Carbon::parse($this->scheduledAtIso))) {
            return;
        }

        if (! in_array($order->status, ['new', 'confirmed'], true)) {
            return;
        }

        if ($order->actual_started_at) {
            return;
        }

        if ($order->start_confirmation_notified_at) {
            return;
        }

        $now = Carbon::now();
        $threshold = $order->scheduled_at->copy()->addMinutes(10);

        if ($now->lessThan($threshold)) {
            return;
        }

        $actionUrl = route('orders.start-confirmation', ['order' => $order->id], false);

        $notifications->send(
            $order->master_id,
            'Подтвердите начало процедуры',
            'Пожалуйста, подтвердите, что процедура началась!',
            $actionUrl,
        );

        $order->forceFill([
            'start_confirmation_notified_at' => $now,
        ])->save();
    }
}
