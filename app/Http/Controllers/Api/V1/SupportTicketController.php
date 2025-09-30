<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupportTicketMessageRequest;
use App\Http\Requests\SupportTicketStoreRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    public function index(): JsonResponse
    {
        $userId = $this->currentUserId();

        $tickets = SupportTicket::where('user_id', $userId)
            ->with(['messages' => fn ($query) => $query->latest('created_at')->limit(1)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (SupportTicket $ticket) => $this->transformTicketSummary($ticket))
            ->all();

        return response()->json([
            'data' => $tickets,
        ]);
    }

    public function store(SupportTicketStoreRequest $request): JsonResponse
    {
        $user = Auth::guard('sanctum')->user();

        if (! $user) {
            abort(403);
        }

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'subject' => $request->validated('subject'),
            'status' => SupportTicket::STATUS_WAITING,
            'last_message_at' => now(),
        ]);

        [$attachmentPath, $attachmentName] = $this->storeAttachment($request->file('attachment'), $user->id);

        $message = $ticket->messages()->create([
            'user_id' => $user->id,
            'sender_type' => 'user',
            'message' => $request->validated('message'),
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        $ticket->touchLastMessageAt($message->created_at);

        $ticket->refresh()->load('messages');

        return response()->json([
            'data' => $this->transformTicket($ticket, $user->id),
            'message' => __('help.support.form.success'),
        ], 201);
    }

    public function show(SupportTicket $ticket): JsonResponse
    {
        $userId = $this->currentUserId();
        $this->ensureTicketBelongsToCurrentUser($ticket, $userId);

        $ticket->load('messages');

        return response()->json([
            'data' => $this->transformTicket($ticket, $userId),
        ]);
    }

    public function reply(SupportTicketMessageRequest $request, SupportTicket $ticket): JsonResponse
    {
        $userId = $this->currentUserId();
        $this->ensureTicketBelongsToCurrentUser($ticket, $userId);

        [$attachmentPath, $attachmentName] = $this->storeAttachment($request->file('attachment'), $userId);

        $message = $ticket->messages()->create([
            'user_id' => $userId,
            'sender_type' => 'user',
            'message' => $request->validated('message'),
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        $ticket->status = SupportTicket::STATUS_WAITING;
        $ticket->touchLastMessageAt($message->created_at);
        $ticket->refresh()->load('messages');

        return response()->json([
            'data' => $this->transformTicket($ticket, $userId),
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

    protected function ensureTicketBelongsToCurrentUser(SupportTicket $ticket, int $userId): void
    {
        if ($ticket->user_id !== $userId) {
            abort(403);
        }
    }

    protected function storeAttachment(?UploadedFile $file, int $userId): array
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
            'support-attachments/' . $userId,
            $filename,
            'public'
        );

        return [$path, $originalName];
    }

    protected function transformTicketSummary(SupportTicket $ticket): array
    {
        $lastMessage = $ticket->messages->first();
        $lastTimestamp = $ticket->last_message_at ?? $ticket->updated_at;

        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'status_label' => __('help.tickets.statuses.' . $ticket->status),
            'updated_at' => optional($ticket->updated_at)->toIso8601String(),
            'last_message_at' => optional($lastTimestamp)->toIso8601String(),
            'last_message_preview' => $lastMessage ? Str::limit((string) $lastMessage->message, 120) : null,
        ];
    }

    protected function transformTicket(SupportTicket $ticket, int $userId): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'status_label' => __('help.tickets.statuses.' . $ticket->status),
            'created_at' => optional($ticket->created_at)->toIso8601String(),
            'updated_at' => optional($ticket->updated_at)->toIso8601String(),
            'last_message_at' => optional($ticket->last_message_at)->toIso8601String(),
            'messages' => $ticket->messages
                ->map(fn (SupportTicketMessage $message) => $this->transformMessage($message, $userId))
                ->values()
                ->all(),
        ];
    }

    protected function transformMessage(SupportTicketMessage $message, int $userId): array
    {
        $isUserMessage = $message->sender_type === 'user';

        return [
            'id' => $message->id,
            'from_support' => ! $isUserMessage,
            'from_current_user' => $isUserMessage && $message->user_id === $userId,
            'sender_type' => $message->sender_type,
            'body' => $message->message,
            'attachment_url' => $message->attachment_url,
            'attachment_name' => $message->attachment_name,
            'created_at' => optional($message->created_at)->toIso8601String(),
            'created_at_human' => optional($message->created_at)->diffForHumans(),
            'sender_label' => $isUserMessage
                ? __('help.tickets.messages.from_you')
                : __('help.tickets.messages.from_support'),
        ];
    }
}
