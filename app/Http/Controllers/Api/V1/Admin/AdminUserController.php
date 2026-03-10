<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Order;
use App\Models\Plan;
use App\Models\SubscriptionTransaction;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Admin\AdminAuditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly AdminAuditService $auditService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            })
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->withCount([
                'clients',
                'subscriptionTransactions',
            ])
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (User $user) => $this->transformUserSummary($user))
            ->all();

        return response()->json([
            'data' => $users,
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount(['clients', 'subscriptionTransactions']);

        $latestPlan = $user->plans()->latest('plan_user.created_at')->first();
        $latestTransaction = SubscriptionTransaction::query()
            ->with('plan')
            ->where('user_id', $user->id)
            ->latest('paid_at')
            ->first();

        $supportStats = [
            'total' => SupportTicket::where('user_id', $user->id)->count(),
            'open' => SupportTicket::where('user_id', $user->id)
                ->whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_WAITING])
                ->count(),
        ];

        $activity = [
            'orders_total' => Order::where('master_id', $user->id)->count(),
            'orders_completed' => Order::where('master_id', $user->id)->where('status', 'completed')->count(),
            'clients_total' => Client::where('user_id', $user->id)->count(),
        ];

        $audit = AuditLog::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (AuditLog $item) => [
                'id' => $item->id,
                'action' => $item->action,
                'meta' => $item->meta,
                'created_at' => optional($item->created_at)->toIso8601String(),
            ])
            ->all();

        return response()->json([
            'data' => array_merge($this->transformUserSummary($user), [
                'phone' => $user->phone,
                'timezone' => $user->timezone,
                'admin_notes' => $user->admin_notes,
                'latest_plan' => $latestPlan ? [
                    'id' => $latestPlan->id,
                    'name' => $latestPlan->name,
                ] : null,
                'latest_transaction' => $latestTransaction ? [
                    'amount' => (float) $latestTransaction->amount,
                    'status' => $latestTransaction->status,
                    'paid_at' => optional($latestTransaction->paid_at)->toIso8601String(),
                    'plan' => $latestTransaction->plan?->name,
                ] : null,
                'subscription' => [
                    'current_plan' => $latestPlan ? [
                        'id' => $latestPlan->id,
                        'name' => $latestPlan->name,
                        'price' => (float) $latestPlan->price,
                        'ends_at' => optional($latestPlan->pivot?->ends_at)->toIso8601String(),
                    ] : null,
                    'plans' => Plan::query()
                        ->orderBy('price')
                        ->get()
                        ->map(fn (Plan $plan) => [
                            'id' => $plan->id,
                            'name' => $plan->name,
                            'price' => (float) $plan->price,
                        ])
                        ->all(),
                ],
                'support' => $supportStats,
                'activity' => $activity,
                'audit' => $audit,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request, null);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
            'is_admin' => $validated['is_admin'],
            'admin_role' => $validated['is_admin'] ? $validated['admin_role'] : null,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'suspended_at' => $validated['status'] === User::STATUS_SUSPENDED ? now() : null,
        ]);

        $this->auditService->log(
            userId: $user->id,
            actorId: $request->user()?->id,
            action: 'admin.user_created',
            subject: $user,
            request: $request,
            meta: [
                'is_admin' => $user->is_admin,
                'admin_role' => $user->admin_role,
            ]
        );

        return response()->json([
            'data' => $this->transformUserSummary($user),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $this->validatePayload($request, $user);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->status = $validated['status'];
        $user->is_admin = $validated['is_admin'];
        $user->admin_role = $validated['is_admin'] ? $validated['admin_role'] : null;
        $user->suspended_at = $validated['status'] === User::STATUS_SUSPENDED ? now() : null;
        $user->admin_notes = $validated['admin_notes'] ?? null;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $this->auditService->log(
            userId: $user->id,
            actorId: $request->user()?->id,
            action: $validated['status'] === User::STATUS_SUSPENDED ? 'admin.user_suspended' : 'admin.user_activated',
            subject: $user,
            request: $request,
            meta: ['admin_notes' => $user->admin_notes]
        );

        return response()->json([
            'data' => $this->transformUserSummary($user->fresh()),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_if($request->user()?->id === $user->id, 422, 'You cannot delete your own admin account.');

        $this->auditService->log(
            userId: $user->id,
            actorId: $request->user()?->id,
            action: 'admin.user_deleted',
            subject: $user,
            request: $request,
            meta: ['email' => $user->email]
        );

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'User deleted.',
        ]);
    }

    public function updateSubscription(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $currentPlan = $user->plans()->latest('plan_user.created_at')->first();
        $newPlan = ! empty($validated['plan_id']) ? Plan::findOrFail($validated['plan_id']) : null;
        $endsAt = ! empty($validated['ends_at']) ? Carbon::parse($validated['ends_at']) : null;

        if ($currentPlan && (! $newPlan || $currentPlan->id !== $newPlan->id)) {
            $user->plans()->updateExistingPivot($currentPlan->id, [
                'ends_at' => now(),
            ]);
        }

        if ($newPlan) {
            $user->plans()->syncWithoutDetaching([$newPlan->id => ['ends_at' => $endsAt]]);
            $user->plans()->updateExistingPivot($newPlan->id, ['ends_at' => $endsAt]);

            SubscriptionTransaction::create([
                'user_id' => $user->id,
                'plan_id' => $newPlan->id,
                'amount' => $newPlan->price,
                'currency' => config('services.yookassa.currency', 'RUB'),
                'status' => 'paid',
                'paid_at' => now(),
                'metadata' => [
                    'initiated_by' => 'admin_backoffice',
                    'previous_plan_id' => $currentPlan?->id,
                    'manual_ends_at' => $endsAt?->toIso8601String(),
                ],
            ]);
        } elseif ($currentPlan) {
            $user->plans()->updateExistingPivot($currentPlan->id, [
                'ends_at' => $endsAt ?: now(),
            ]);
        }

        $this->auditService->log(
            userId: $user->id,
            actorId: $request->user()?->id,
            action: 'admin.subscription_updated',
            subject: $user,
            request: $request,
            meta: [
                'previous_plan_id' => $currentPlan?->id,
                'new_plan_id' => $newPlan?->id,
                'ends_at' => $endsAt?->toIso8601String(),
            ]
        );

        return response()->json([
            'data' => [
                'current_plan' => $newPlan ? [
                    'id' => $newPlan->id,
                    'name' => $newPlan->name,
                    'price' => (float) $newPlan->price,
                    'ends_at' => $endsAt?->toIso8601String(),
                ] : ($currentPlan ? [
                    'id' => $currentPlan->id,
                    'name' => $currentPlan->name,
                    'price' => (float) $currentPlan->price,
                    'ends_at' => ($endsAt ?: now())->toIso8601String(),
                ] : null),
            ],
        ]);
    }

    protected function validatePayload(Request $request, ?User $user): array
    {
        if ($request->input('password') === '') {
            $request->merge(['password' => null]);
        }

        $emailRule = Rule::unique('users', 'email');
        if ($user) {
            $emailRule = $emailRule->ignore($user->id);
        }

        $isCreating = ! $user;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', $emailRule],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$isCreating ? 'required' : 'nullable', 'string', 'min:8'],
            'status' => ['required', Rule::in([User::STATUS_ACTIVE, User::STATUS_SUSPENDED])],
            'is_admin' => ['required', 'boolean'],
            'admin_role' => ['nullable', Rule::in(User::adminRoles())],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    protected function transformUserSummary(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'status_label' => $user->status_label,
            'is_admin' => $user->is_admin,
            'admin_role' => $user->admin_role,
            'admin_role_label' => $user->admin_role_label,
            'clients_count' => (int) ($user->clients_count ?? 0),
            'subscription_transactions_count' => (int) ($user->subscription_transactions_count ?? 0),
            'created_at' => optional($user->created_at)->toIso8601String(),
            'last_seen_at' => optional($user->updated_at)->toIso8601String(),
        ];
    }
}
