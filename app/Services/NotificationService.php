<?php

namespace App\Services;

use App\Events\UserNotificationCreated;
use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Создаёт и отсылает уведомление пользователю.
     */
    public function send(int $userId, string $title, string $message): Notification
    {
        return DB::transaction(function () use ($userId, $title, $message) {
            /** @var Notification $notification */
            // Сохраняем уведомление в рамках транзакции, чтобы не отправить лишний пуш при ошибке БД.
            $notification = Notification::query()->create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
            ]);

            // Отправляем событие в Pusher, чтобы фронтенд увидел уведомление мгновенно.
            broadcast(new UserNotificationCreated($notification))->toOthers();

            return $notification;
        });
    }

    /**
     * Возвращает пагинированный список уведомлений пользователя с фильтрами.
     */
    public function listForUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Notification::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        if (!empty($filters['unread'])) {
            $query->where('is_read', false);
        }

        if (!empty($filters['search'])) {
            // Фильтрация по заголовку и тексту для страницы /notifications.
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 10);
        $perPage = $perPage > 0 ? $perPage : 10;

        return $query->paginate($perPage);
    }

    /**
     * Помечает одно или несколько уведомлений как прочитанные.
     */
    public function markAsRead(int $userId, array $ids = null): int
    {
        $query = Notification::query()
            ->where('user_id', $userId)
            ->where('is_read', false);

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return $query->update([
            'is_read' => true,
        ]);
    }

    /**
     * Возвращает количество непрочитанных уведомлений пользователя.
     */
    public function countUnread(int $userId): int
    {
        // Быстрый подсчёт для бейджа в шапке приложения.
        return Notification::query()
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }
}
