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

        $knowledgeBase = collect(Arr::get($config, 'knowledge_base_links', []))
            ->map(function (array $item): array {
                return [
                    'title' => __($item['title']),
                    'description' => __($item['description']),
                    'url' => $item['url'],
                    'icon' => $item['icon'] ?? 'ri-book-2-line',
                ];
            })
            ->values()
            ->all();

        $faqs = collect(Arr::get($config, 'faqs', []))
            ->map(fn (array $item): array => [
                'question' => __($item['question']),
                'answer' => __($item['answer']),
            ])
            ->values()
            ->all();

        $supportConfig = Arr::get($config, 'support', []);
        $tips = collect(Arr::get($supportConfig, 'tips', []))
            ->map(fn ($tip) => __($tip))
            ->values()
            ->all();

        $responseTime = (int) ($supportConfig['response_time_hours'] ?? 24);

        return response()->json([
            'data' => [
                'knowledge_base' => $knowledgeBase,
                'faqs' => $faqs,
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
