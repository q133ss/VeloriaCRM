<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Clients\ClientVisitStats;

/**
 * Keeps `clients.last_visit_at` in step with the bookings.
 *
 * The column is read directly by the analytics, the warm-up groups and the
 * waiting list, so it has to be right without those pages knowing where it comes
 * from. Every write to an order can change it: closing a visit, cancelling one,
 * marking a no-show, moving a booking to a different date or to a different
 * person. Rather than remember to call the service in each of those places, the
 * one write path is here.
 */
class OrderObserver
{
    public function __construct(private readonly ClientVisitStats $stats)
    {
    }

    public function saved(Order $order): void
    {
        $this->stats->refreshForAccount($order->master_id, $order->client_id);

        // A booking handed to another client leaves the first one with one visit
        // fewer, and she is no longer reachable from the order.
        $previous = $order->getOriginal('client_id');

        if ($previous && (int) $previous !== (int) $order->client_id) {
            $this->stats->refreshForAccount($order->master_id, (int) $previous);
        }
    }

    public function deleted(Order $order): void
    {
        $this->stats->refreshForAccount($order->master_id, $order->client_id);
    }
}
