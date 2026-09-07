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

/**
 * Nudges the master shortly before a booking starts, so the timer gets started
 * at the right moment instead of being reconstructed from memory afterwards.
 *
 * Its sibling, SendOrderStartReminderJob, fires ten minutes *after* the booking
 * was due and asks whether the visit began at all. This one comes first and asks
 * her to start it; the other stays as the safety net for a forgotten one.
 */
class SendOrderStartPromptJob implements ShouldQueue
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
        $order = Order::query()->with('client')->find($this->orderId);

        if (! $order || ! $order->master_id || ! $order->scheduled_at) {
            return;
        }

        // The booking was moved after this job was queued; the new time has its own.
        if ($order->scheduled_at->getTimestamp() !== $this->scheduledAtTimestamp) {
            return;
        }

        if (! in_array($order->status, ['new', 'confirmed'], true)) {
            return;
        }

        if ($order->actual_started_at || $order->start_prompt_notified_at) {
            return;
        }

        $notifications->send(
            $order->master_id,
            __('orders.start_prompt.title'),
            __('orders.start_prompt.message', [
                'client' => $order->client?->name ?: __('calendar.unnamed_client'),
                'time' => $order->scheduled_at->format('H:i'),
            ]),
            '/calendar',
        );

        $order->forceFill(['start_prompt_notified_at' => Carbon::now()])->save();
    }
}
