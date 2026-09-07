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
use Illuminate\Support\Facades\Schema;

class SendOrderStartReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $orderId,
        public readonly int $scheduledAtTimestamp,
    ) {
    }

    public function handle(NotificationService $notifications): void
    {
        $order = Order::query()
            ->with(['master', 'client'])
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

        if ($order->scheduled_at->getTimestamp() !== $this->scheduledAtTimestamp) {
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
        // Matches OrderService::START_REMINDER_DELAY_SECONDS.
        $threshold = Carbon::createFromTimestamp($this->scheduledAtTimestamp)->addMinutes(5);

        if ($now->lessThan($threshold)) {
            return;
        }

        $actionUrl = route('orders.start-confirmation', ['order' => $order->id], false);

        $notifications->send(
            $order->master_id,
            __('orders.start_reminder.title'),
            __('orders.start_reminder.message', [
                'client' => $order->client?->name ?: __('calendar.unnamed_client'),
                'time' => $order->scheduled_at->format('H:i'),
            ]),
            $actionUrl,
        );

        if (Schema::hasColumn($order->getTable(), 'start_confirmation_notified_at')) {
            $order->forceFill([
                'start_confirmation_notified_at' => $now,
            ])->save();
        }
    }
}
