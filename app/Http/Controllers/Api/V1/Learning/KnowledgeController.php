<?php

namespace App\Http\Controllers\Api\V1\Learning;

use App\Http\Controllers\Controller;
use App\Models\LearningArticle;
use App\Models\LearningTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KnowledgeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locale = app()->getLocale();
        $search = trim((string) $request->query('search', ''));
        $searchLower = mb_strtolower($search);

        $articles = LearningArticle::orderBy('id')->get();
        $templates = LearningTemplate::orderBy('position')->orderBy('id')->get();

        if ($search !== '') {
            $articles = $articles->filter(function (LearningArticle $article) use ($locale, $searchLower) {
                $haystack = implode(' ', array_filter([
                    $article->getTranslationAsString('title', $locale, 'en'),
                    $article->getTranslationAsString('summary', $locale, 'en'),
                    $article->topic,
                ]));

                return Str::contains(mb_strtolower($haystack), $searchLower);
            });

            $templates = $templates->filter(function (LearningTemplate $template) use ($locale, $searchLower) {
                $haystack = implode(' ', array_filter([
                    $template->getTranslationAsString('title', $locale, 'en'),
                    $template->getTranslationAsString('description', $locale, 'en'),
                ]));

                return Str::contains(mb_strtolower($haystack), $searchLower);
            });
        }

        $groupedTemplates = $templates->groupBy('type');

        return response()->json([
            'articles' => $articles->map(function (LearningArticle $article) use ($locale) {
                $action = $article->getTranslation('action', $locale, 'en');

                if (is_string($action)) {
                    $action = ['label' => $action];
                }

                return [
                    'id' => $article->id,
                    'slug' => $article->slug,
                    'title' => $article->getTranslationAsString('title', $locale, 'en'),
                    'summary' => $article->getTranslationAsString('summary', $locale, 'en'),
                    'reading_time_minutes' => $article->reading_time_minutes,
                    'topic' => $article->topic,
                    'content' => $article->getTranslation('content', $locale, 'en'),
                    'action' => $action,
                ];
            })->values()->all(),
            'templates' => $groupedTemplates->mapWithKeys(function ($templates, string $type) use ($locale) {
                return [
                    $type => $templates->map(function (LearningTemplate $template) use ($locale) {
                        $content = $template->getLocalizedContent($locale, 'en');

                        return [
                            'id' => $template->id,
                            'slug' => $template->slug,
                            'type' => $template->type,
                            'title' => $template->getTranslationAsString('title', $locale, 'en'),
                            'description' => $template->getTranslationAsString('description', $locale, 'en'),
                            'content' => $content,
                        ];
                    })->values()->all(),
                ];
            })->all(),
        ]);
    }
}
