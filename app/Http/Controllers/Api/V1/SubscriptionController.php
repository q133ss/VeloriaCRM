<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\SubscriptionTransaction;
use App\Services\YooKassaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class SubscriptionController extends Controller
{
    public function __construct(private readonly YooKassaService $yooKassa)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $currentPlan = $user->plans()->orderByDesc('plan_user.created_at')->first();
        $currentPlanEndsAt = $currentPlan?->pivot?->ends_at;

        if ($currentPlanEndsAt && ! $currentPlanEndsAt instanceof Carbon) {
            $currentPlanEndsAt = Carbon::parse($currentPlanEndsAt);
        }

        $activePlan = null;
        if ($currentPlan && (! $currentPlanEndsAt || $currentPlanEndsAt->isFuture())) {
            $activePlan = $currentPlan;
        }

        $plans = Plan::orderBy('price')->get();
        $planDetails = Collection::make(trans('subscription.plans'));
        $comparison = Collection::make(trans('subscription.comparison'));

        $currencySymbol = trans('subscription.currency');
        $billingPeriod = trans('subscription.billing_period');

        $statusLabels = trans('subscription.statuses');
        $statusBadges = [
            'succeeded' => 'bg-label-success',
            'pending' => 'bg-label-warning',
            'waiting_for_capture' => 'bg-label-info',
            'canceled' => 'bg-label-secondary',
            'cancelled' => 'bg-label-secondary',
            'failed' => 'bg-label-danger',
        ];

        $transactions = $user->subscriptionTransactions()
            ->with('plan')
            ->latest()
            ->take(10)
            ->get()
            ->map(function (SubscriptionTransaction $transaction) use ($planDetails, $statusLabels, $statusBadges, $currencySymbol) {
                $statusKey = strtolower($transaction->status ?? 'unknown');
                $statusLabel = Arr::get($statusLabels, $statusKey, Arr::get($statusLabels, 'unknown'));
                $statusBadge = Arr::get($statusBadges, $statusKey, 'bg-label-secondary');
                $planDetailsEntry = $transaction->plan ? $planDetails->get($transaction->plan->slug, []) : [];

                return [
                    'id' => $transaction->getKey(),
                    'created_at' => $transaction->created_at?->toIso8601String(),
                    'created_at_formatted' => $transaction->created_at?->locale(app()->getLocale())->isoFormat('D MMM YYYY, HH:mm'),
                    'plan_name' => Arr::get($planDetailsEntry, 'name', $transaction->plan?->name),
                    'amount' => (float) $transaction->amount,
                    'amount_display' => $currencySymbol . number_format((float) $transaction->amount, 2, '.', ' '),
                    'status' => $statusKey,
                    'status_label' => $statusLabel,
                    'status_badge' => $statusBadge,
                    'payment_id' => $transaction->payment_id,
                ];
            })
            ->values();

        $currentPlanData = null;
        if ($currentPlan) {
            $planDetailsEntry = $planDetails->get($currentPlan->slug, []);
            $currentPlanData = [
                'id' => $currentPlan->getKey(),
                'slug' => $currentPlan->slug,
                'name' => Arr::get($planDetailsEntry, 'name', ucfirst($currentPlan->slug)),
                'tagline' => Arr::get($planDetailsEntry, 'tagline'),
                'badge' => Arr::get($planDetailsEntry, 'badge'),
                'price' => (float) $currentPlan->price,
                'price_display' => $currentPlan->price > 0
                    ? $currencySymbol . number_format($currentPlan->price, 0, '', ' ')
                    : null,
                'ends_at' => $currentPlanEndsAt?->toIso8601String(),
                'active_until_label' => $currentPlanEndsAt
                    ? trans('subscription.current_plan.active_until', [
                        'date' => $currentPlanEndsAt->copy()->locale(app()->getLocale())->isoFormat('D MMMM YYYY'),
                    ])
                    : null,
                'is_active' => (bool) $activePlan,
                'is_free' => $currentPlan->price <= 0,
            ];
        }

        $planResponse = $plans->map(function (Plan $plan) use ($currentPlan, $planDetails, $currencySymbol) {
            $details = $planDetails->get($plan->slug, []);
            $isCurrent = $currentPlan && $plan->getKey() === $currentPlan->getKey();
            $currentPrice = $currentPlan?->price ?? null;

            return [
                'id' => $plan->getKey(),
                'slug' => $plan->slug,
                'name' => Arr::get($details, 'name', ucfirst($plan->slug)),
                'tagline' => Arr::get($details, 'tagline'),
                'badge' => Arr::get($details, 'badge'),
                'features' => Arr::get($details, 'features', []),
                'price' => (float) $plan->price,
                'price_display' => $plan->price > 0
                    ? $currencySymbol . number_format($plan->price, 0, '', ' ')
                    : null,
                'is_current' => $isCurrent,
                'is_free' => $plan->price <= 0,
                'is_upgrade' => $currentPrice === null ? true : $plan->price > $currentPrice,
            ];
        })->values();

        $comparisonResponse = $comparison->map(function (array $row) {
            return [
                'feature' => Arr::get($row, 'feature'),
                'description' => Arr::get($row, 'description'),
                'plans' => Arr::get($row, 'plans', []),
            ];
        })->values();

        return response()->json([
            'current_plan' => $currentPlanData,
            'plans' => $planResponse,
            'comparison' => $comparisonResponse,
            'transactions' => $transactions,
            'meta' => [
                'currency' => $currencySymbol,
                'billing_period' => $billingPeriod,
            ],
            'yookassa' => [
                'enabled' => $this->yooKassa->enabled(),
            ],
        ]);
    }

    public function upgrade(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'plan_id' => ['required', 'integer', 'exists:plans,id'],
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->validator->errors()->first('plan_id') ?? trans('subscription.alerts.upgrade_error'),
            ], 422);
        }

        $user = $request->user();
        $plan = Plan::findOrFail($validated['plan_id']);
        $currentPlan = $user->plans()->orderByDesc('plan_user.created_at')->first();

        if ($currentPlan && $plan->price <= $currentPlan->price) {
            return response()->json([
                'message' => trans('subscription.alerts.upgrade_error'),
            ], 422);
        }

        if ($plan->price <= 0) {
            $user->plans()->syncWithoutDetaching([$plan->getKey() => ['ends_at' => null]]);
            $user->plans()->updateExistingPivot($plan->getKey(), ['ends_at' => null]);

            return response()->json([
                'message' => trans('subscription.current_plan.free_plan'),
            ]);
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
            $payment = $this->yooKassa->createPayment($user, $plan, url()->previous() ?: url('/subscription'));
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

            return response()->json([
                'message' => trans('subscription.alerts.upgrade_error'),
            ], 422);
        }

        $transaction->update([
            'payment_id' => $payment['id'] ?? null,
            'status' => $payment['status'] ?? 'pending',
            'metadata' => array_merge($transaction->metadata ?? [], Arr::only($payment, ['confirmation_url'])),
        ]);

        $confirmationUrl = $payment['confirmation_url'] ?? null;

        return response()->json([
            'redirect_url' => $confirmationUrl,
            'message' => $confirmationUrl ? null : trans('subscription.subtitle'),
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentPlan = $user->plans()->orderByDesc('plan_user.created_at')->first();

        if (! $currentPlan) {
            return response()->json([
                'message' => trans('subscription.current_plan.no_plan'),
            ], 404);
        }

        $now = Carbon::now();
        $user->plans()->updateExistingPivot($currentPlan->getKey(), [
            'ends_at' => $now,
        ]);

        return response()->json([
            'message' => trans('subscription.alerts.cancel_success', [
                'date' => $now->locale(app()->getLocale())->isoFormat('D MMMM YYYY'),
            ]),
            'ends_at' => $now->toIso8601String(),
        ]);
    }
}
