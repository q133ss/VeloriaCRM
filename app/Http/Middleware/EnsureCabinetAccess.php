<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The master's cabinet pages are a Blade shell that fills itself from the API,
 * so the API's 401 never stopped anyone from seeing the whole interface.
 * A guest, or a holder of a client-app token, gets a plain 403 here instead.
 */
class EnsureCabinetAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum') ?? $request->user();

        abort_unless($user instanceof User, 403);

        return $next($request);
    }
}
