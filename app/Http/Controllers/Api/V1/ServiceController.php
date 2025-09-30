<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceFilterRequest;
use App\Http\Requests\ServiceFormRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function index(ServiceFilterRequest $request): JsonResponse
    {
        $userId = $this->currentUserId();
        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'name';
        $direction = $filters['direction'] ?? 'asc';

        if (! in_array($sort, ['name', 'base_price', 'duration_min', 'created_at'], true)) {
            $sort = 'name';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $query = Service::with('category')->where('user_id', $userId);

        if ($filters['search'] ?? null) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (($filters['category_id'] ?? null) !== null) {
            $query->where('category_id', $filters['category_id']);
        }

        if (($filters['price_min'] ?? null) !== null) {
            $query->where('base_price', '>=', $filters['price_min']);
        }

        if (($filters['price_max'] ?? null) !== null) {
            $query->where('base_price', '<=', $filters['price_max']);
        }

        if (($filters['duration_min'] ?? null) !== null) {
            $query->where('duration_min', '>=', $filters['duration_min']);
        }

        if (($filters['duration_max'] ?? null) !== null) {
            $query->where('duration_min', '<=', $filters['duration_max']);
        }

        $services = $query->orderBy($sort, $direction)->get();

        $categories = ServiceCategory::where('user_id', $userId)
            ->orderBy('name')
            ->get();

        $groups = $this->groupServicesByCategory($services, $categories);

        $allServicesQuery = Service::where('user_id', $userId);
        $globalCounts = (clone $allServicesQuery)
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id')
            ->all();

        $filteredCounts = $services
            ->groupBy(fn (Service $service) => $service->category_id)
            ->map->count()
            ->all();

        $categoryOptions = $categories->map(function (ServiceCategory $category) use ($globalCounts, $filteredCounts) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'total_services' => (int) ($globalCounts[$category->id] ?? 0),
                'filtered_services' => (int) ($filteredCounts[$category->id] ?? 0),
            ];
        })->values()->all();

        $uncategorizedTotal = (int) ($globalCounts[null] ?? ($globalCounts[''] ?? 0));
        $uncategorizedFiltered = (int) ($filteredCounts[null] ?? ($filteredCounts[''] ?? 0));

        $aggregates = (clone $allServicesQuery)
            ->selectRaw('MIN(base_price) as min_price, MAX(base_price) as max_price, MIN(duration_min) as min_duration, MAX(duration_min) as max_duration, COUNT(*) as total')
            ->first();

        $stats = [
            'total_filtered' => $services->count(),
            'total_all' => (int) ($aggregates?->total ?? 0),
            'category_count' => $categories->count(),
            'avg_price' => $services->count() > 0 ? round($services->avg('base_price'), 2) : 0.0,
            'avg_duration' => $services->count() > 0 ? (int) round($services->avg('duration_min')) : 0,
            'price_range' => [
                'min' => $aggregates?->min_price !== null ? (float) $aggregates->min_price : null,
                'max' => $aggregates?->max_price !== null ? (float) $aggregates->max_price : null,
            ],
            'duration_range' => [
                'min' => $aggregates?->min_duration !== null ? (int) $aggregates->min_duration : null,
                'max' => $aggregates?->max_duration !== null ? (int) $aggregates->max_duration : null,
            ],
        ];

        return response()->json([
            'data' => [
                'groups' => $groups,
            ],
            'meta' => [
                'filters' => [
                    'search' => $filters['search'] ?? null,
                    'category_id' => $filters['category_id'] ?? null,
                    'price_min' => $filters['price_min'] ?? null,
                    'price_max' => $filters['price_max'] ?? null,
                    'duration_min' => $filters['duration_min'] ?? null,
                    'duration_max' => $filters['duration_max'] ?? null,
                    'sort' => $sort,
                    'direction' => $direction,
                ],
                'category_options' => $categoryOptions,
                'uncategorized' => [
                    'total_services' => $uncategorizedTotal,
                    'filtered_services' => $uncategorizedFiltered,
                ],
                'stats' => $stats,
            ],
        ]);
    }

    public function store(ServiceFormRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->currentUserId();

        $service = Service::create([
            'user_id' => $userId,
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'base_price' => $validated['base_price'],
            'cost' => $validated['cost'] ?? null,
            'duration_min' => $validated['duration_min'],
            'upsell_suggestions' => Arr::get($validated, 'upsell_suggestions', []),
        ]);

        $service->loadMissing('category');

        return response()->json([
            'data' => $this->transformService($service),
            'message' => __('services.messages.created'),
        ], 201);
    }

    public function show(Service $service): JsonResponse
    {
        $this->ensureServiceBelongsToCurrentUser($service);
        $service->loadMissing('category');

        return response()->json([
            'data' => $this->transformService($service),
            'meta' => [
                'category_options' => $this->categoryOptions($this->currentUserId()),
            ],
        ]);
    }

    public function update(ServiceFormRequest $request, Service $service): JsonResponse
    {
        $this->ensureServiceBelongsToCurrentUser($service);
        $validated = $request->validated();

        $service->update([
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'base_price' => $validated['base_price'],
            'cost' => $validated['cost'] ?? null,
            'duration_min' => $validated['duration_min'],
            'upsell_suggestions' => Arr::get($validated, 'upsell_suggestions', []),
        ]);

        $service->loadMissing('category');

        return response()->json([
            'data' => $this->transformService($service),
            'message' => __('services.messages.updated'),
        ]);
    }

    public function destroy(Service $service): JsonResponse
    {
        $this->ensureServiceBelongsToCurrentUser($service);
        $service->delete();

        return response()->json([
            'message' => __('services.messages.deleted'),
        ]);
    }

    public function options(): JsonResponse
    {
        $userId = $this->currentUserId();

        $categories = ServiceCategory::where('user_id', $userId)
            ->withCount('services')
            ->orderBy('name')
            ->get()
            ->map(function (ServiceCategory $category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'services_count' => (int) $category->services_count,
                ];
            })
            ->values()
            ->all();

        $aggregates = Service::where('user_id', $userId)
            ->selectRaw('MIN(base_price) as min_price, MAX(base_price) as max_price, MIN(duration_min) as min_duration, MAX(duration_min) as max_duration')
            ->first();

        return response()->json([
            'data' => [
                'categories' => $categories,
                'price_range' => [
                    'min' => $aggregates?->min_price !== null ? (float) $aggregates->min_price : null,
                    'max' => $aggregates?->max_price !== null ? (float) $aggregates->max_price : null,
                ],
                'duration_range' => [
                    'min' => $aggregates?->min_duration !== null ? (int) $aggregates->min_duration : null,
                    'max' => $aggregates?->max_duration !== null ? (int) $aggregates->max_duration : null,
                ],
            ],
        ]);
    }

    protected function groupServicesByCategory(Collection $services, Collection $categories): array
    {
        $groups = [];

        foreach ($categories as $category) {
            $categoryServices = $services
                ->where('category_id', $category->id)
                ->values();

            $groups[] = [
                'id' => $category->id,
                'name' => $category->name,
                'services_count' => $categoryServices->count(),
                'services' => $categoryServices->map(fn (Service $service) => $this->transformService($service))->all(),
            ];
        }

        $uncategorized = $services->whereNull('category_id')->values();

        if ($uncategorized->count() > 0 || $categories->count() === 0) {
            $groups[] = [
                'id' => null,
                'name' => __('services.groups.uncategorized'),
                'services_count' => $uncategorized->count(),
                'services' => $uncategorized->map(fn (Service $service) => $this->transformService($service))->all(),
            ];
        }

        return $groups;
    }

    protected function transformService(Service $service): array
    {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'category_id' => $service->category_id,
            'category_name' => $service->category?->name,
            'base_price' => $service->base_price !== null ? (float) $service->base_price : null,
            'cost' => $service->cost !== null ? (float) $service->cost : null,
            'margin' => $service->margin !== null ? round($service->margin, 2) : null,
            'duration_min' => $service->duration_min,
            'upsell_suggestions' => array_values($service->upsell_suggestions ?? []),
            'created_at' => optional($service->created_at)->toIso8601String(),
            'updated_at' => optional($service->updated_at)->toIso8601String(),
        ];
    }

    protected function categoryOptions(int $userId): array
    {
        return ServiceCategory::where('user_id', $userId)
            ->orderBy('name')
            ->get()
            ->map(fn (ServiceCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->values()
            ->all();
    }

    protected function ensureServiceBelongsToCurrentUser(Service $service): void
    {
        if ($service->user_id !== $this->currentUserId()) {
            abort(403);
        }
    }

    protected function currentUserId(): int
    {
        $userId = Auth::guard('sanctum')->id();

        if (! $userId) {
            abort(403);
        }

        return $userId;
    }
}
