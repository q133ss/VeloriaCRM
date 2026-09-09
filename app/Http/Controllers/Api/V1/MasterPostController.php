<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterPostFormRequest;
use App\Models\MasterPost;
use App\Services\ClientNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MasterPostController extends Controller
{
    public function __construct(private readonly ClientNotificationService $clientNotifications)
    {
    }

    public function index(): JsonResponse
    {
        $posts = MasterPost::forUser($this->currentUserId())
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $posts]);
    }

    public function store(MasterPostFormRequest $request): JsonResponse
    {
        $userId = $this->currentUserId();
        $validated = $request->validated();
        $isPublished = Arr::get($validated, 'is_published', true);

        $post = MasterPost::create([
            'user_id' => $userId,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'image_url' => Arr::get($validated, 'image_url'),
            'is_published' => $isPublished,
            'published_at' => Arr::get($validated, 'published_at') ? Carbon::parse($validated['published_at']) : ($isPublished ? Carbon::now() : null),
        ]);

        if ($post->is_published) {
            $this->clientNotifications->notifyMasterPostPublished($post);
        }

        return response()->json([
            'message' => __('master_posts.notifications.created'),
            'data' => $post,
        ], 201);
    }

    public function update(MasterPostFormRequest $request, MasterPost $masterPost): JsonResponse
    {
        $this->ensureBelongsToUser($masterPost);
        $validated = $request->validated();
        $wasPublished = $masterPost->is_published;
        $isPublished = Arr::get($validated, 'is_published', $masterPost->is_published);

        $masterPost->update([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'image_url' => Arr::get($validated, 'image_url', $masterPost->image_url),
            'is_published' => $isPublished,
            'published_at' => Arr::get($validated, 'published_at')
                ? Carbon::parse($validated['published_at'])
                : ($isPublished && ! $masterPost->published_at ? Carbon::now() : $masterPost->published_at),
        ]);

        if (! $wasPublished && $masterPost->is_published) {
            $this->clientNotifications->notifyMasterPostPublished($masterPost);
        }

        return response()->json([
            'message' => __('master_posts.notifications.updated'),
            'data' => $masterPost,
        ]);
    }

    public function destroy(MasterPost $masterPost): JsonResponse
    {
        $this->ensureBelongsToUser($masterPost);
        $masterPost->delete();

        return response()->json([
            'message' => __('master_posts.notifications.deleted'),
        ]);
    }

    private function ensureBelongsToUser(MasterPost $masterPost): void
    {
        if ($masterPost->user_id !== $this->currentUserId()) {
            abort(404);
        }
    }

    protected function currentUserId(): int
    {
        $userId = Auth::guard('sanctum')->id();

        if (! $userId) {
            abort(403);
        }

        return $userId;
    }
}
