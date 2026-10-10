<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Prepayment\PrepaymentSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ЮKassa's notification for a booking prepayment.
 *
 * The body is never believed: it only says which payment to look at, and the
 * settlement service asks ЮKassa about it with the master's own keys. So a
 * forged call can at worst cost one API request.
 *
 * Each master points her shop's notifications at this URL; those who do not
 * are still covered by the minute-by-minute `prepayment:sync`.
 */
class YooKassaWebhookController extends Controller
{
    public function __invoke(Request $request, PrepaymentSettlementService $settlement): JsonResponse
    {
        $event = (string) $request->input('event', '');
        $object = (array) $request->input('object', []);

        $paymentId = str_starts_with($event, 'refund.') ? ($object['payment_id'] ?? null) : ($object['id'] ?? null);

        if (! is_string($paymentId) || $paymentId === '') {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        // Subscription payments (the platform's own shop) land here too.
        $payment = Payment::query()->where('provider_payment_id', $paymentId)->first();

        if (! $payment) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        try {
            $settlement->sync($payment);
        } catch (Throwable $exception) {
            Log::warning('YooKassa webhook could not be settled', [
                'payment_id' => $payment->id,
                'event' => $event,
                'exception' => $exception->getMessage(),
            ]);

            // Not 2xx: ЮKassa will deliver the notification again.
            return response()->json(['ok' => false], 500);
        }

        return response()->json(['ok' => true]);
    }
}
