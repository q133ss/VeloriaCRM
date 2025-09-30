<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCategoryFormRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ServiceCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $userId = $this->currentUserId();

        $categories = ServiceCategory::where('user_id', $userId)
            ->withCount('services')
            ->orderBy('name')
            ->get()
            ->map(fn (ServiceCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'services_count' => (int) $category->services_count,
            ])
            ->values()
            ->all();

        $uncategorized = Service::where('user_id', $userId)
            ->whereNull('category_id')
            ->count();

        return response()->json([
            'data' => [
                'categories' => $categories,
                'uncategorized_services' => $uncategorized,
            ],
        ]);
    }

    public function store(ServiceCategoryFormRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $category = ServiceCategory::create([
            'user_id' => $this->currentUserId(),
            'name' => $validated['name'],
        ]);

        return response()->json([
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
            'message' => __('services.messages.category_created'),
        ], 201);
    }

    public function update(ServiceCategoryFormRequest $request, ServiceCategory $category): JsonResponse
    {
        $this->ensureCategoryBelongsToCurrentUser($category);
        $validated = $request->validated();

        $category->update(['name' => $validated['name']]);

        return response()->json([
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
            'message' => __('services.messages.category_updated'),
        ]);
    }

    public function destroy(ServiceCategory $category): JsonResponse
    {
        $this->ensureCategoryBelongsToCurrentUser($category);
        $category->delete();

        return response()->json([
            'message' => __('services.messages.category_deleted'),
        ]);
    }

    protected function ensureCategoryBelongsToCurrentUser(ServiceCategory $category): void
    {
        if ($category->user_id !== $this->currentUserId()) {
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
