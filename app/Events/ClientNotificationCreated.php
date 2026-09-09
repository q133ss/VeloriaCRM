<?php

namespace App\Events;

use App\Models\ClientNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientNotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ClientNotification $notification;

    public function __construct(ClientNotification $notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('client-notifications.' . $this->notification->client_id);
    }

    public function broadcastAs(): string
    {
        return 'ClientNotificationCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'action_url' => $this->notification->action_url,
            'is_read' => (bool) $this->notification->is_read,
            'created_at' => $this->notification->created_at->toIso8601String(),
        ];
    }
}
