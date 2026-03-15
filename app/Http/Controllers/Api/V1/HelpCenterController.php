<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class HelpCenterController extends Controller
{
    public function overview(): JsonResponse
    {
        $config = config('help');

        $supportConfig = Arr::get($config, 'support', []);
        $tips = collect(Arr::get($supportConfig, 'tips', []))
            ->map(fn ($tip) => __($tip))
            ->values()
            ->all();

        $responseTime = (int) ($supportConfig['response_time_hours'] ?? 24);

        return response()->json([
            'data' => [
                'support' => [
                    'contact_email' => $supportConfig['contact_email'] ?? null,
                    'response_time_hours' => $responseTime,
                    'response_time_text' => __('help.support.response_time', ['hours' => $responseTime]),
                    'working_hours' => __($supportConfig['working_hours'] ?? ''),
                    'tips' => $tips,
                ],
            ],
        ]);
    }
}
