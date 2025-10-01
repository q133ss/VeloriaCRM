<?php

namespace App\Services;

use App\Models\LearningRecommendation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class AIService extends BaseService
{
    public function __construct(private readonly OpenAIService $openAI)
    {
    }

    public function summarizeLearningPlan(User $user, Collection $insights, string $locale): array
    {
        $context = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'locale' => $locale,
            'insights' => $insights->map(function (LearningRecommendation $insight) use ($locale) {
                return [
                    'title' => $insight->getTranslationAsString('title', $locale, 'en'),
                    'description' => $insight->getTranslationAsString('description', $locale, 'en'),
                    'impact' => $insight->getTranslationAsString('impact_text', $locale, 'en'),
                    'action' => $insight->getTranslationAsString('action', $locale, 'en'),
                    'type' => $insight->type,
                    'confidence' => $insight->confidence,
                ];
            })->values()->all(),
        ];

        if (!$this->canUseAi()) {
            return $this->fallbackSummary($locale, $context);
        }

        try {
            $response = $this->openAI->respond(
                $this->prompt($locale),
                $context,
                [
                    'response_format' => ['type' => 'json_object'],
                    'max_tokens' => 400,
                ]
            );

            $content = $response['content'] ?? null;
            if (is_string($content)) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return [
                        'headline' => $decoded['headline'] ?? $this->fallbackHeadline($locale),
                        'description' => $decoded['description'] ?? $this->fallbackDescription($locale, count($context['insights'])),
                        'tips' => $this->normalizeTips($decoded['tips'] ?? [], $locale, $context['insights']),
                    ];
                }
            }
        } catch (Throwable $exception) {
            Log::debug('AI learning summary failed', [
                'exception' => $exception->getMessage(),
            ]);
        }

        return $this->fallbackSummary($locale, $context);
    }

    protected function canUseAi(): bool
    {
        return filled(config('openai.api_key'));
    }

    protected function prompt(string $locale): string
    {
        $language = $locale === 'ru' ? 'Russian' : 'English';

        return <<<PROMPT
You are an assistant that summarises coaching insights for beauty masters.
Analyse the provided insights and craft a short actionable overview in {$language}.
Respond strictly in JSON with keys: headline (string), description (string), tips (array of up to 3 short strings).
Focus on motivation and next actions for the upcoming week.
PROMPT;
    }

    protected function fallbackSummary(string $locale, array $context): array
    {
        $insights = $context['insights'] ?? [];

        return [
            'headline' => $this->fallbackHeadline($locale),
            'description' => $this->fallbackDescription($locale, count($insights)),
            'tips' => $this->normalizeTips([], $locale, $insights),
        ];
    }

    protected function fallbackHeadline(string $locale): string
    {
        return trans('learning.plan.ai_summary.fallback.headline', [], $locale);
    }

    protected function fallbackDescription(string $locale, int $insightsCount): string
    {
        return trans('learning.plan.ai_summary.fallback.description', ['count' => $insightsCount], $locale);
    }

    protected function normalizeTips(array $tips, string $locale, array $insights): array
    {
        $tips = array_values(array_filter($tips, fn ($tip) => is_string($tip) && trim($tip) !== ''));

        if (!empty($tips)) {
            return array_slice($tips, 0, 3);
        }

        $fallbacks = [];
        foreach ($insights as $insight) {
            $title = $insight['title'] ?? null;
            $impact = $insight['impact'] ?? null;
            $action = $insight['action'] ?? null;

            $parts = array_filter([$title, $action, $impact], fn ($value) => is_string($value) && trim($value) !== '');

            if (!empty($parts)) {
                $fallbacks[] = implode(' — ', $parts);
            }

            if (count($fallbacks) >= 3) {
                break;
            }
        }

        if (empty($fallbacks)) {
            $fallbacks[] = trans('learning.plan.ai_summary.fallback.tip_default', [], $locale);
        }

        return $fallbacks;
    }
}
