<?php

namespace App\Services;

use App\Models\SubscriptionTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubscriptionPaymentSyncService
{
    public function __construct(private readonly YooKassaService $yooKassa)
    {
    }

    public function syncForUser(User $user): int
    {
        return $this->syncTransactions(
            $user->subscriptionTransactions()
                ->whereIn('status', ['pending', 'waiting_for_capture'])
                ->whereNotNull('payment_id')
                ->latest()
                ->get(),
            false
        );
    }

    public function syncAllPending(): int
    {
        return $this->syncTransactions(
            SubscriptionTransaction::query()
                ->with(['user', 'plan'])
                ->whereIn('status', ['pending', 'waiting_for_capture'])
                ->whereNotNull('payment_id')
                ->where(function ($query) {
                    $query
                        ->whereNull('metadata->sync_attempts')
                        ->orWhereRaw("(metadata->>'sync_attempts')::int < 10");
                })
                ->latest()
                ->get(),
            true
        );
    }

    private function syncTransactions($transactions, bool $trackAttempts): int
    {
        if (! $this->yooKassa->enabled()) {
            return 0;
        }

        $updated = 0;

        foreach ($transactions as $transaction) {
            if ($trackAttempts) {
                $metadata = $transaction->metadata ?? [];
                $metadata['sync_attempts'] = (int) ($metadata['sync_attempts'] ?? 0) + 1;
                $metadata['last_sync_attempt_at'] = Carbon::now()->toIso8601String();
                $transaction->update([
                    'metadata' => $metadata,
                ]);
            }

            try {
                $payment = $this->yooKassa->getPaymentInfo((string) $transaction->payment_id);
            } catch (Throwable $exception) {
                Log::warning('Failed to sync YooKassa payment status', [
                    'transaction_id' => $transaction->getKey(),
                    'payment_id' => $transaction->payment_id,
                    'exception' => $exception->getMessage(),
                ]);

                continue;
            }

            $status = strtolower((string) ($payment['status'] ?? $transaction->status ?? 'pending'));
            $isPaid = (bool) ($payment['paid'] ?? false);
            $paidAt = $payment['captured_at'] ?? $payment['created_at'] ?? null;
            $needsUpdate = $status !== strtolower((string) $transaction->status)
                || ($isPaid && ! $transaction->paid_at);

            if (! $needsUpdate) {
                continue;
            }

            DB::transaction(function () use ($transaction, $status, $isPaid, $paidAt) {
                $metadata = $transaction->metadata ?? [];
                $metadata['synced_at'] = Carbon::now()->toIso8601String();

                $transaction->update([
                    'status' => $status,
                    'paid_at' => $isPaid && $paidAt ? Carbon::parse($paidAt) : $transaction->paid_at,
                    'metadata' => $metadata,
                ]);

                if (! $isPaid || $status !== 'succeeded') {
                    return;
                }

                $alreadyActivated = $transaction->user
                    ->plans()
                    ->wherePivot('plan_id', $transaction->plan_id)
                    ->where('plan_user.created_at', '>=', $transaction->created_at)
                    ->exists();

                if ($alreadyActivated) {
                    return;
                }

                $startsAt = $transaction->paid_at ?? Carbon::now();
                $transaction->user->plans()->attach($transaction->plan_id, [
                    'ends_at' => $startsAt->copy()->addMonth(),
                    'created_at' => $startsAt,
                    'updated_at' => $startsAt,
                ]);
            });

            $updated++;
        }

        return $updated;
    }
}
