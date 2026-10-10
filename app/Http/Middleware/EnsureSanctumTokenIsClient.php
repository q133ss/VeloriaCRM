<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;

class EnsureSanctumTokenIsClient
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (! $user instanceof Client) {
            return response()->json([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => __('client_portal.auth.unauthorized'),
                ],
            ], 401);
        }

        $this->touchLastSeen($user);

        return $next($request);
    }

    /**
     * Every authenticated client-portal call is proof the app is actually in
     * someone's hands, unlike `client_user_id` which just links the booking
     * identity and can be set long before anyone opens the app. Chat polls this
     * route every few seconds while open, so the write is throttled to once a
     * minute rather than on every request.
     */
    private function touchLastSeen(Client $client): void
    {
        if ($client->last_seen_at !== null && $client->last_seen_at->gt(now()->subMinute())) {
            return;
        }

        $client->forceFill(['last_seen_at' => now()])->saveQuietly();
    }
}

