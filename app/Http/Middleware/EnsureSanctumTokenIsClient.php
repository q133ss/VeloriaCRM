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

        return $next($request);
    }
}

