<?php

namespace App\Services\Catalog;

use App\Models\Order;
use Illuminate\Support\Arr;

/**
 * How often each service is actually booked, and what it brought in.
 *
 * The price list knew what a service costs the master and how long she thinks it
 * takes, and nothing about whether anyone buys it. That is the one question a
 * master has when she opens this page with a mind to change something: a service
 * booked six times and never once finished is worth a conversation, and a price
 * list cannot start it without counting.
 *
 * Bookings keep their services as a JSON snapshot rather than through a pivot
 * table — deliberately, so that renaming or deleting a service never rewrites
 * history — which is why this counts in PHP over two columns instead of in SQL.
 */
class ServiceDemand
{
    /**
     * Below this, «часто берут вместе» is a coincidence with a confident voice.
     */
    public const MIN_COMPANION_SAMPLES = 5;

    /**
     * @return array<int, array{bookings: int, completed: int, revenue: float, companions: array<int, int>}>
     *                                  keyed by service id
     */
    public function forMaster(int $masterId): array
    {
        $stats = [];

        Order::query()
            ->where('master_id', $masterId)
            ->whereNotNull('services')
            ->select(['services', 'status'])
            ->chunk(500, function ($orders) use (&$stats) {
                foreach ($orders as $order) {
                    $this->countOrder($order->services ?? [], (string) $order->status, $stats);
                }
            });

        return $stats;
    }

    /**
     * @param  array<int, mixed>  $services
     * @param  array<int, array{bookings: int, completed: int, revenue: float, companions: array<int, int>}>  $stats
     */
    private function countOrder(array $services, string $status, array &$stats): void
    {
        $ids = collect($services)
            ->map(fn ($service) => (int) Arr::get($service, 'id'))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        foreach ($services as $service) {
            $id = (int) Arr::get($service, 'id');

            if (! $id) {
                continue;
            }

            $stats[$id] ??= ['bookings' => 0, 'completed' => 0, 'revenue' => 0.0, 'companions' => []];
            $stats[$id]['bookings']++;

            // A cancelled visit was still demand — it just never became money.
            if ($status === 'completed') {
                $stats[$id]['completed']++;
                $stats[$id]['revenue'] += (float) Arr::get($service, 'price', 0);
            }

            foreach ($ids as $companion) {
                if ($companion === $id) {
                    continue;
                }

                $stats[$id]['companions'][$companion] = ($stats[$id]['companions'][$companion] ?? 0) + 1;
            }
        }
    }
}
