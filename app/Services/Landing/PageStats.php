<?php

namespace App\Services\Landing;

use App\Models\Client;
use App\Models\Order;
use App\Models\Service;

/**
 * Real figures for the counters block of a landing page, taken from the master's
 * own data. Nothing here is a placeholder: the page skips the block while the
 * numbers are still too small to say anything.
 */
class PageStats
{
    /** Below this many clients the counters would only advertise a fresh account. */
    public const MIN_CLIENTS = 10;

    /**
     * @return array{clients: int, visits: int, services: int, upcoming: int}
     */
    public function forUser(int $userId): array
    {
        $bookings = Order::query()
            ->where('master_id', $userId)
            ->whereNotIn('status', ['cancelled', 'no_show']);

        return [
            'clients' => Client::query()->where('user_id', $userId)->count(),
            'visits' => (clone $bookings)->where('scheduled_at', '<', now())->count(),
            'services' => Service::query()->where('user_id', $userId)->count(),
            'upcoming' => (clone $bookings)->where('scheduled_at', '>=', now())->count(),
        ];
    }

    public function worthShowing(array $stats): bool
    {
        return $stats['clients'] >= self::MIN_CLIENTS;
    }
}
