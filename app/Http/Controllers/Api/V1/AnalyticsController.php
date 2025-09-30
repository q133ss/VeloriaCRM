<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyticsFilterRequest;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics)
    {
    }

    public function index(AnalyticsFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $userId = $this->currentUserId();

        $payload = $this->analytics->build(
            $userId,
            $filters['start_date'],
            $filters['end_date'],
            $filters['group_by'],
            $filters['compare_to'],
        );

        return response()->json([
            'data' => $payload,
        ]);
    }
}
