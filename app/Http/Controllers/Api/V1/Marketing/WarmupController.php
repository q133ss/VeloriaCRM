<?php

namespace App\Http\Controllers\Api\V1\Marketing;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WarmupController extends MarketingController
{
    public function index(): JsonResponse
    {
        if (! $this->userHasEliteAccess()) {
            return response()->json([
                'error' => [
                    'code' => 'plan_restriction',
                    'message' => __('marketing.warmup.only_elite'),
                ],
            ], 403);
        }

        $userId = $this->currentUserId();
        $clients = Client::where('user_id', $userId)
            ->orderByDesc('last_visit_at')
            ->get();

        $now = Carbon::now();

        $almostSleeping = $clients->filter(function (Client $client) use ($now) {
            if (! $client->last_visit_at) {
                return false;
            }

            $days = $client->last_visit_at->diffInDays($now);

            return $days >= 75 && $days < 90;
        });

        $sleeping = $clients->filter(function (Client $client) use ($now) {
            if (! $client->last_visit_at) {
                return $client->created_at?->diffInDays($now) > 120;
            }

            return $client->last_visit_at->diffInDays($now) >= 90;
        });

        $newIdle = $clients->filter(function (Client $client) use ($now) {
            if ($client->last_visit_at) {
                return false;
            }

            return $client->created_at?->diffInDays($now) >= 14 && $client->created_at?->diffInDays($now) <= 45;
        });

        $groups = [
            'almost_sleeping' => $this->serializeClients($almostSleeping, $now),
            'sleeping' => $this->serializeClients($sleeping, $now),
            'new_idle' => $this->serializeClients($newIdle, $now),
        ];

        $suggestions = $this->buildWarmupSuggestions($groups);

        return response()->json([
            'data' => [
                'groups' => $groups,
                'suggestions' => $suggestions,
            ],
            'meta' => [
                'ai_summary' => __('marketing.warmup.summary', [
                    'sleeping' => count($groups['sleeping']),
                    'almost' => count($groups['almost_sleeping']),
                    'new' => count($groups['new_idle']),
                ]),
            ],
        ]);
    }

    protected function serializeClients(Collection $clients, Carbon $now): array
    {
        return $clients->sortByDesc(function (Client $client) use ($now) {
            return $this->daysSinceLastInteraction($client, $now);
        })->map(function (Client $client) use ($now) {
            $days = $this->daysSinceLastInteraction($client, $now);
            $lastVisit = $client->last_visit_at ?? $client->created_at;

            return [
                'id' => $client->id,
                'name' => $client->name,
                'loyalty_level' => $client->loyalty_level,
                'tags' => $client->tags ?? [],
                'last_visit_at' => $lastVisit?->toIso8601String(),
                'days_since_touch' => $days,
            ];
        })->values()->all();
    }

    protected function daysSinceLastInteraction(Client $client, Carbon $now): int
    {
        $reference = $client->last_visit_at ?? $client->created_at ?? $now;

        return $reference->diffInDays($now);
    }

    protected function buildWarmupSuggestions(array $groups): array
    {
        $sleeping = collect($groups['sleeping'] ?? []);
        $almost = collect($groups['almost_sleeping'] ?? []);
        $newIdle = collect($groups['new_idle'] ?? []);

        $suggestions = [];

        if ($sleeping->isNotEmpty()) {
            $targets = $sleeping->sortByDesc('days_since_touch')->take(15)->values()->all();
            $suggestions[] = [
                'title' => __('marketing.warmup.suggestions.sleeping_title'),
                'description' => __('marketing.warmup.suggestions.sleeping_description', ['count' => count($targets)]),
                'recommended_action' => __('marketing.warmup.suggestions.sleeping_action'),
                'clients' => $targets,
            ];
        }

        if ($almost->isNotEmpty()) {
            $targets = $almost->filter(fn ($client) => $client['days_since_touch'] >= 89)->take(30)->values()->all();

            if ($targets === []) {
                $targets = $almost->sortByDesc('days_since_touch')->take(30)->values()->all();
            }

            $suggestions[] = [
                'title' => __('marketing.warmup.suggestions.almost_title'),
                'description' => __('marketing.warmup.suggestions.almost_description', ['count' => count($targets)]),
                'recommended_action' => __('marketing.warmup.suggestions.almost_action'),
                'clients' => $targets,
            ];
        }

        if ($newIdle->isNotEmpty()) {
            $targets = $newIdle->take(20)->values()->all();
            $suggestions[] = [
                'title' => __('marketing.warmup.suggestions.new_title'),
                'description' => __('marketing.warmup.suggestions.new_description', ['count' => count($targets)]),
                'recommended_action' => __('marketing.warmup.suggestions.new_action'),
                'clients' => $targets,
            ];
        }

        if ($suggestions === []) {
            $suggestions[] = [
                'title' => __('marketing.warmup.suggestions.no_data_title'),
                'description' => __('marketing.warmup.suggestions.no_data_description'),
                'recommended_action' => null,
                'clients' => [],
            ];
        }

        return $suggestions;
    }
}
