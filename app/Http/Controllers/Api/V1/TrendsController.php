<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LearningArticle;
use App\Models\LearningCategory;
use App\Models\LearningLesson;
use App\Models\LearningRecommendation;
use App\Models\LearningTemplate;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TrendsController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();
        $locale = app()->getLocale();

        $services = Service::forUser($user->id)
            ->with('category')
            ->orderByDesc('id')
            ->get();

        $specialty = $this->resolveSpecialty($services, $locale);
        $keywords = $specialty['keywords'];

        $categories = LearningCategory::withCount('lessons')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $lessons = LearningLesson::with('category')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $articles = LearningArticle::orderBy('id')->get();
        $templates = LearningTemplate::orderBy('position')->orderBy('id')->get();
        $signals = LearningRecommendation::forUser($user)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->limit(3)
            ->get();

        $sortedLessons = $this->sortBySpecialtyMatch($lessons, $keywords, function (LearningLesson $lesson) use ($locale) {
            return implode(' ', array_filter([
                $lesson->getTranslationAsString('title', $locale, 'en'),
                $lesson->getTranslationAsString('summary', $locale, 'en'),
                $lesson->category?->getTranslationAsString('title', $locale, 'en'),
            ]));
        });

        $sortedArticles = $this->sortBySpecialtyMatch($articles, $keywords, function (LearningArticle $article) use ($locale) {
            return implode(' ', array_filter([
                $article->getTranslationAsString('title', $locale, 'en'),
                $article->getTranslationAsString('summary', $locale, 'en'),
                $article->topic,
            ]));
        });

        $spotlight = $sortedLessons->first() ?? $lessons->first();
        $articleSpotlight = $sortedArticles->first() ?? $articles->first();

        return response()->json([
            'meta' => [
                'title' => 'Тренды',
                'subtitle' => 'Актуальные техники, идеи и форматы услуг для вашей ниши.',
                'specialty' => [
                    'label' => $specialty['label'],
                    'hint' => $specialty['hint'],
                ],
            ],
            'spotlight' => $this->transformSpotlight($spotlight, $articleSpotlight, $locale),
            'signals' => $this->transformSignals($signals, $specialty['label'], $locale),
            'trend_cards' => $sortedLessons
                ->take(6)
                ->map(fn (LearningLesson $lesson) => $this->transformLesson($lesson, $locale))
                ->values()
                ->all(),
            'articles' => $sortedArticles
                ->take(8)
                ->map(fn (LearningArticle $article) => $this->transformArticle($article, $locale))
                ->values()
                ->all(),
            'playbooks' => $templates
                ->take(6)
                ->map(fn (LearningTemplate $template) => $this->transformTemplate($template, $locale))
                ->values()
                ->all(),
            'categories' => $categories
                ->map(fn (LearningCategory $category) => [
                    'slug' => $category->slug,
                    'name' => $category->getTranslationAsString('title', $locale, 'en'),
                    'description' => $category->getTranslationAsString('description', $locale, 'en'),
                    'icon' => $category->icon ?: 'ri-sparkling-line',
                    'count' => $category->lessons_count,
                ])
                ->values()
                ->all(),
        ]);
    }

    protected function resolveSpecialty(Collection $services, string $locale): array
    {
        $category = $services
            ->filter(fn (Service $service) => $service->category)
            ->groupBy(fn (Service $service) => $service->category_id)
            ->sortByDesc(fn (Collection $group) => $group->count())
            ->map(fn (Collection $group) => $group->first()->category)
            ->first();

        if ($category) {
            $label = $category->name;

            return [
                'label' => $label,
                'hint' => 'Подобрано под вашу специализацию и похожие запросы клиенток.',
                'keywords' => $this->keywordBag([$label]),
            ];
        }

        $serviceNames = $services->pluck('name')->filter()->take(3)->values()->all();
        $label = !empty($serviceNames) ? implode(', ', $serviceNames) : 'вашей специализации';

        return [
            'label' => $label,
            'hint' => 'Показываем идеи, которые помогут оставаться актуальной для вашей аудитории.',
            'keywords' => $this->keywordBag($serviceNames),
        ];
    }

    protected function keywordBag(array $items): array
    {
        return collect($items)
            ->filter()
            ->flatMap(function (string $item) {
                return preg_split('/[\s,.;:!?()\/\\\\-]+/u', mb_strtolower($item)) ?: [];
            })
            ->map(fn (string $keyword) => trim($keyword))
            ->filter(fn (string $keyword) => mb_strlen($keyword) >= 3)
            ->unique()
            ->values()
            ->all();
    }

    protected function sortBySpecialtyMatch(Collection $items, array $keywords, callable $extractor): Collection
    {
        if (empty($keywords)) {
            return $items->values();
        }

        return $items
            ->sortByDesc(function ($item) use ($keywords, $extractor) {
                $haystack = mb_strtolower((string) $extractor($item));

                return collect($keywords)->sum(function (string $keyword) use ($haystack) {
                    return Str::contains($haystack, $keyword) ? 1 : 0;
                });
            })
            ->values();
    }

    protected function transformSpotlight(?LearningLesson $lesson, ?LearningArticle $article, string $locale): array
    {
        return [
            'eyebrow' => 'Главное сейчас',
            'title' => $lesson?->getTranslationAsString('title', $locale, 'en')
                ?? $article?->getTranslationAsString('title', $locale, 'en')
                ?? 'Тренд недели',
            'summary' => $lesson?->getTranslationAsString('summary', $locale, 'en')
                ?? $article?->getTranslationAsString('summary', $locale, 'en')
                ?? 'Следите за тем, что хотят клиентки прямо сейчас, и адаптируйте предложение под спрос.',
            'takeaway' => $article?->getTranslationAsString('summary', $locale, 'en')
                ?? 'Добавьте один актуальный формат услуги в коммуникацию и посмотрите на отклик.',
            'period_label' => now()->locale($locale)->isoFormat('MMMM YYYY'),
        ];
    }

    protected function transformSignals(Collection $signals, string $specialtyLabel, string $locale): array
    {
        if ($signals->isEmpty()) {
            return [[
                'title' => 'Спрос смещается в сторону новых форматов',
                'description' => 'Клиентки чаще откликаются на понятные визуальные тренды и быстрые обновления образа.',
                'next_step' => 'Сделайте пост или сторис про 1 новый эффект для направления ' . $specialtyLabel . '.',
            ]];
        }

        return $signals->map(function (LearningRecommendation $signal) use ($locale) {
            return [
                'title' => $signal->getTranslationAsString('title', $locale, 'en'),
                'description' => $signal->getTranslationAsString('description', $locale, 'en'),
                'next_step' => $signal->getTranslationAsString('action', $locale, 'en'),
            ];
        })->values()->all();
    }

    protected function transformLesson(LearningLesson $lesson, string $locale): array
    {
        return [
            'id' => $lesson->id,
            'slug' => $lesson->slug,
            'title' => $lesson->getTranslationAsString('title', $locale, 'en'),
            'summary' => $lesson->getTranslationAsString('summary', $locale, 'en'),
            'duration_minutes' => $lesson->duration_minutes,
            'format' => $lesson->format,
            'category' => [
                'name' => $lesson->category?->getTranslationAsString('title', $locale, 'en') ?? 'Тренд',
                'icon' => $lesson->category?->icon ?: 'ri-fire-line',
            ],
            'content' => $lesson->getTranslation('content', $locale, 'en'),
        ];
    }

    protected function transformArticle(LearningArticle $article, string $locale): array
    {
        $action = $article->getTranslation('action', $locale, 'en');

        return [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->getTranslationAsString('title', $locale, 'en'),
            'summary' => $article->getTranslationAsString('summary', $locale, 'en'),
            'topic' => $article->topic,
            'reading_time_minutes' => $article->reading_time_minutes,
            'content' => $article->getTranslation('content', $locale, 'en'),
            'action' => is_array($action) ? $action : ['label' => $action],
        ];
    }

    protected function transformTemplate(LearningTemplate $template, string $locale): array
    {
        $typeLabels = [
            LearningTemplate::TYPE_TEXT => 'Текст',
            LearningTemplate::TYPE_VOICE => 'Голос',
            LearningTemplate::TYPE_STORY => 'Сторис',
            LearningTemplate::TYPE_CHECKLIST => 'Чек-лист',
        ];

        return [
            'id' => $template->id,
            'slug' => $template->slug,
            'type' => $template->type,
            'type_label' => $typeLabels[$template->type] ?? 'Сценарий',
            'title' => $template->getTranslationAsString('title', $locale, 'en'),
            'description' => $template->getTranslationAsString('description', $locale, 'en'),
            'content' => $template->getLocalizedContent($locale, 'en'),
        ];
    }
}
