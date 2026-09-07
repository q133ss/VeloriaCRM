<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceFilterRequest;
use App\Http\Requests\ServiceFormRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Booking\ServiceDurationEstimator;
use App\Services\Catalog\ServiceDemand;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function index(
        ServiceFilterRequest $request,
        ServiceDurationEstimator $estimator,
        ServiceDemand $demand,
    ): JsonResponse {
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

        $services = Service::query()
            ->with('category')
            ->forUser($userId)
            ->withFilters($filters)
            ->orderBy($sort, $direction)
            ->get();

        $categories = ServiceCategory::where('user_id', $userId)
            ->orderBy('name')
            ->get();

        // The two things the price list never knew about itself: how long a
        // service really takes, and whether anyone books it.
        $context = [
            'measured' => $estimator->perServiceFor($userId),
            'demand' => $demand->forMaster($userId),
            'names' => Service::query()->forUser($userId)->pluck('name', 'id')->all(),
            'estimator' => $estimator,
        ];

        $groups = $this->groupServicesByCategory($services, $categories, $context);

        $allServicesQuery = Service::query()->forUser($userId);
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

        $total = (int) (clone $allServicesQuery)->count();

        return response()->json([
            'data' => [
                'groups' => $groups,
            ],
            'meta' => [
                'filters' => [
                    'search' => $filters['search'] ?? null,
                    'category_id' => $filters['category_id'] ?? null,
                    'sort' => $sort,
                    'direction' => $direction,
                ],
                'category_options' => $categoryOptions,
                'uncategorized' => [
                    'total_services' => $uncategorizedTotal,
                    'filtered_services' => $uncategorizedFiltered,
                ],
                'stats' => [
                    'total_filtered' => $services->count(),
                    'total_all' => $total,
                    'category_count' => $categories->count(),
                    // How many services the clock disagrees with. This is the
                    // one number on the page worth reading before the list.
                    'needs_review' => $this->needsReviewCount($userId, $context),
                ],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function needsReviewCount(int $userId, array $context): int
    {
        return Service::query()
            ->forUser($userId)
            ->get(['id', 'duration_min'])
            ->filter(fn (Service $service) => $this->durationFacts($service, $context)['needs_review'] ?? false)
            ->count();
    }

    /**
     * What the clock says about this service, next to what the price list says.
     *
     * @param  array<string, mixed>  $context
     * @return array{planned: ?int, measured: ?int, samples: ?int, needs_review: bool, hint: ?string, samples_text: ?string}
     */
    protected function durationFacts(Service $service, array $context): array
    {
        $planned = $service->duration_min !== null ? (int) $service->duration_min : null;
        $measurement = $context['measured'][$service->id] ?? null;

        $facts = [
            'planned' => $planned,
            'measured' => null,
            'samples' => null,
            'needs_review' => false,
            'hint' => null,
            'samples_text' => null,
        ];

        if (! $measurement || $planned === null) {
            return $facts;
        }

        /** @var ServiceDurationEstimator $estimator */
        $estimator = $context['estimator'];

        $facts['measured'] = (int) $measurement['minutes'];
        $facts['samples'] = (int) $measurement['samples'];
        $facts['needs_review'] = $estimator->worthSaying($facts['measured'], $planned);

        if ($facts['needs_review']) {
            $facts['hint'] = __('services.duration.measured', ['minutes' => $facts['measured']]);
            $facts['samples_text'] = __('services.duration.samples', [
                'count' => $facts['samples'],
                'unit' => $this->unit($facts['samples'], 'visits'),
            ]);
        }

        return $facts;
    }

    /**
     * Whether anyone books it, and what it brought in.
     *
     * @param  array<string, mixed>  $context
     */
    protected function demandFacts(Service $service, array $context): array
    {
        $stats = $context['demand'][$service->id] ?? null;
        $bookings = (int) ($stats['bookings'] ?? 0);
        $completed = (int) ($stats['completed'] ?? 0);

        if ($bookings < 1) {
            return [
                'bookings' => 0,
                'completed' => 0,
                'revenue' => 0.0,
                'text' => __('services.demand.never'),
                'note' => null,
            ];
        }

        return [
            'bookings' => $bookings,
            'completed' => $completed,
            'revenue' => round((float) ($stats['revenue'] ?? 0), 2),
            'text' => __('services.demand.bookings', [
                'count' => $bookings,
                'unit' => $this->unit($bookings, 'bookings'),
            ]),
            // Six bookings and nothing finished is the sentence that starts a
            // conversation about a service; the money alone would say «0 ₽».
            'note' => $completed < 1 ? __('services.demand.never_completed') : null,
        ];
    }

    /**
     * The services this one is offered with — the master's own list first, then
     * whatever the bookings actually show, when they show it often enough to
     * mean something.
     *
     * @param  array<string, mixed>  $context
     */
    protected function companionFacts(Service $service, array $context): array
    {
        $names = $context['names'] ?? [];

        $chosen = collect($service->upsell_suggestions ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => isset($names[$id]))
            ->map(fn (int $id) => ['id' => $id, 'name' => $names[$id]])
            ->values()
            ->all();

        $observed = collect($context['demand'][$service->id]['companions'] ?? [])
            ->filter(fn (int $count) => $count >= ServiceDemand::MIN_COMPANION_SAMPLES)
            ->sortDesc()
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => isset($names[$id]))
            ->reject(fn (int $id) => collect($chosen)->contains('id', $id))
            ->map(fn (int $id) => [
                'id' => $id,
                'name' => $names[$id],
                'count' => (int) $context['demand'][$service->id]['companions'][$id],
            ])
            ->values()
            ->all();

        return ['chosen' => $chosen, 'observed' => $observed];
    }

    /**
     * Russian needs three forms and English two; both live in the language file
     * so this stays a rule about numbers rather than a rule about words.
     */
    protected function unit(int $count, string $key): string
    {
        $forms = (array) __('services.units.' . $key);

        if (app()->getLocale() !== 'ru') {
            return $count === 1 ? ($forms['one'] ?? '') : ($forms['many'] ?? '');
        }

        $mod100 = $count % 100;

        if ($mod100 >= 11 && $mod100 <= 14) {
            return $forms['many'] ?? '';
        }

        return match ($count % 10) {
            1 => $forms['one'] ?? '',
            2, 3, 4 => $forms['few'] ?? '',
            default => $forms['many'] ?? '',
        };
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

    /**
     * @param  array<string, mixed>  $context
     */
    protected function groupServicesByCategory(Collection $services, Collection $categories, array $context = []): array
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
                'services' => $categoryServices->map(fn (Service $service) => $this->transformService($service, $context))->all(),
            ];
        }

        $uncategorized = $services->whereNull('category_id')->values();

        if ($uncategorized->count() > 0 || $categories->count() === 0) {
            $groups[] = [
                'id' => null,
                'name' => __('services.groups.uncategorized'),
                'services_count' => $uncategorized->count(),
                'services' => $uncategorized->map(fn (Service $service) => $this->transformService($service, $context))->all(),
            ];
        }

        return $groups;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function transformService(Service $service, array $context = []): array
    {
        $price = $service->base_price !== null ? (float) $service->base_price : null;
        $cost = $service->cost !== null ? (float) $service->cost : null;

        $payload = [
            'id' => $service->id,
            'name' => $service->name,
            'category_id' => $service->category_id,
            'category_name' => $service->category?->name,
            'base_price' => $price,
            'cost' => $cost,
            'margin' => $service->margin !== null ? round($service->margin, 2) : null,
            // The one money fact a list should interrupt for.
            'below_cost' => $price !== null && $cost !== null && $cost > $price,
            'duration_min' => $service->duration_min,
            'upsell_suggestions' => array_values(array_map('intval', $service->upsell_suggestions ?? [])),
            'created_at' => optional($service->created_at)->toIso8601String(),
            'updated_at' => optional($service->updated_at)->toIso8601String(),
        ];

        if ($context === []) {
            return $payload;
        }

        return $payload + [
            'duration' => $this->durationFacts($service, $context),
            'demand' => $this->demandFacts($service, $context),
            'companions' => $this->companionFacts($service, $context),
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
