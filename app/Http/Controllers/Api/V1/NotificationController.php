<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications
    ) {
    }

    /**
     * Возвращаем список уведомлений пользователя с фильтрами и пагинацией.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $filters = [
            'unread' => $request->boolean('unread'),
            'search' => $request->input('search'),
            'per_page' => $request->integer('per_page'),
        ];

        $notifications = $this->notifications->listForUser($user->id, $filters);

        $data = collect($notifications->items())->map(function ($notification) {
            return [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'action_url' => $notification->action_url,
                'is_read' => (bool) $notification->is_read,
                'created_at' => $notification->created_at?->toIso8601String(),
            ];
        })->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
            ],
            // Возвращаем счётчик отдельно, чтобы в шапке можно было показать точное число непрочитанных.
            'unread_count' => $this->notifications->countUnread($user->id),
        ]);
    }

    /**
     * Помечаем указанные уведомления как прочитанные (если ids не переданы — все).
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['array', 'nullable'],
            'ids.*' => ['integer', 'min:1'],
        ]);

        $user = $request->user();

        // Если ids пустой массив или null — помечаем все непрочитанные.
        $updated = $this->notifications->markAsRead($user->id, $data['ids'] ?? null);

        return response()->json([
            'updated' => $updated,
        ]);
    }

    /**
     * Простая ручка для тестирования: создаём уведомление через API и триггерим Pusher.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'action_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $user = $request->user();
        // Позволяем явно указать адресата, иначе уведомление уйдёт текущему пользователю.
        $targetUserId = $data['user_id'] ?? $user->id;

        $notification = $this->notifications->send(
            $targetUserId,
            $data['title'],
            $data['message'],
            $data['action_url'] ?? null,
        );

        return response()->json([
            'data' => $notification,
        ], 201);
    }
}
