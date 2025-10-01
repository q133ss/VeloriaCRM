<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SubscriptionTransaction;
use App\Services\YooKassaService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class SubscriptionController extends Controller
{
    public function __construct(private readonly YooKassaService $yooKassa)
    {
    }

    public function show(Request $request): View
    {
        $user = $request->user();

        $currentPlan = $user->plans()->orderByDesc('plan_user.created_at')->first();
        $activePlan = null;
        $currentPlanEndsAt = $currentPlan?->pivot?->ends_at;

        if ($currentPlan && (! $currentPlanEndsAt || Carbon::parse($currentPlanEndsAt)->isFuture())) {
            $activePlan = $currentPlan;
        }

        $plans = Plan::orderBy('price')->get();
        $planDetails = Collection::make(trans('subscription.plans'));
        $comparison = Collection::make(trans('subscription.comparison'));
        $currencySymbol = trans('subscription.currency');
        $billingPeriod = trans('subscription.billing_period');

        $transactions = $user->subscriptionTransactions()
            ->with('plan')
            ->latest()
            ->take(10)
            ->get();

        return view('subscription.index', [
            'user' => $user,
            'currentPlan' => $currentPlan,
            'activePlan' => $activePlan,
            'plans' => $plans,
            'planDetails' => $planDetails,
            'comparison' => $comparison,
            'transactions' => $transactions,
            'yooKassaEnabled' => $this->yooKassa->enabled(),
            'currencySymbol' => $currencySymbol,
            'billingPeriod' => $billingPeriod,
        ]);
    }

    public function upgrade(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ]);

        $user = $request->user();
        $plan = Plan::findOrFail($validated['plan_id']);
        $currentPlan = $user->plans()->orderByDesc('plan_user.created_at')->first();

        if ($currentPlan && $plan->price <= $currentPlan->price) {
            return back()->with('error', __('subscription.alerts.upgrade_error'));
        }

        if ($plan->price <= 0) {
            $user->plans()->syncWithoutDetaching([$plan->getKey() => ['ends_at' => null]]);
            $user->plans()->updateExistingPivot($plan->getKey(), ['ends_at' => null]);

            return redirect()->route('subscription')->with('status', __('subscription.current_plan.free_plan'));
        }

        $transaction = SubscriptionTransaction::create([
            'user_id' => $user->getKey(),
            'plan_id' => $plan->getKey(),
            'amount' => $plan->price,
            'currency' => config('services.yookassa.currency', 'RUB'),
            'status' => 'pending',
            'metadata' => [
                'initiated_at' => Carbon::now()->toIso8601String(),
                'initiated_by' => 'upgrade',
            ],
        ]);

        try {
            $payment = $this->yooKassa->createPayment($user, $plan, route('subscription'));
        } catch (Throwable $exception) {
            Log::error('Failed to create YooKassa payment', [
                'user_id' => $user->getKey(),
                'plan_id' => $plan->getKey(),
                'exception' => $exception->getMessage(),
            ]);

            $transaction->update([
                'status' => 'failed',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'error' => $exception->getMessage(),
                ]),
            ]);

            return back()->with('error', __('subscription.alerts.upgrade_error'));
        }

        $transaction->update([
            'payment_id' => $payment['id'] ?? null,
            'status' => $payment['status'] ?? 'pending',
            'metadata' => array_merge($transaction->metadata ?? [], Arr::only($payment, ['confirmation_url'])),
        ]);

        $confirmationUrl = $payment['confirmation_url'] ?? null;
        if ($confirmationUrl) {
            return redirect()->away($confirmationUrl);
        }

        return redirect()->route('subscription')->with('status', __('subscription.subtitle'));
    }

    public function cancel(Request $request): RedirectResponse
    {
        $user = $request->user();
        $currentPlan = $user->plans()->orderByDesc('plan_user.created_at')->first();
        if (! $currentPlan) {
            return back();
        }

        $now = Carbon::now();
        $user->plans()->updateExistingPivot($currentPlan->getKey(), [
            'ends_at' => $now,
        ]);

        return back()->with('status', __('subscription.alerts.cancel_success', [
            'date' => $now->locale(app()->getLocale())->translatedFormat('d MMMM YYYY'),
        ]));
    }
}
