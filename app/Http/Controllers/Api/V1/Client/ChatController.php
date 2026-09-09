<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatMessageRequest;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Client;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chat)
    {
    }

    public function show(): JsonResponse
    {
        /** @var Client $client */
        $client = request()->user();

        $thread = $this->chat->resolveThreadForClient($client);
        $thread->markReadByClient();
        $thread->load('messages');

        return response()->json([
            'data' => $this->transformThread($thread),
        ]);
    }

    public function send(ChatMessageRequest $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();

        $thread = $this->chat->resolveThreadForClient($client);
        $this->chat->sendFromClient($thread, $client, $request->validated('body'), $request->file('attachment'));

        $thread->refresh()->load('messages');

        return response()->json([
            'data' => $this->transformThread($thread),
        ], 201);
    }

    private function transformThread(ChatThread $thread): array
    {
        return [
            'id' => $thread->id,
            'messages' => $thread->messages
                ->map(fn (ChatMessage $message) => $this->transformMessage($message))
                ->values()
                ->all(),
        ];
    }

    private function transformMessage(ChatMessage $message): array
    {
        $fromClient = $message->sender_type === ChatThread::SENDER_CLIENT;

        return [
            'id' => $message->id,
            'from_me' => $fromClient,
            'sender_type' => $message->sender_type,
            'body' => $message->body,
            'attachment_url' => $message->attachment_url,
            'attachment_name' => $message->attachment_name,
            'created_at' => optional($message->created_at)->toIso8601String(),
        ];
    }
}
