<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Созданное уведомление, которое мы передаём на фронтенд.
     */
    public Notification $notification;

    /**
     * Подключаем модель уведомления в событие.
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Канал, в который будет отправлено событие (private, т.к. уведомления персональные).
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('notifications.' . $this->notification->user_id);
    }

    /**
     * Алиас события, чтобы в JavaScript слушать короткое имя.
     */
    public function broadcastAs(): string
    {
        return 'UserNotificationCreated';
    }

    /**
     * Данные, которые попадут в Pusher.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'is_read' => (bool) $this->notification->is_read,
            'created_at' => $this->notification->created_at->toIso8601String(),
        ];
    }
}
