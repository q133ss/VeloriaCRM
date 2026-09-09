<?php

namespace App\Services;

use App\Events\ChatMessageCreated;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sits between the client-side and master-side chat controllers so the
 * "write the message, touch the thread, broadcast it, tell the other side"
 * sequence lives in one place instead of two — the same reason BookingController
 * hands off to AvailabilityService/BookingConflictService rather than each
 * caller reimplementing conflict detection.
 */
class ChatService
{
    public function __construct(
        private readonly NotificationService $masterNotifications,
        private readonly ClientNotificationService $clientNotifications,
    ) {
    }

    public function resolveThreadForClient(Client $client): ChatThread
    {
        return ChatThread::query()->firstOrCreate([
            'user_id' => $client->user_id,
            'client_id' => $client->id,
        ]);
    }

    public function resolveThreadForMasterClient(int $masterId, Client $client): ChatThread
    {
        return ChatThread::query()->firstOrCreate([
            'user_id' => $masterId,
            'client_id' => $client->id,
        ]);
    }

    public function sendFromClient(ChatThread $thread, Client $client, ?string $body, ?UploadedFile $attachment): ChatMessage
    {
        [$attachmentPath, $attachmentName] = $this->storeAttachment($attachment, $thread->id);

        $message = DB::transaction(function () use ($thread, $body, $attachmentPath, $attachmentName) {
            $created = $thread->messages()->create([
                'sender_type' => ChatThread::SENDER_CLIENT,
                'body' => $body,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
            ]);

            $thread->recordMessage(ChatThread::SENDER_CLIENT, $created->created_at);

            broadcast(new ChatMessageCreated($created))->toOthers();

            return $created;
        });

        $this->masterNotifications->send(
            $thread->user_id,
            __('chat.master_notification.title'),
            __('chat.master_notification.message', [
                'client' => $client->name ?: __('calendar.unnamed_client'),
                'preview' => $this->preview($body, $attachmentName),
            ]),
            '/messages',
        );

        return $message;
    }

    public function sendFromMaster(ChatThread $thread, User $master, ?string $body, ?UploadedFile $attachment): ChatMessage
    {
        [$attachmentPath, $attachmentName] = $this->storeAttachment($attachment, $thread->id);

        $message = DB::transaction(function () use ($thread, $master, $body, $attachmentPath, $attachmentName) {
            $created = $thread->messages()->create([
                'sender_type' => ChatThread::SENDER_MASTER,
                'user_id' => $master->id,
                'body' => $body,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
            ]);

            $thread->recordMessage(ChatThread::SENDER_MASTER, $created->created_at);

            broadcast(new ChatMessageCreated($created))->toOthers();

            return $created;
        });

        $thread->loadMissing('client');

        if ($thread->client) {
            $this->clientNotifications->notifyChatMessage(
                $thread->client,
                $master->name ?: 'Мастер',
                $this->preview($body, $attachmentName),
            );
        }

        return $message;
    }

    private function preview(?string $body, ?string $attachmentName): string
    {
        $trimmed = trim((string) $body);

        if ($trimmed !== '') {
            return Str::limit($trimmed, 120);
        }

        return $attachmentName ?: '';
    }

    private function storeAttachment(?UploadedFile $file, int $threadId): array
    {
        if (! $file) {
            return [null, null];
        }

        $originalName = $file->getClientOriginalName();
        $nameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME) ?: 'attachment';
        $extension = $file->getClientOriginalExtension();
        $slug = Str::slug($nameWithoutExtension);
        $slug = $slug !== '' ? $slug : 'attachment';
        $filename = now()->format('YmdHis') . '_' . $slug;
        if ($extension) {
            $filename .= '.' . strtolower($extension);
        }

        $path = $file->storeAs(
            'chat-attachments/' . $threadId,
            $filename,
            'public'
        );

        return [$path, $originalName];
    }
}
