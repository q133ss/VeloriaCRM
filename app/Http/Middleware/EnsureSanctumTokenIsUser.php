<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class EnsureSanctumTokenIsUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => __('auth.unauthorized'),
                ],
            ], 401);
        }

        return $next($request);
    }
}

