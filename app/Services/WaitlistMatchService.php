<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Order;
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
        $entries = WaitlistEntry::query()
            ->with(['client', 'clientUser', 'service'])
            ->where('user_id', $masterId)
            ->whereIn('status', ['pending', 'notified'])
            ->get();

        $matches = $entries
            ->map(fn (WaitlistEntry $entry) => $this->scoreEntry($masterId, $entry, $slotStart, $slotDuration, $serviceId))
            ->filter()
            ->sortByDesc('match_score')
            ->values();

        return $limit !== null ? $matches->take($limit)->values() : $matches;
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
            __('waitlist.notifications.slot_opened_message', [
                'count' => $matches->count(),
                'time' => $slotStart->format('d.m.Y H:i'),
                'client' => Arr::get($top, 'client.name', __('calendar.unnamed_client')),
            ]),
            '/calendar'
        );
    }

    private function scoreEntry(
        int $masterId,
        WaitlistEntry $entry,
        Carbon $slotStart,
        int $slotDuration,
        ?int $serviceId,
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

        $clientOrders = $this->clientOrders($masterId, $entry->client);
        $ltv = (float) $clientOrders->where('status', 'completed')->sum('total_price');
        if ($ltv >= 20000) {
            $score += 18;
            $reasons[] = __('waitlist.reasons.high_ltv');
        } elseif ($ltv >= 8000) {
            $score += 10;
            $reasons[] = __('waitlist.reasons.good_ltv');
        }

        $recentVisits = $clientOrders
            ->filter(fn (Order $order) => $order->scheduled_at && $order->scheduled_at->gt(now()->subMonths(6)))
            ->count();
        if ($recentVisits >= 4) {
            $score += 10;
            $reasons[] = __('waitlist.reasons.regular_client');
        }

        $noShows = $clientOrders->where('status', 'no_show')->count();
        if ($noShows > 0) {
            $score -= min(18, $noShows * 6);
            $reasons[] = __('waitlist.reasons.no_show_risk');
        }

        return [
            'id' => $entry->id,
            'match_score' => round($score, 1),
            'match_reasons' => array_values(array_unique($reasons)),
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

    private function clientOrders(int $masterId, Client $client): Collection
    {
        $query = Order::query()
            ->with('client')
            ->where('master_id', $masterId);

        if ($client->phone || $client->email) {
            $query->where(function ($builder) use ($client) {
                if ($client->phone) {
                    $builder->orWhereHas('client', fn ($clientQuery) => $clientQuery->where('phone', $client->phone));
                }

                if ($client->email) {
                    $builder->orWhereHas('client', fn ($clientQuery) => $clientQuery->where('email', $client->email));
                }
            });
        } else {
            return collect();
        }

        return $query->get();
    }
}
