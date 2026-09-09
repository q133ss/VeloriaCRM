<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var Client $client */
        $client = request()->user();

        $notifications = ClientNotification::query()
            ->where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'message', 'action_url', 'is_read', 'created_at']);

        return response()->json([
            'data' => [
                'notifications' => $notifications,
            ],
        ]);
    }

    public function markAsRead(Request $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();

        $data = $request->validate([
            'ids' => ['array', 'nullable'],
            'ids.*' => ['integer', 'min:1'],
        ]);

        $query = ClientNotification::query()
            ->where('client_id', $client->id)
            ->where('is_read', false);

        if (! empty($data['ids'])) {
            $query->whereIn('id', $data['ids']);
        }

        $updated = $query->update(['is_read' => true]);

        return response()->json([
            'updated' => $updated,
        ]);
    }
}
