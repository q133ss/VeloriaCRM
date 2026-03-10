<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminAuditService
{
    public function log(
        int $userId,
        ?int $actorId,
        string $action,
        mixed $subject = null,
        ?Request $request = null,
        array $meta = []
    ): void {
        AuditLog::create([
            'user_id' => $userId,
            'actor_id' => $actorId,
            'action' => $action,
            'subject_id' => is_object($subject) && isset($subject->id) ? $subject->id : null,
            'subject_type' => is_object($subject) ? $subject::class : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }
}
