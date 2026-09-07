<?php

namespace App\Services\Clients;

use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * When a client last came, how many times she has come, and when she is coming
 * next — counted from the bookings instead of being asked for.
 *
 * `clients.last_visit_at` used to be a field on the form: the master was invited
 * to type in a date the calendar already knew. Nobody ever did, so the column was
 * null for every client, and everything reading it — this list, the "sleeping
 * clients" figure in the analytics, the warm-up groups, the order of the waiting
 * list — quietly reported that nobody had ever been anywhere.
 *
 * A visit is a booking whose time has passed and which was neither cancelled nor
 * marked as a no-show. Deliberately not "completed": plenty of masters never
 * press the button, and refusing to count a client who obviously came is the
 * worse mistake. The outreach message is written from the same rule, and the two
 * must not disagree about who has been away for three months.
 */
class ClientVisitStats
{
    /** A booking in the past with one of these statuses means she was here. */
    public const VISITED = ['completed', 'in_progress', 'confirmed'];

    /** A booking ahead with one of these means she is expected. */
    public const EXPECTED = ['new', 'confirmed', 'in_progress'];

    /**
     * Days without a visit, and with nothing booked, before a client counts as
     * slipped away. Six weeks is longer than the usual rhythm of colour or nails
     * and short enough that the master can still write while she is remembered.
     */
    public const SLEEPING_AFTER_DAYS = 45;

    /**
     * @param  Collection<int, Client>  $cards
     * @return array<int, array{visits: int, last_visit_at: ?Carbon, next_at: ?Carbon, next_order_id: ?int, no_shows: int}>
     *                                  keyed by card id
     */
    public function forCards(int $masterId, Collection $cards, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();
        $blank = ['visits' => 0, 'last_visit_at' => null, 'next_at' => null, 'next_order_id' => null, 'no_shows' => 0];

        $accountIds = $cards->pluck('client_user_id')->filter()->unique()->values();

        if ($accountIds->isEmpty()) {
            return $cards->mapWithKeys(fn (Client $card) => [$card->id => $blank])->all();
        }

        $totals = $this->totalsByAccount($masterId, $accountIds->all(), $now);
        $next = $this->nextByAccount($masterId, $accountIds->all(), $now);

        return $cards->mapWithKeys(function (Client $card) use ($totals, $next, $blank) {
            $account = $card->client_user_id;

            if (! $account || ! isset($totals[$account])) {
                $stats = $blank;
            } else {
                $stats = $totals[$account];
            }

            $upcoming = $account ? ($next[$account] ?? null) : null;

            return [$card->id => array_merge($stats, [
                'next_at' => $upcoming['at'] ?? null,
                'next_order_id' => $upcoming['id'] ?? null,
            ])];
        })->all();
    }

    /**
     * @return array{visits: int, last_visit_at: ?Carbon, next_at: ?Carbon, next_order_id: ?int, no_shows: int}
     */
    public function forCard(Client $card, ?Carbon $now = null): array
    {
        return $this->forCards($card->user_id, collect([$card]), $now)[$card->id];
    }

    /**
     * Which of the four things the master might do about this person is true.
     * The list turns this into a tab and into the one button on the row.
     */
    public function groupFor(array $stats, ?Carbon $now = null): string
    {
        $now ??= Carbon::now();

        if ($stats['next_at']) {
            return 'upcoming';
        }

        if ($stats['visits'] < 1) {
            return 'new';
        }

        if ($stats['last_visit_at'] && $stats['last_visit_at']->lt($now->copy()->subDays(self::SLEEPING_AFTER_DAYS))) {
            return 'sleeping';
        }

        return 'active';
    }

