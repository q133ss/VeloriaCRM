<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The backoffice door, and — with arguments — the rooms behind it.
 *
 * Four admin roles are declared on the model and shown in the interface, but
 * nothing ever read them: any admin could change anyone's plan, edit any user
 * and create a fresh super administrator. Support staff could promote
 * themselves and hand out paid tiers for free.
 *
 * Bare `admin.access` still means "any admin role" and guards the read-only
 * screens. Listing roles — `admin.access:super_admin` — narrows a route to them.
 */
class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'canAccessAdmin') || ! $user->canAccessAdmin()) {
            abort(403);
        }

        if ($roles !== [] && ! in_array($user->admin_role, $roles, true)) {
            abort(403, 'Это действие доступно другой роли в бэкофисе.');
        }

        return $next($request);
    }
}
