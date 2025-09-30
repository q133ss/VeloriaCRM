<?php

namespace App\Http\Controllers\Api\V1\Marketing;

use App\Http\Requests\PromotionFilterRequest;
use App\Http\Requests\PromotionFormRequest;
use App\Http\Requests\PromotionUsageRequest;
use App\Models\Client;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PromotionController extends MarketingController
{
    public function index(PromotionFilterRequest $request): JsonResponse
    {
        $userId = $this->currentUserId();
        $filters = $request->validated();

        $query = Promotion::with('usages')->forUser($userId)->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['audience'])) {
            $query->where('audience', $filters['audience']);
        }

        $promotions = $query->get();

        $statusCounts = Promotion::forUser($userId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        return response()->json([
            'data' => $promotions->map(fn (Promotion $promotion) => $this->transformPromotion($promotion))->all(),
            'meta' => [
                'filters' => $filters,
                'status_counts' => $statusCounts,
                'totals' => [
                    'active' => $promotions->filter(fn (Promotion $promotion) => $promotion->isActive())->count(),
                    'archived' => $promotions->where('status', 'archived')->count(),
                ],
                'suggestions' => $this->buildPromotionSuggestions($promotions),
            ],
        ]);
    }

    public function store(PromotionFormRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->currentUserId();

        $promotion = Promotion::create([
            'user_id' => $userId,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'value' => Arr::get($validated, 'value'),
            'gift_description' => Arr::get($validated, 'gift_description'),
            'promo_code' => Arr::get($validated, 'promo_code'),
            'audience' => $validated['audience'],
            'conditions' => Arr::get($validated, 'conditions'),
            'starts_at' => Arr::get($validated, 'starts_at') ? Carbon::parse($validated['starts_at']) : null,
            'ends_at' => Arr::get($validated, 'ends_at') ? Carbon::parse($validated['ends_at']) : null,
            'status' => $validated['status'] ?? 'draft',
            'usage_limit' => Arr::get($validated, 'usage_limit'),
            'metadata' => Arr::get($validated, 'metadata'),
        ]);

        $promotion->load('usages');

        return response()->json([
            'data' => $this->transformPromotion($promotion),
            'message' => __('marketing.promotions.created'),
        ], 201);
    }

    public function show(Promotion $promotion): JsonResponse
    {
        $this->ensurePromotionBelongsToUser($promotion);
        $promotion->load('usages');

        return response()->json([
            'data' => $this->transformPromotion($promotion),
            'meta' => [
                'options' => $this->promotionOptions(),
                'analytics' => $this->buildPromotionAnalytics($promotion),
            ],
        ]);
    }

    public function update(PromotionFormRequest $request, Promotion $promotion): JsonResponse
    {
        $this->ensurePromotionBelongsToUser($promotion);
        $validated = $request->validated();

        $promotion->update([
            'name' => $validated['name'] ?? $promotion->name,
            'type' => $validated['type'] ?? $promotion->type,
            'value' => Arr::get($validated, 'value', $promotion->value),
            'gift_description' => Arr::get($validated, 'gift_description', $promotion->gift_description),
            'promo_code' => Arr::get($validated, 'promo_code', $promotion->promo_code),
            'audience' => $validated['audience'] ?? $promotion->audience,
            'conditions' => Arr::get($validated, 'conditions', $promotion->conditions),
            'starts_at' => Arr::get($validated, 'starts_at') ? Carbon::parse($validated['starts_at']) : $promotion->starts_at,
            'ends_at' => Arr::get($validated, 'ends_at') ? Carbon::parse($validated['ends_at']) : $promotion->ends_at,
            'status' => $validated['status'] ?? $promotion->status,
            'usage_limit' => Arr::get($validated, 'usage_limit', $promotion->usage_limit),
            'metadata' => Arr::get($validated, 'metadata', $promotion->metadata),
        ]);

        $promotion->load('usages');

        return response()->json([
            'data' => $this->transformPromotion($promotion),
            'message' => __('marketing.promotions.updated'),
        ]);
    }

    public function archive(Promotion $promotion): JsonResponse
    {
        $this->ensurePromotionBelongsToUser($promotion);
        $promotion->update(['status' => 'archived']);

        return response()->json([
            'data' => $this->transformPromotion($promotion->fresh('usages')),
            'message' => __('marketing.promotions.archived'),
        ]);
    }

    public function recordUsage(PromotionUsageRequest $request, Promotion $promotion): JsonResponse
    {
        $this->ensurePromotionBelongsToUser($promotion);
        $validated = $request->validated();

        $usage = $promotion->usages()->create([
            'client_id' => $validated['client_id'] ?? null,
            'order_id' => $validated['order_id'] ?? null,
            'revenue' => $validated['revenue'] ?? 0,
            'used_at' => Carbon::parse($validated['used_at']),
            'context' => Arr::get($validated, 'context'),
        ]);

        $this->refreshPromotionStats($promotion);

        return response()->json([
            'data' => $usage->toArray(),
            'promotion' => $this->transformPromotion($promotion->fresh('usages')),
            'message' => __('marketing.promotions.usage_recorded'),
        ]);
    }

    public function options(): JsonResponse
    {
        return response()->json([
            'data' => $this->promotionOptions(),
        ]);
    }

    protected function promotionOptions(): array
    {
        $userId = $this->currentUserId();
        $clients = Client::where('user_id', $userId)->count();

        return [
            'types' => [
                ['value' => 'percentage', 'label' => __('marketing.promotions.types.percentage')],
                ['value' => 'fixed', 'label' => __('marketing.promotions.types.fixed')],
                ['value' => 'gift', 'label' => __('marketing.promotions.types.gift')],
                ['value' => 'bogo', 'label' => __('marketing.promotions.types.bogo')],
                ['value' => 'loyalty', 'label' => __('marketing.promotions.types.loyalty')],
            ],
            'audiences' => [
                ['value' => 'all', 'label' => __('marketing.promotions.audiences.all'), 'count' => $clients],
                ['value' => 'new', 'label' => __('marketing.promotions.audiences.new')],
                ['value' => 'loyal', 'label' => __('marketing.promotions.audiences.loyal')],
                ['value' => 'custom', 'label' => __('marketing.promotions.audiences.custom')],
            ],
        ];
    }

    protected function buildPromotionAnalytics(Promotion $promotion): array
    {
        $promotion->loadMissing('usages');
        $usages = $promotion->usages;
        $uniqueClients = $usages->pluck('client_id')->filter()->unique()->count();
        $revenue = (float) $usages->sum('revenue');

        return [
            'usage_count' => $usages->count(),
            'unique_clients' => $uniqueClients,
            'revenue_generated' => $revenue,
            'last_used_at' => optional($usages->sortByDesc('used_at')->first())->used_at?->toIso8601String(),
        ];
    }

    protected function transformPromotion(Promotion $promotion): array
    {
        $analytics = $this->buildPromotionAnalytics($promotion);
        $isActive = $promotion->isActive();

        return [
            'id' => $promotion->id,
            'name' => $promotion->name,
            'type' => $promotion->type,
            'value' => $promotion->value,
            'gift_description' => $promotion->gift_description,
            'promo_code' => $promotion->promo_code,
            'audience' => $promotion->audience,
            'conditions' => $promotion->conditions,
            'starts_at' => optional($promotion->starts_at)->toIso8601String(),
            'ends_at' => optional($promotion->ends_at)->toIso8601String(),
            'status' => $promotion->status,
            'usage_limit' => $promotion->usage_limit,
            'metrics' => $analytics,
            'is_active' => $isActive,
            'created_at' => optional($promotion->created_at)->toIso8601String(),
            'updated_at' => optional($promotion->updated_at)->toIso8601String(),
        ];
    }

    protected function ensurePromotionBelongsToUser(Promotion $promotion): void
    {
        if ($promotion->user_id !== $this->currentUserId()) {
            abort(403);
        }
    }

    protected function refreshPromotionStats(Promotion $promotion): void
    {
        $promotion->loadMissing('usages');
        $usages = $promotion->usages;

        $promotion->update([
            'usage_count' => $usages->count(),
            'unique_clients' => $usages->pluck('client_id')->filter()->unique()->count(),
            'revenue_generated' => (float) $usages->sum('revenue'),
        ]);
    }

    protected function buildPromotionSuggestions(Collection $promotions): array
    {
        if ($promotions->isEmpty()) {
            return [[
                'title' => __('marketing.promotions.suggestions.start_title'),
                'description' => __('marketing.promotions.suggestions.start_description'),
            ]];
        }

        $active = $promotions->filter(fn (Promotion $promotion) => $promotion->isActive());
        $drafts = $promotions->where('status', 'draft');

        $suggestions = [];

        if ($drafts->isNotEmpty()) {
            $suggestions[] = [
                'title' => __('marketing.promotions.suggestions.finish_title'),
                'description' => __('marketing.promotions.suggestions.finish_description', ['count' => $drafts->count()]),
            ];
        }

        if ($active->isNotEmpty()) {
            $suggestions[] = [
                'title' => __('marketing.promotions.suggestions.track_title'),
                'description' => __('marketing.promotions.suggestions.track_description'),
            ];
        }

        if ($suggestions === []) {
            $suggestions[] = [
                'title' => __('marketing.promotions.suggestions.iterate_title'),
                'description' => __('marketing.promotions.suggestions.iterate_description'),
            ];
        }

        return $suggestions;
    }
}
