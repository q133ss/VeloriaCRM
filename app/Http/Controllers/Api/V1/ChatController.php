<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatMessageRequest;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Client;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chat)
    {
    }

    /**
     * Cheap, dashboard-wide poll target for the sidebar badge — the full
     * index() above pulls client names and last-message previews for every
     * thread, more than a badge on every page load needs.
     */
    public function unreadCount(): JsonResponse
    {
        $count = ChatThread::unreadForMaster($this->currentUserId())->count();

        return response()->json(['unread_count' => $count]);
    }

    public function index(): JsonResponse
    {
        $threads = ChatThread::forMaster($this->currentUserId())
            ->with(['client', 'messages' => fn ($query) => $query->latest('created_at')->limit(1)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (ChatThread $thread) => $this->transformThreadSummary($thread))
            ->all();

        return response()->json(['data' => $threads]);
    }

    /**
     * Opens (or, from a client-detail page, starts) a conversation with one
     * of the master's own clients — the master-side counterpart of a client's
     * first message auto-creating the thread.
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $this->currentUserId();
        $validated = $request->validate([
            'client_id' => ['required', 'integer'],
        ]);

        $client = Client::query()
            ->where('user_id', $userId)
            ->findOrFail((int) $validated['client_id']);

        $thread = $this->chat->resolveThreadForMasterClient($userId, $client);
        $thread->load('messages');

        return response()->json([
            'data' => $this->transformThread($thread),
        ], 201);
    }

    public function show(ChatThread $thread): JsonResponse
    {
        $this->ensureThreadBelongsToCurrentUser($thread);

        $thread->markReadByMaster();
        $thread->load(['messages', 'client']);

        return response()->json([
            'data' => $this->transformThread($thread),
        ]);
    }

    public function reply(ChatMessageRequest $request, ChatThread $thread): JsonResponse
    {
        $this->ensureThreadBelongsToCurrentUser($thread);

        /** @var \App\Models\User $master */
        $master = Auth::guard('sanctum')->user();

        $this->chat->sendFromMaster($thread, $master, $request->validated('body'), $request->file('attachment'));

        $thread->refresh()->load(['messages', 'client']);

        return response()->json([
            'data' => $this->transformThread($thread),
        ]);
    }

    protected function currentUserId(): int
    {
        $userId = Auth::guard('sanctum')->id();

        if (! $userId) {
            abort(403);
        }

        return $userId;
    }

    private function ensureThreadBelongsToCurrentUser(ChatThread $thread): void
    {
        if ($thread->user_id !== $this->currentUserId()) {
            abort(404);
        }
    }

    private function transformThreadSummary(ChatThread $thread): array
    {
        $lastMessage = $thread->messages->first();

        return [
            'id' => $thread->id,
            'client_id' => $thread->client_id,
            'client_name' => $thread->client?->name,
            'last_message_at' => optional($thread->last_message_at)->toIso8601String(),
            'last_message_preview' => $lastMessage ? Str::limit((string) $lastMessage->body, 120) : null,
            'unread' => $thread->isUnreadForMaster(),
        ];
    }

    private function transformThread(ChatThread $thread): array
    {
        return [
            'id' => $thread->id,
            'client_id' => $thread->client_id,
            'client_name' => $thread->client?->name,
            'messages' => $thread->messages
                ->map(fn (ChatMessage $message) => $this->transformMessage($message))
                ->values()
                ->all(),
        ];
    }

    private function transformMessage(ChatMessage $message): array
    {
        $fromMaster = $message->sender_type === ChatThread::SENDER_MASTER;

        return [
            'id' => $message->id,
            'from_me' => $fromMaster,
            'sender_type' => $message->sender_type,
            'body' => $message->body,
            'attachment_url' => $message->attachment_url,
            'attachment_name' => $message->attachment_name,
            'created_at' => optional($message->created_at)->toIso8601String(),
        ];
    }
}
