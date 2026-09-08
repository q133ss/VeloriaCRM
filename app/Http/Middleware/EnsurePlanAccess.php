<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards a whole route group behind a paid tier.
 *
 * Tier checks used to live inside controllers, one private copy each, and the
 * marketing endpoints simply never got one: campaigns and promotions answered
 * every request on the free plan. A group-level guard cannot be forgotten by the
 * next endpoint added to the group, which is the whole point of putting it here.
 *
 * Usage: `->middleware('plan:pro')` or `'plan:elite'`.
 */
class EnsurePlanAccess
{
    public function handle(Request $request, Closure $next, string $plan = 'pro'): Response
    {
        $user = $request->user();

        $allowed = $user !== null && match ($plan) {
            'elite' => $user->hasEliteAccess(),
            default => $user->hasProAccess(),
        };

        if ($allowed) {
            return $next($request);
        }

        // A page, not an API call: sending the master to the plan screen beats
        // rendering the section with zeroes in every counter and a «не удалось
        // загрузить» in the middle, which is what /landings did on Lite.
        if (! $request->expectsJson()) {
            return redirect('/subscription')->with(
                'plan_required',
                trans('subscription.errors.plan_required', [
                    'plan' => $plan === 'elite' ? 'Elite' : 'Pro',
                ]),
            );
        }

        return response()->json([
            'error' => [
                'code' => 'feature_unavailable',
                'message' => trans('subscription.errors.plan_required', [
                    'plan' => $plan === 'elite' ? 'Elite' : 'Pro',
                ]),
                'required_plan' => $plan,
                'upgrade_url' => url('/subscription'),
            ],
        ], 403);
    }
}
