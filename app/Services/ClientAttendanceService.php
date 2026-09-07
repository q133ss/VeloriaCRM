<?php

namespace App\Services;

use App\Models\Order;

/**
 * How often a client has failed to turn up, stated as a fact.
 *
 * Deliberately not a risk score. A percentage next to someone's name is a label
 * on a person, and it invites the master to treat a number she cannot audit as a
 * judgement about a human being. "Не пришла дважды" is the same information
 * without the verdict, and it points at an action she can actually take.
 *
 * Cancellations are not counted. Someone who cancelled told you in advance —
 * that is the opposite behaviour, and lumping the two together (as the dashboard
 * indicator used to) marks considerate clients as unreliable.
 */
class ClientAttendanceService
{
    /**
     * Keyed on `orders.client_id`, which holds the client's *account* id.
     *
     * @param  array<int, int>  $clientUserIds
     * @return array<int, int>
     */
    public function noShowCountsFor(int $masterId, array $clientUserIds, ?int $excludeOrderId = null): array
    {
        $clientUserIds = array_values(array_unique(array_filter($clientUserIds)));

        if ($clientUserIds === []) {
            return [];
        }

        return Order::query()
            ->where('master_id', $masterId)
            ->whereIn('client_id', $clientUserIds)
            ->where('status', 'no_show')
            ->when($excludeOrderId, fn ($query) => $query->whereKeyNot($excludeOrderId))
            ->groupBy('client_id')
            ->selectRaw('client_id, COUNT(*) AS total')
            ->pluck('total', 'client_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * @return array{type: string, count: int, text: string}|null
     */
    public function factFor(int $count): ?array
    {
        if ($count < 1) {
            return null;
        }

        return [
            'type' => 'no_show',
            'count' => $count,
            'text' => trans_choice('calendar.day.attention.no_show', $count, ['count' => $count]),
        ];
    }
}
