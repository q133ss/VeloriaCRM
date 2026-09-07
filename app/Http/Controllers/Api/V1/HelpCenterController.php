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

        // The answers used to live in the config and reach nobody: the page
        // rendered only the contact form, and a master looking for «почему
        // клиент не получил напоминание» found an empty textarea.
        $faq = collect(Arr::get($config, 'faqs', []))
            ->map(fn (array $item) => [
                'question' => __($item['question']),
                'answer' => __($item['answer']),
                'link' => isset($item['link'])
                    ? ['label' => __($item['link']['label']), 'url' => $item['link']['url']]
                    : null,
            ])
            ->values()
            ->all();

        $extensions = Arr::get($config, 'attachment.extensions', []);

        return response()->json([
            'data' => [
                'faq' => $faq,
                'support' => [
                    'contact_email' => $supportConfig['contact_email'] ?? null,
                    'response_time_hours' => $responseTime,
                    'response_time_text' => __('help.support.response_time', ['hours' => $responseTime]),
                    'working_hours' => __($supportConfig['working_hours'] ?? ''),
                    'tips' => $tips,
                ],
                // The form can now say the rules the validator enforces.
                'limits' => [
                    'subject_max' => (int) Arr::get($config, 'limits.subject_max', 255),
                    'message_min' => (int) Arr::get($config, 'limits.message_min', 10),
                    'reply_min' => (int) Arr::get($config, 'limits.reply_min', 3),
                ],
                'attachment' => [
                    'max_mb' => (int) Arr::get($config, 'attachment.max_mb', 10),
                    'extensions' => array_values($extensions),
                    'accept' => collect($extensions)->map(fn ($ext) => '.' . $ext)->implode(','),
                ],
            ],
        ]);
    }
}
