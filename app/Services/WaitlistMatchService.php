<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WaitlistMatchService
{
    public function rankForSlot(
        int $masterId,
        Carbon $slotStart,
        int $slotDuration = 60,
        ?int $serviceId = null,
        ?int $limit = 8,
    ): Collection {
        return $this->rankForSlots($masterId, [
            'slot' => ['start' => $slotStart, 'duration' => $slotDuration, 'service_id' => $serviceId],
        ], $limit)->get('slot', collect());
    }

    /**
     * Rank the waiting list against several slots at once.
     *
     * Scoring one slot used to run two subqueries per waiting-list entry, so a
     * day with four gaps multiplied that by four. Client history is now loaded
     * once for everybody and scored in memory: three queries, whatever the
     * number of slots.
     *
     * @param  array<string, array{start: Carbon, duration?: int, service_id?: int|null}>  $slots
     * @return Collection<string, Collection<int, array<string, mixed>>>
     */
    public function rankForSlots(int $masterId, array $slots, ?int $limit = 8, bool $requireFit = false): Collection
    {
        if ($slots === []) {
            return collect();
        }

        $entries = WaitlistEntry::query()
            ->with(['client', 'clientUser', 'service'])
            ->where('user_id', $masterId)
            ->whereIn('status', ['pending', 'notified'])
            ->get();

        $history = $this->historyForEntries($masterId, $entries);
        $results = collect();

        foreach ($slots as $key => $slot) {
            $slotStart = $slot['start'];
            $slotDuration = (int) ($slot['duration'] ?? 60);
            $serviceId = $slot['service_id'] ?? null;

            $matches = $entries
                ->map(function (WaitlistEntry $entry) use ($slotStart, $slotDuration, $serviceId, $requireFit, $history) {
                    // A gap cannot hold a service that runs past its end.
                    if ($requireFit && $entry->service && (int) $entry->service->duration_min > $slotDuration) {
                        return null;
                    }

                    return $this->scoreEntry(
                        $entry,
                        $slotStart,
                        $slotDuration,
                        $serviceId,
                        $history[$entry->id] ?? ['ltv' => 0.0, 'recent_visits' => 0, 'no_shows' => 0],
                    );
                })
                ->filter()
                ->sortByDesc('match_score')
                ->values();

            $results->put($key, $limit !== null ? $matches->take($limit)->values() : $matches);
        }

        return $results;
    }

    public function notifyMatchesForSlot(
        int $masterId,
        Carbon $slotStart,
        int $slotDuration = 60,
        ?int $serviceId = null,
    ): void {
        $matches = $this->rankForSlot($masterId, $slotStart, $slotDuration, $serviceId, 3);

        if ($matches->isEmpty()) {
            return;
        }

        $top = $matches->first();

        app(NotificationService::class)->send(
            $masterId,
            __('waitlist.notifications.slot_opened_title'),
            trans_choice('waitlist.notifications.slot_opened_message', $matches->count(), [
                'count' => $matches->count(),
                'time' => $slotStart->format('d.m.Y H:i'),
                'client' => Arr::get($top, 'client.name', __('calendar.unnamed_client')),
            ]),
            '/calendar'
        );
    }

    /**
     * @param  array{ltv: float, recent_visits: int, no_shows: int}  $history
     */
    private function scoreEntry(
        WaitlistEntry $entry,
        Carbon $slotStart,
        int $slotDuration,
        ?int $serviceId,
        array $history,
    ): ?array {
        if (! $entry->client) {
            return null;
        }

        $score = 0.0;
        $reasons = [];

        $preferredDates = collect($entry->preferred_dates ?? [])
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->values();

        if ($preferredDates->isNotEmpty()) {
            $bestDistance = $preferredDates
                ->map(function (string $date) use ($slotStart) {
                    try {
                        return abs(Carbon::parse($date)->startOfDay()->diffInDays($slotStart->copy()->startOfDay(), false));
                    } catch (\Throwable) {
                        return null;
                    }
                })
                ->filter(fn ($value) => $value !== null)
                ->min();

            if ($bestDistance === null) {
                return null;
            }

            if ($bestDistance === 0) {
                $score += 28;
                $reasons[] = __('waitlist.reasons.exact_date');
            } elseif ($bestDistance <= max(1, (int) $entry->flexibility_days)) {
                $score += max(8, 20 - ($bestDistance * 3));
                $reasons[] = __('waitlist.reasons.flexible_date');
            } else {
                return null;
            }
        }

        $preferredWindows = collect($entry->preferred_time_windows ?? []);
        if ($preferredWindows->isNotEmpty()) {
            $matchedWindow = $preferredWindows->contains(function ($window) use ($slotStart) {
                $start = Arr::get($window, 'start');
                $end = Arr::get($window, 'end');

                return is_string($start)
                    && is_string($end)
                    && $slotStart->format('H:i') >= $start
                    && $slotStart->format('H:i') <= $end;
            });

            if ($matchedWindow) {
                $score += 16;
                $reasons[] = __('waitlist.reasons.time_window');
            } else {
                $score -= 8;
            }
        }

        if ($serviceId !== null && (int) $entry->service_id === $serviceId) {
            $score += 30;
            $reasons[] = __('waitlist.reasons.service_match');
        }

        $manualPriority = (int) ($entry->priority_manual ?? 0);
        $score += min(20, $manualPriority * 4);
        if ($manualPriority > 0) {
            $reasons[] = __('waitlist.reasons.manual_priority');
        }

        // Two thresholds, one sentence: which side of them she falls on decides
        // the ranking, and is nobody's business on screen.
        $ltv = $history['ltv'];
        if ($ltv >= 20000) {
            $score += 18;
            $reasons[] = __('waitlist.reasons.valuable_client');
        } elseif ($ltv >= 8000) {
            $score += 10;
            $reasons[] = __('waitlist.reasons.valuable_client');
        }

        $recentVisits = $history['recent_visits'];
        if ($recentVisits >= 4) {
            $score += 10;
            $reasons[] = __('waitlist.reasons.regular_client');
        }

        // Kept out of the reasons: those say why she is being offered the slot,
        // and a history of not turning up is an argument the other way.
        $warnings = [];
        $noShows = $history['no_shows'];
        if ($noShows > 0) {
            $score -= min(18, $noShows * 6);
            $warnings[] = __('waitlist.warnings.no_show_risk');
        }

        return [
            'id' => $entry->id,
            'match_score' => round($score, 1),
            'match_reasons' => array_values(array_unique($reasons)),
            'match_warnings' => $warnings,
            'matched_slot' => $slotStart->toIso8601String(),
            'slot_duration' => $slotDuration,
            'client' => [
                'id' => $entry->client->id,
                'name' => $entry->client->name,
                'phone' => $entry->client->phone,
                'email' => $entry->client->email,
                'loyalty_level' => $entry->client->loyalty_level,
            ],
            'client_user' => $entry->clientUser ? [
                'id' => $entry->clientUser->id,
                'name' => $entry->clientUser->name,
                'phone' => $entry->clientUser->phone,
                'email' => $entry->clientUser->email,
            ] : null,
            'service' => $entry->service ? [
                'id' => $entry->service->id,
                'name' => $entry->service->name,
                'duration' => (int) $entry->service->duration_min,
                'price' => (float) $entry->service->base_price,
            ] : null,
            'preferred_dates' => $preferredDates->all(),
            'preferred_time_windows' => $entry->preferred_time_windows ?? [],
            'priority_manual' => $manualPriority,
            'source' => $entry->source,
            'notes' => $entry->notes,
        ];
    }

    /**
     * Lifetime value, recent visits and no-shows for every entry, in two queries.
     *
     * A waiting-list entry names a card, but order history hangs off the client's
     * account, so cards are mapped to accounts first — by the explicit link when
     * there is one, and by phone or email otherwise, which is how this matching
     * has always worked.
     *
     * @param  Collection<int, WaitlistEntry>  $entries
     * @return array<int, array{ltv: float, recent_visits: int, no_shows: int}>
     */
    private function historyForEntries(int $masterId, Collection $entries): array
    {
        $phones = [];
        $emails = [];

        foreach ($entries as $entry) {
            $card = $entry->client;

            if (! $card) {
                continue;
            }

            if ($card->phone) {
                $phones[] = $card->phone;
            }

            if ($card->email) {
                $emails[] = $card->email;
            }
        }

        $accounts = collect();

        if ($phones !== [] || $emails !== []) {
            $accounts = User::query()
                ->when($phones !== [], fn ($query) => $query->orWhereIn('phone', array_unique($phones)))
                ->when($emails !== [], fn ($query) => $query->orWhereIn('email', array_unique($emails)))
                ->get(['id', 'phone', 'email']);
        }

        $byPhone = [];
        $byEmail = [];

        foreach ($accounts as $account) {
            if ($account->phone) {
                $byPhone[$account->phone][] = $account->id;
            }

            if ($account->email) {
                $byEmail[$account->email][] = $account->id;
            }
        }

        $accountsPerEntry = [];
        $allAccountIds = [];

        foreach ($entries as $entry) {
            $card = $entry->client;
            $ids = [];

            if ($entry->client_user_id) {
                $ids[] = (int) $entry->client_user_id;
            }

            if ($card) {
                foreach ([$byPhone[$card->phone] ?? [], $byEmail[$card->email] ?? []] as $matched) {
                    foreach ($matched as $id) {
                        $ids[] = (int) $id;
                    }
                }
            }

            $ids = array_values(array_unique($ids));
            $accountsPerEntry[$entry->id] = $ids;
            $allAccountIds = array_merge($allAccountIds, $ids);
        }

        $allAccountIds = array_values(array_unique($allAccountIds));

        if ($allAccountIds === []) {
            return [];
        }

        // CASE rather than FILTER: the suite runs on SQLite, production on Postgres.
        $rows = Order::query()
            ->where('master_id', $masterId)
            ->whereIn('client_id', $allAccountIds)
            ->groupBy('client_id')
            ->selectRaw(
                'client_id,'
                . " SUM(CASE WHEN status = 'completed' THEN total_price ELSE 0 END) AS ltv,"
                . ' SUM(CASE WHEN scheduled_at > ? THEN 1 ELSE 0 END) AS recent_visits,'
                . " SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) AS no_shows",
                [Carbon::now()->subMonths(6)],
            )
            ->get()
            ->keyBy('client_id');

        $history = [];

        foreach ($accountsPerEntry as $entryId => $ids) {
            $ltv = 0.0;
            $recent = 0;
            $noShows = 0;

            foreach ($ids as $id) {
                $row = $rows->get($id);

                if (! $row) {
                    continue;
                }

                $ltv += (float) $row->ltv;
                $recent += (int) $row->recent_visits;
                $noShows += (int) $row->no_shows;
            }

            $history[$entryId] = [
                'ltv' => $ltv,
                'recent_visits' => $recent,
                'no_shows' => $noShows,
            ];
        }

        return $history;
    }
}
