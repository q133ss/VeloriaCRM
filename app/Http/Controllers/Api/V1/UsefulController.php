<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\UsefulDigestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsefulController extends Controller
{
    public function __construct(
        private readonly UsefulDigestService $usefulDigestService
    ) {
    }

    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();
        $locale = app()->getLocale();

        return response()->json(
            $this->usefulDigestService->buildOverviewPayload($user, $locale)
        );
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'channel' => ['required', 'string', 'in:platform,telegram,both'],
            'preferences' => ['nullable', 'string', 'max:2000'],
        ]);

        return response()->json([
            'data' => $this->usefulDigestService->updatePreferences($request->user(), $data),
            'message' => 'Useful digest preferences updated.',
        ]);
    }

    public function sendTestDigest(Request $request): JsonResponse
    {
        $locale = app()->getLocale();

        return response()->json([
            'data' => $this->usefulDigestService->sendTestDigest($request->user(), $locale),
            'message' => 'Test useful digest sent.',
        ]);
    }
}
