<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LearningArticle;
use App\Services\UsefulDigestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsefulController extends Controller
{
    public function __construct(
        private readonly UsefulDigestService $usefulDigestService
    ) {
    }

    public function overview(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:64'],
            'unread' => ['nullable', 'boolean'],
        ]);

        return response()->json(
            $this->usefulDigestService->buildOverviewPayload($request->user(), app()->getLocale(), $filters)
        );
    }

    /**
     * «Я это уже читала?» — the answer is kept for her instead of being asked.
     */
    public function markRead(Request $request, LearningArticle $article): JsonResponse
    {
        $read = (bool) ($request->validate(['read' => ['required', 'boolean']])['read']);

        if ($read) {
            DB::table('useful_article_reads')->updateOrInsert(
                ['user_id' => $request->user()->id, 'learning_article_id' => $article->id],
                ['read_at' => now(), 'updated_at' => now(), 'created_at' => now()],
            );
        } else {
            DB::table('useful_article_reads')
                ->where('user_id', $request->user()->id)
                ->where('learning_article_id', $article->id)
                ->delete();
        }

        return response()->json(['data' => ['id' => $article->id, 'is_read' => $read]]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'channel' => ['required', 'string', 'in:platform,telegram,both'],
            'preferences' => ['nullable', 'string', 'max:2000'],
        ]);

        // Choosing Telegram without a linked account subscribed the master to a
        // digest that had nowhere to arrive, and nothing said so.
        if (in_array($data['channel'], ['telegram', 'both'], true) && ! $request->user()->telegram_id) {
            return response()->json([
                'error' => [
                    'code' => 'telegram_not_linked',
                    'message' => 'Телеграм ещё не подключён — привяжите его в интеграциях, иначе дайджесту некуда прийти.',
                ],
            ], 422);
        }

        return response()->json([
            'data' => $this->usefulDigestService->updatePreferences($request->user(), $data),
            'message' => 'Useful digest preferences updated.',
        ]);
    }

    public function sendTestDigest(Request $request): JsonResponse
    {
        $locale = app()->getLocale();

        return response()->json([
            'data' => $this->usefulDigestService->sendTestDigest($request->user(), $locale),
            'message' => 'Test useful digest sent.',
        ]);
    }
}
