<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChatMessage $message;

    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('chat-thread.' . $this->message->chat_thread_id);
    }

    public function broadcastAs(): string
    {
        return 'ChatMessageCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'chat_thread_id' => $this->message->chat_thread_id,
            'sender_type' => $this->message->sender_type,
            'body' => $this->message->body,
            'attachment_url' => $this->message->attachment_url,
            'attachment_name' => $this->message->attachment_name,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
