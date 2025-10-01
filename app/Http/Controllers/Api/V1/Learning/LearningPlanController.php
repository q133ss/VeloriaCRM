<?php

namespace App\Http\Controllers\Api\V1\Learning;

use App\Http\Controllers\Controller;
use App\Models\LearningRecommendation;
use App\Models\LearningTask;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class LearningPlanController extends Controller
{
    public function __construct(private readonly AIService $ai)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $locale = app()->getLocale();

        $insights = LearningRecommendation::forUser($user)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        $tasks = LearningTask::forUser($user)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $weekStart = Carbon::now($user->timezone ?? config('app.timezone'))->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $aiSummary = $this->ai->summarizeLearningPlan($user, $insights, $locale);

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', LearningTask::STATUS_COMPLETED)->count();
        $progressValue = $totalTasks > 0 ? $completedTasks / $totalTasks : 0;

        return response()->json([
            'period' => [
                'start' => $weekStart->toDateString(),
                'end' => $weekEnd->toDateString(),
                'label' => $this->formatDateRangeLabel($weekStart, $weekEnd, $locale),
            ],
            'ai' => $aiSummary,
            'insights' => $insights->map(fn (LearningRecommendation $insight) => $this->transformInsight($insight, $locale))->values()->all(),
            'plan' => [
                'progress' => [
                    'value' => round($progressValue, 2),
                    'percent' => (int) round($progressValue * 100),
                    'completed' => $completedTasks,
                    'total' => $totalTasks,
                ],
                'tasks' => $tasks->map(fn (LearningTask $task) => $this->transformTask($task, $locale))->values()->all(),
            ],
        ]);
    }

    public function updateTask(Request $request, LearningTask $task): JsonResponse
    {
        $user = $request->user();

        if ($task->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'completed' => ['sometimes', 'boolean'],
            'progress_current' => ['sometimes', 'integer', 'min:0'],
        ]);

        if (empty($data)) {
            throw ValidationException::withMessages([
                'completed' => [__('validation.required_without', ['attribute' => 'completed', 'values' => 'progress_current'])],
            ]);
        }

        if (array_key_exists('progress_current', $data)) {
            $task->updateProgress((int) $data['progress_current']);
        }

        if (array_key_exists('completed', $data)) {
            if ($data['completed']) {
                $task->markCompleted();
            } else {
                $progressCurrent = $task->progress_current;
                if ($task->progress_target > 0 && $progressCurrent >= $task->progress_target) {
                    $progressCurrent = max(0, $task->progress_target - 1);
                }
                $task->reopen($progressCurrent);
            }
        }

        $task->refresh();

        return response()->json([
            'task' => $this->transformTask($task, app()->getLocale()),
        ]);
    }

    protected function formatDateRangeLabel(Carbon $start, Carbon $end, string $locale): string
    {
        $startDate = $start->copy()->locale($locale);
        $endDate = $end->copy()->locale($locale);

        if ($startDate->isSameMonth($endDate)) {
            return sprintf(
                '%s — %s',
                $startDate->isoFormat('D'),
                $endDate->isoFormat('D MMMM')
            );
        }

        return sprintf(
            '%s — %s',
            $startDate->isoFormat('D MMMM'),
            $endDate->isoFormat('D MMMM')
        );
    }

    protected function transformInsight(LearningRecommendation $insight, string $locale): array
    {
        return [
            'id' => $insight->id,
            'type' => $insight->type,
            'title' => $insight->getTranslationAsString('title', $locale, 'en'),
            'description' => $insight->getTranslationAsString('description', $locale, 'en'),
            'impact' => $insight->getTranslationAsString('impact_text', $locale, 'en'),
            'action' => $insight->getTranslationAsString('action', $locale, 'en'),
            'confidence' => $insight->confidence,
            'meta' => $insight->meta ?? [],
        ];
    }

    protected function transformTask(LearningTask $task, string $locale): array
    {
        $unit = $task->getTranslation('progress_unit', $locale, 'en');
        $percent = $task->progress_target > 0
            ? (int) min(100, round($task->progress_current / $task->progress_target * 100))
            : null;

        return [
            'id' => $task->id,
            'title' => $task->getTranslationAsString('title', $locale, 'en'),
            'description' => $task->getTranslationAsString('description', $locale, 'en'),
            'status' => $task->status,
            'due_on' => $task->due_on?->toDateString(),
            'due_label' => $task->due_on?->locale($locale)->isoFormat('D MMMM'),
            'progress' => [
                'current' => $task->progress_current,
                'target' => $task->progress_target,
                'unit' => is_string($unit) ? $unit : null,
                'percent' => $percent,
            ],
            'completed_at' => $task->completed_at?->toIso8601String(),
            'meta' => $task->meta ?? [],
        ];
    }
}
