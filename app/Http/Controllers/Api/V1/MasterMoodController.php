<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMasterMoodRequest;
use App\Models\MasterMood;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class MasterMoodController extends Controller
{
    /**
     * Сохраняет ответ мастера на вопрос о самочувствии.
     */
    public function store(StoreMasterMoodRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $master = $request->user();

        // Используем персональную таймзону мастера (если задана) для корректной агрегации по дням.
        $timezone = $master?->timezone ?: config('app.timezone');
        $moodDate = isset($validated['date'])
            ? Carbon::parse($validated['date'], $timezone)->toDateString()
            : Carbon::now($timezone)->toDateString();

        $mood = $validated['mood'];

        // upsert гарантирует, что в день хранится только один ответ, который можно обновить.
        $record = MasterMood::updateOrCreate(
            [
                'user_id' => $master->id,
                'mood_date' => $moodDate,
            ],
            [
                'mood' => $mood,
                'mood_label' => MasterMood::labelFor($mood),
            ]
        );

        return response()->json([
            'data' => [
                'id' => $record->id,
                'mood' => $record->mood,
                'mood_label' => $record->mood_label,
                'date' => $record->mood_date->toDateString(),
            ],
        ], 201);
    }
}
