<?php

namespace App\Http\Controllers\Api\V1\Learning;

use App\Http\Controllers\Controller;
use App\Models\LearningCategory;
use App\Models\LearningLesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locale = app()->getLocale();
        $categorySlug = $request->query('category');

        $categories = LearningCategory::withCount('lessons')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $activeCategory = $categorySlug
            ? $categories->firstWhere('slug', $categorySlug)
            : null;

        $lessonsQuery = LearningLesson::with('category')
            ->orderBy('position')
            ->orderBy('id');

        if ($activeCategory) {
            $lessonsQuery->where('learning_category_id', $activeCategory->id);
        } elseif ($categorySlug) {
            $lessonsQuery->whereRaw('1 = 0');
        }

        $lessons = $lessonsQuery->get();

        return response()->json([
            'categories' => $categories->map(function (LearningCategory $category) use ($locale, $activeCategory) {
                return [
                    'slug' => $category->slug,
                    'name' => $category->getTranslationAsString('title', $locale, 'en'),
                    'description' => $category->getTranslationAsString('description', $locale, 'en'),
                    'icon' => $category->icon,
                    'lesson_count' => $category->lessons_count,
                    'active' => $activeCategory && $activeCategory->id === $category->id,
                ];
            })->values()->all(),
            'lessons' => $lessons->map(function (LearningLesson $lesson) use ($locale) {
                return [
                    'id' => $lesson->id,
                    'slug' => $lesson->slug,
                    'title' => $lesson->getTranslationAsString('title', $locale, 'en'),
                    'summary' => $lesson->getTranslationAsString('summary', $locale, 'en'),
                    'duration_minutes' => $lesson->duration_minutes,
                    'format' => $lesson->format,
                    'category' => $lesson->category ? [
                        'slug' => $lesson->category->slug,
                        'name' => $lesson->category->getTranslationAsString('title', $locale, 'en'),
                    ] : null,
                    'content' => $lesson->getTranslation('content', $locale, 'en'),
                ];
            })->values()->all(),
        ]);
    }
}
