<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $userId = $request->query('user_id');
        $actorId = $request->query('actor_id');
        $action = trim((string) $request->query('action', ''));

        $logs = AuditLog::query()
            ->with(['user:id,name,email', 'actor:id,name,email'])
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when($actorId, fn ($query) => $query->where('actor_id', $actorId))
            ->when($action !== '', fn ($query) => $query->where('action', 'like', '%' . $action . '%'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('action', 'like', '%' . $search . '%')
                        ->orWhere('subject_type', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('actor', function ($actorQuery) use ($search) {
                            $actorQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest('created_at')
            ->limit(250)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'meta' => $log->meta,
                'ip_address' => $log->ip_address,
                'created_at' => optional($log->created_at)->toIso8601String(),
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ] : null,
                'actor' => $log->actor ? [
                    'id' => $log->actor->id,
                    'name' => $log->actor->name,
                    'email' => $log->actor->email,
                ] : null,
            ])
            ->all();

        return response()->json([
            'data' => $logs,
        ]);
    }
}
