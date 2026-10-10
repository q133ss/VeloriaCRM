<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Prepayment\PrepaymentSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Where a client lands after paying a booking prepayment (from the landing
 * page or the app), and the poll that page uses to learn the result. The
 * token in the address is the only key; it says nothing about anyone else.
 */
class PaymentReturnController extends Controller
{
    public function __construct(private readonly PrepaymentSettlementService $settlement)
    {
    }

    /** Two addresses lead here (/pay/return/{token}, /l/{slug}/paid/{token}); the token is read by name, not by position. */
    public function page(Request $request): Response
    {
        $token = (string) $request->route('token');

        return response()->view('payments.return', [
            'token' => $token,
            'statusUrl' => url('/api/v1/payments/status/' . $token),
        ]);
    }

    public function status(string $token): JsonResponse
    {
        $payment = Payment::query()->where('return_token', $token)->with('order')->first();

        if (! $payment || ! $payment->order) {
            return response()->json(['data' => ['state' => 'unknown']], 404);
        }

        // The client has just come back: do not wait for the webhook or the minute sync.
        if (in_array($payment->status, [Payment::STATUS_PENDING, Payment::STATUS_WAITING_FOR_CAPTURE], true)) {
            try {
                $payment = $this->settlement->sync($payment)->load('order');
            } catch (Throwable $exception) {
                Log::info('Could not check a prepayment on return', ['payment_id' => $payment->id, 'exception' => $exception->getMessage()]);
            }
        }

        $order = $payment->order;
        $timezone = User::query()->find($order->master_id)?->timezone ?: config('app.timezone');
        $start = $order->scheduled_at?->copy()->timezone($timezone);

        return response()->json(['data' => [
            'state' => $this->state($order),
            'amount' => (float) $payment->amount,
            'service' => $payment->metadata['service_label'] ?? null,
            'date_label' => $start?->translatedFormat('j F, l'),
            'time' => $start?->format('H:i'),
            'expires_at' => $order->payment_status === 'awaiting' ? $order->prepay_expires_at?->toIso8601String() : null,
        ]]);
    }

    private function state(Order $order): string
    {
        return match ($order->payment_status) {
            'paid' => 'paid',
            'awaiting' => 'awaiting',
            'refunded' => 'refunded',
            default => 'expired',
        };
    }
}
