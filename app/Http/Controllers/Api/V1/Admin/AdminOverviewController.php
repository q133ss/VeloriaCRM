<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminOverviewController extends Controller
{
    public function index(): JsonResponse
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sevenDaysAgo = $now->copy()->subDays(7);

        $summary = [
            'total_users' => User::count(),
            'new_users_7d' => User::where('created_at', '>=', $sevenDaysAgo)->count(),
            'paid_users' => User::whereHas('subscriptionTransactions', function ($query) {
                $query->where('status', 'paid');
            })->count(),
            'suspended_users' => User::where('status', User::STATUS_SUSPENDED)->count(),
            'open_tickets' => SupportTicket::whereIn('status', [
                SupportTicket::STATUS_OPEN,
                SupportTicket::STATUS_WAITING,
            ])->count(),
            'revenue_30d' => (float) SubscriptionTransaction::query()
                ->where('status', 'paid')
                ->where('paid_at', '>=', $thirtyDaysAgo)
                ->sum('amount'),
        ];

        $plans = SubscriptionTransaction::query()
            ->select('plans.name', DB::raw('COUNT(DISTINCT subscription_transactions.user_id) as total'))
            ->join('plans', 'plans.id', '=', 'subscription_transactions.plan_id')
            ->where('subscription_transactions.status', 'paid')
            ->groupBy('plans.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'total' => (int) $row->total,
            ])
            ->all();

        $tickets = SupportTicket::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentUsers = User::query()
            ->latest()
            ->limit(6)
            ->get(['id', 'name', 'email', 'status', 'is_admin', 'admin_role', 'created_at'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'is_admin' => $user->is_admin,
                'admin_role' => $user->admin_role,
                'created_at' => optional($user->created_at)->toIso8601String(),
            ])
            ->all();

        return response()->json([
            'summary' => $summary,
            'plans' => $plans,
            'tickets' => [
                'open' => (int) ($tickets[SupportTicket::STATUS_OPEN] ?? 0),
                'waiting' => (int) ($tickets[SupportTicket::STATUS_WAITING] ?? 0),
                'responded' => (int) ($tickets[SupportTicket::STATUS_RESPONDED] ?? 0),
                'closed' => (int) ($tickets[SupportTicket::STATUS_CLOSED] ?? 0),
            ],
            'recent_users' => $recentUsers,
        ]);
    }
}