    /**
     * Bring the cached column back in step with the bookings.
     *
     * The column stays because the analytics, the warm-up and the waiting list
     * all read it directly; this list computes the same figure fresh, so the two
     * can never drift apart in what they say.
     */
    public function refresh(Client $card): bool
    {
        $last = $this->forCard($card)['last_visit_at'];

        if ($this->sameMoment($card->last_visit_at, $last)) {
            return false;
        }

        $card->forceFill(['last_visit_at' => $last])->saveQuietly();

        return true;
    }

    /**
     * The card a booking belongs to, refreshed. Called whenever an order is
     * written or deleted, so a visit closed today is a visit by the time the
     * master looks at the client list.
     */
    public function refreshForAccount(int $masterId, ?int $accountId): void
    {
        if (! $accountId) {
            return;
        }

        Client::query()
            ->where('user_id', $masterId)
            ->where('client_user_id', $accountId)
            ->get()
            ->each(fn (Client $card) => $this->refresh($card));
    }

    /**
     * Everyone, for the nightly pass: a booking becomes a visit because time
     * passed, and no write happens at that moment to notice it.
     */
    public function refreshAll(?int $masterId = null): int
    {
        $changed = 0;

        Client::query()
            ->when($masterId, fn ($query) => $query->where('user_id', $masterId))
            ->whereNotNull('client_user_id')
            ->chunkById(200, function (Collection $cards) use (&$changed) {
                foreach ($cards->groupBy('user_id') as $ownerId => $owned) {
                    $stats = $this->forCards((int) $ownerId, $owned);

                    foreach ($owned as $card) {
                        $last = $stats[$card->id]['last_visit_at'];

                        if ($this->sameMoment($card->last_visit_at, $last)) {
                            continue;
                        }

                        $card->forceFill(['last_visit_at' => $last])->saveQuietly();
                        $changed++;
                    }
                }
            });

        return $changed;
    }

    /**
     * @param  array<int, int>  $accountIds
     * @return array<int, array{visits: int, last_visit_at: ?Carbon, no_shows: int}>
     */
    private function totalsByAccount(int $masterId, array $accountIds, Carbon $now): array
    {
        $visited = self::VISITED;
        $placeholders = implode(',', array_fill(0, count($visited), '?'));

        return Order::query()
            ->where('master_id', $masterId)
            ->whereIn('client_id', $accountIds)
            ->groupBy('client_id')
            ->select('client_id')
            ->selectRaw(
                "sum(case when status in ({$placeholders}) and scheduled_at <= ? then 1 else 0 end) as visits",
                [...$visited, $now],
            )
            ->selectRaw(
                "max(case when status in ({$placeholders}) and scheduled_at <= ? then scheduled_at end) as last_visit_at",
                [...$visited, $now],
            )
            ->selectRaw("sum(case when status = ? then 1 else 0 end) as no_shows", ['no_show'])
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->client_id => [
                'visits' => (int) $row->visits,
                'last_visit_at' => $row->last_visit_at ? Carbon::parse($row->last_visit_at) : null,
                'no_shows' => (int) $row->no_shows,
            ]])
            ->all();
    }

    /**
     * @param  array<int, int>  $accountIds
     * @return array<int, array{id: int, at: Carbon}>
     */
    private function nextByAccount(int $masterId, array $accountIds, Carbon $now): array
    {
        return Order::query()
            ->where('master_id', $masterId)
            ->whereIn('client_id', $accountIds)
            ->whereIn('status', self::EXPECTED)
            ->where('scheduled_at', '>', $now)
            ->orderBy('scheduled_at')
            ->get(['id', 'client_id', 'scheduled_at'])
            ->groupBy('client_id')
            ->map(fn (Collection $orders) => [
                'id' => (int) $orders->first()->id,
                'at' => $orders->first()->scheduled_at,
            ])
            ->mapWithKeys(fn (array $next, $accountId) => [(int) $accountId => $next])
            ->all();
    }

    private function sameMoment(?Carbon $left, ?Carbon $right): bool
    {
        if ($left === null || $right === null) {
            return $left === null && $right === null;
        }

        return $left->equalTo($right);
    }
}
