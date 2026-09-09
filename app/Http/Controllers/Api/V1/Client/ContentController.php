<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\MasterPost;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    public function posts(): JsonResponse
    {
        /** @var Client $client */
        $client = request()->user();
        $masterId = (int) $client->user_id;

        $posts = MasterPost::forUser($masterId)
            ->published()
            ->orderByDesc('published_at')
            ->get(['id', 'title', 'body', 'image_url', 'published_at']);

        return response()->json([
            'data' => [
                'posts' => $posts,
            ],
        ]);
    }

    /**
     * Feeds both the news feed's promo callouts and Phase 9's referral screen —
     * no new model, just the same active-promotion scope the CRM's own
     * marketing section reads.
     */
    public function promotions(): JsonResponse
    {
        /** @var Client $client */
        $client = request()->user();
        $masterId = (int) $client->user_id;

        $promotions = Promotion::forUser($masterId)
            ->active()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'type', 'percent', 'gift_description', 'promo_code', 'ends_at']);

        return response()->json([
            'data' => [
                'promotions' => $promotions,
            ],
        ]);
    }
}
