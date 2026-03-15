<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminUsefulPostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $articles = LearningArticle::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('slug', 'like', '%' . $search . '%')
                        ->orWhere('topic', 'like', '%' . $search . '%')
                        ->orWhere('title->ru', 'like', '%' . $search . '%')
                        ->orWhere('title->en', 'like', '%' . $search . '%');
                });
            })
            ->when($status === 'published', fn ($query) => $query->where('is_published', true))
            ->when($status === 'draft', fn ($query) => $query->where('is_published', false))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (LearningArticle $article) => $this->transformSummary($article))
            ->all();

        return response()->json([
            'data' => $articles,
        ]);
    }

    public function show(LearningArticle $article): JsonResponse
    {
        return response()->json([
            'data' => $this->transformDetail($article),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $article = LearningArticle::create($validated);

        return response()->json([
            'data' => $this->transformDetail($article),
        ], 201);
    }

    public function update(Request $request, LearningArticle $article): JsonResponse
    {
        $validated = $this->validatePayload($request, $article);
        $article->update($validated);

        return response()->json([
            'data' => $this->transformDetail($article->fresh()),
        ]);
    }

    public function destroy(LearningArticle $article): JsonResponse
    {
        $article->delete();

        return response()->json([
            'message' => 'Useful post deleted.',
        ]);
    }

    protected function validatePayload(Request $request, ?LearningArticle $article = null): array
    {
        $slugRule = Rule::unique('learning_articles', 'slug');
        if ($article) {
            $slugRule = $slugRule->ignore($article->id);
        }

        $validated = $request->validate([
            'slug' => ['nullable', 'string', 'max:255', $slugRule],
            'topic' => ['nullable', 'string', 'max:100'],
            'reading_time_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'title' => ['required', 'array'],
            'title.ru' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'array'],
            'summary.ru' => ['nullable', 'string', 'max:1000'],
            'summary.en' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'array'],
            'content.ru' => ['nullable'],
            'content.en' => ['nullable'],
            'action' => ['nullable', 'array'],
            'action.ru' => ['nullable', 'array'],
            'action.ru.label' => ['nullable', 'string', 'max:255'],
            'action.ru.url' => ['nullable', 'url', 'max:2048'],
            'action.en' => ['nullable', 'array'],
            'action.en.label' => ['nullable', 'string', 'max:255'],
            'action.en.url' => ['nullable', 'url', 'max:2048'],
        ]);

        $title = $this->normalizeTranslation($validated['title'] ?? []);
        $summary = $this->normalizeTranslation($validated['summary'] ?? []);
        $content = $this->normalizeTranslationContent($validated['content'] ?? []);
        $action = $this->normalizeActionTranslation($validated['action'] ?? []);

        return [
            'slug' => $validated['slug'] ?: Str::slug($title['ru'] ?? Str::random(8)),
            'topic' => $validated['topic'] ?? null,
            'reading_time_minutes' => (int) $validated['reading_time_minutes'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'source_url' => $validated['source_url'] ?? null,
            'published_at' => $validated['published_at'] ?? now(),
            'is_published' => (bool) $validated['is_published'],
            'is_featured' => (bool) $validated['is_featured'],
            'title' => $title,
            'summary' => $summary,
            'content' => $content,
            'action' => $action,
        ];
    }

    protected function normalizeTranslation(array $values): array
    {
        $ru = trim((string) ($values['ru'] ?? ''));
        $en = trim((string) ($values['en'] ?? ''));

        return [
            'ru' => $ru,
            'en' => $en !== '' ? $en : $ru,
        ];
    }

    protected function normalizeTranslationContent(array $values): array
    {
        return [
            'ru' => $this->normalizeMixedContent($values['ru'] ?? null),
            'en' => $this->normalizeMixedContent($values['en'] ?? ($values['ru'] ?? null)),
        ];
    }

    protected function normalizeMixedContent(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        try {
            return json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $trimmed;
        }
    }

    protected function normalizeActionTranslation(array $values): array
    {
        $ru = Arr::get($values, 'ru', []);
        $en = Arr::get($values, 'en', []);

        return [
            'ru' => [
                'label' => trim((string) ($ru['label'] ?? 'Открыть материал')),
                'url' => $ru['url'] ?? null,
            ],
            'en' => [
                'label' => trim((string) ($en['label'] ?? ($ru['label'] ?? 'Open material'))),
                'url' => $en['url'] ?? ($ru['url'] ?? null),
            ],
        ];
    }

    protected function transformSummary(LearningArticle $article): array
    {
        return [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->getTranslationAsString('title', 'ru', 'en'),
            'topic' => $article->topic,
            'is_published' => (bool) $article->is_published,
            'is_featured' => (bool) $article->is_featured,
            'published_at' => optional($article->published_at)->toIso8601String(),
            'sort_order' => (int) $article->sort_order,
            'reading_time_minutes' => (int) $article->reading_time_minutes,
        ];
    }

    protected function transformDetail(LearningArticle $article): array
    {
        return [
            'id' => $article->id,
            'slug' => $article->slug,
            'topic' => $article->topic,
            'reading_time_minutes' => (int) $article->reading_time_minutes,
            'sort_order' => (int) $article->sort_order,
            'source_url' => $article->source_url,
            'published_at' => optional($article->published_at)->toIso8601String(),
            'is_published' => (bool) $article->is_published,
            'is_featured' => (bool) $article->is_featured,
            'title' => $article->title ?? ['ru' => '', 'en' => ''],
            'summary' => $article->summary ?? ['ru' => '', 'en' => ''],
            'content' => $article->content ?? ['ru' => null, 'en' => null],
            'action' => $article->action ?? [
                'ru' => ['label' => 'Открыть материал', 'url' => null],
                'en' => ['label' => 'Open material', 'url' => null],
            ],
        ];
    }
}
