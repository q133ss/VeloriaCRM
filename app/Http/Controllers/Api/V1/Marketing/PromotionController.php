<?php

namespace App\Http\Controllers\Api\V1\Marketing;

use App\Http\Requests\PromotionFilterRequest;
use App\Http\Requests\PromotionFormRequest;
use App\Http\Requests\PromotionUsageRequest;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\Service;
use App\Models\ServiceCategory;
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

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (array_key_exists('archived', $filters) && $filters['archived'] !== null) {
            if ($filters['archived']) {
                $query->whereNotNull('archived_at');
            } else {
                $query->whereNull('archived_at');
            }
        }

        $promotions = $query->get();

        $typeCounts = Promotion::forUser($userId)
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->all();

        return response()->json([
            'data' => $promotions->map(fn (Promotion $promotion) => $this->transformPromotion($promotion))->all(),
            'meta' => [
                'filters' => $filters,
                'type_counts' => $typeCounts,
                'totals' => [
                    'active' => $promotions->filter(fn (Promotion $promotion) => $promotion->isActive())->count(),
                    'archived' => $promotions->whereNotNull('archived_at')->count(),
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
            'percent' => Arr::get($validated, 'percent'),
            'service_id' => Arr::get($validated, 'service_id'),
            'service_category_id' => Arr::get($validated, 'service_category_id'),
            'gift_description' => Arr::get($validated, 'gift_description'),
            'promo_code' => Arr::get($validated, 'promo_code'),
            'starts_at' => Arr::get($validated, 'starts_at') ? Carbon::parse($validated['starts_at']) : null,
            'ends_at' => Arr::get($validated, 'ends_at') ? Carbon::parse($validated['ends_at']) : null,
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
            'percent' => Arr::get($validated, 'percent', $promotion->percent),
            'service_id' => Arr::get($validated, 'service_id', $promotion->service_id),
            'service_category_id' => Arr::get($validated, 'service_category_id', $promotion->service_category_id),
            'gift_description' => Arr::get($validated, 'gift_description', $promotion->gift_description),
            'promo_code' => Arr::get($validated, 'promo_code', $promotion->promo_code),
            'starts_at' => Arr::get($validated, 'starts_at') ? Carbon::parse($validated['starts_at']) : $promotion->starts_at,
            'ends_at' => Arr::get($validated, 'ends_at') ? Carbon::parse($validated['ends_at']) : $promotion->ends_at,
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
        $promotion->update(['archived_at' => Carbon::now()]);

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
        $services = Service::where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name']);
        $categories = ServiceCategory::where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'types' => [
                ['value' => 'order_percent', 'label' => __('marketing.promotions.types.order_percent')],
                ['value' => 'service_percent', 'label' => __('marketing.promotions.types.service_percent')],
                ['value' => 'category_percent', 'label' => __('marketing.promotions.types.category_percent')],
                ['value' => 'free_service', 'label' => __('marketing.promotions.types.free_service')],
            ],
            'services' => $services->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
            ])->values()->all(),
            'categories' => $categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values()->all(),
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
            'percent' => $promotion->percent,
            'service_id' => $promotion->service_id,
            'service_category_id' => $promotion->service_category_id,
            'gift_description' => $promotion->gift_description,
            'promo_code' => $promotion->promo_code,
            'starts_at' => optional($promotion->starts_at)->toIso8601String(),
            'ends_at' => optional($promotion->ends_at)->toIso8601String(),
            'usage_limit' => $promotion->usage_limit,
            'metrics' => $analytics,
            'is_active' => $isActive,
            'is_archived' => (bool) $promotion->archived_at,
            'archived_at' => optional($promotion->archived_at)->toIso8601String(),
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
        $upcoming = $promotions->filter(function (Promotion $promotion) {
            return $promotion->archived_at === null
                && $promotion->starts_at !== null
                && $promotion->starts_at->isFuture();
        });

        $suggestions = [];

        if ($upcoming->isNotEmpty()) {
            $suggestions[] = [
                'title' => __('marketing.promotions.suggestions.finish_title'),
                'description' => __('marketing.promotions.suggestions.finish_description', ['count' => $upcoming->count()]),
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
