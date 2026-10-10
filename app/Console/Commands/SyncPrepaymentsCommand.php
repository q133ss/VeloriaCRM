<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Prepayment\PrepaymentSettlementService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Every minute: ask ЮKassa about prepayments still in flight (a master who has
 * not set up the webhook, a client who paid and closed the page), then release
 * the bookings whose time ran out unpaid.
 */
class SyncPrepaymentsCommand extends Command
{
    protected $signature = 'prepayment:sync';

    protected $description = 'Sync booking prepayments with ЮKassa and release unpaid bookings';

    /** A payment link stays payable for a while after the hold ends; keep watching that long. */
    private const WATCH_HOURS = 3;

    public function handle(PrepaymentSettlementService $settlement): int
    {
        $synced = 0;

        Payment::query()
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_WAITING_FOR_CAPTURE])
            ->whereNotNull('order_id')
            ->where('created_at', '>=', now()->subHours(self::WATCH_HOURS))
            ->chunkById(100, function ($payments) use ($settlement, &$synced) {
                foreach ($payments as $payment) {
                    try {
                        $settlement->sync($payment);
                        $synced++;
                    } catch (Throwable $exception) {
                        $this->warn("Payment {$payment->id}: {$exception->getMessage()}");
                    }
                }
            });

        $released = 0;

        // After the sync above, so that a payment made in the last second is not lost.
        Order::query()
            ->where('payment_status', 'awaiting')
            ->where('prepay_expires_at', '<', now())
            ->chunkById(100, function ($orders) use ($settlement, &$released) {
                foreach ($orders as $order) {
                    $released += $settlement->expire($order) ? 1 : 0;
                }
            });

        $this->info("Synced {$synced} payment(s), released {$released} unpaid booking(s).");

        return self::SUCCESS;
    }
}
