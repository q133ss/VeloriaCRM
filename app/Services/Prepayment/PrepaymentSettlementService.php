<?php

namespace App\Services\Prepayment;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\LandingRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\Booking\BookingConflictService;
use App\Services\Booking\ClientBookingService;
use App\Services\Booking\PrepaymentFailedException;
use App\Services\ClientNotificationService;
use App\Services\WaitlistMatchService;
use App\Services\YooKassaService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Everything that happens to a booking prepayment after it was created: the
 * money arrives (webhook, the client coming back, the minute-by-minute sync),
 * the time runs out, the booking is cancelled and the money goes back.
 *
 * Money is only ever believed after asking ЮKassa with the master's own keys;
 * a webhook body or a return URL just says "go and look".
 */
class PrepaymentSettlementService
{
    public function __construct(
        private readonly ClientBookingService $booking,
        private readonly BookingConflictService $conflicts,
        private readonly WaitlistMatchService $waitlist,
        private readonly ClientNotificationService $clientNotifications,
    ) {
    }

    public function gateway(Payment $payment): YooKassaService
    {
        return YooKassaService::forMaster(Setting::query()->where('user_id', $payment->user_id)->first());
    }

    /** Ask ЮKassa what became of the payment and bring the booking in line. */
    public function sync(Payment $payment): Payment
    {
        return $this->apply($payment, $this->gateway($payment)->getPaymentInfo($payment->provider_payment_id));
    }

    /**
     * @param  array{status:string,paid:bool,captured_at?:?string,refunded_amount?:?string}  $info
     */
    public function apply(Payment $payment, array $info): Payment
    {
        $status = strtolower((string) ($info['status'] ?? ''));
        $paid = (bool) ($info['paid'] ?? false) && $status === 'succeeded';
        $refunded = (float) ($info['refunded_amount'] ?? 0);

        $outcome = DB::transaction(function () use ($payment, $status, $paid, $refunded, $info) {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = $locked->order_id ? Order::query()->lockForUpdate()->find($locked->order_id) : null;

            if ($refunded > (float) $locked->refunded_amount) {
                $locked->refunded_amount = $refunded;
            }

            if ($locked->status === Payment::STATUS_SUCCEEDED || $locked->status === Payment::STATUS_REFUNDED) {
                $this->recordRefund($locked, $order);
                $locked->save();

                return 'noop';
            }

            if ($paid) {
                $locked->forceFill([
                    'status' => Payment::STATUS_SUCCEEDED,
                    'paid_at' => ! empty($info['captured_at']) ? Carbon::parse($info['captured_at']) : now(),
                ])->save();

                return $order ? $this->settlePaid($locked, $order) : 'noop';
            }

            if ($status === Payment::STATUS_CANCELED) {
                $locked->forceFill(['status' => Payment::STATUS_CANCELED])->save();

                return 'canceled';
            }

            if ($status !== '' && $status !== $locked->status) {
                $locked->forceFill(['status' => $status])->save();
            }

            return 'noop';
        });

        $payment->refresh();
        $order = $payment->order;

        match ($outcome) {
            'paid' => $this->afterPaid($payment, $order),
            'refund_late' => $this->refundLatePayment($payment, $order),
            'canceled' => $order ? $this->expire($order, 'failed') : null,
            default => null,
        };

        return $payment->refresh();
    }

    /**
     * Cancel a booking whose prepayment did not arrive, freeing its slot.
     * Does nothing unless it is still waiting for payment.
     */
    public function expire(Order $order, string $paymentStatus = 'expired'): bool
    {
        $done = DB::transaction(function () use ($order, $paymentStatus) {
            $locked = Order::query()->lockForUpdate()->find($order->id);

            if (! $locked || $locked->payment_status !== 'awaiting') {
                return false;
            }

            $locked->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => __('prepayment.cancel_reason_unpaid'),
                'payment_status' => $paymentStatus,
            ]);

            $this->booking->cancelAppointmentFor($locked);

            return true;
        });

        if (! $done) {
            return false;
        }

        $order->refresh();
        $this->markLandingRequest($order, 'expired');
        $this->tellClientBookingLapsed($order);

        if ($order->scheduled_at) {
            $serviceId = collect($order->services ?? [])->pluck('id')->filter()->map(fn ($id) => (int) $id)->first();
            $this->waitlist->notifyMatchesForSlot(
                $order->master_id,
                $order->scheduled_at->copy(),
                $this->conflicts->resolveOrderDuration($order),
                $serviceId,
            );
        }

        return true;
    }

    /**
     * What a master's cancellation should return, by her own rule:
     * everything when the visit is far enough away, otherwise only the
     * `partial_percent` she chose (default none).
     */
    public function suggestedRefund(Order $order): float
    {
        $paid = $this->refundable($order);

        if ($paid <= 0) {
            return 0.0;
        }

        $refund = (array) (Setting::query()->where('user_id', $order->master_id)->first()?->deposit_policy['refund'] ?? []);
        $hours = $refund['full_before_hours'] ?? null;

        if ($hours === null || $hours === '' || ! $order->scheduled_at
            || now()->lte($order->scheduled_at->copy()->subHours((int) $hours))) {
            return $paid;
        }

        return round($paid * max(0, min(100, (float) ($refund['partial_percent'] ?? 0))) / 100, 2);
    }

    /** What is still returnable on the order's prepayment. */
    public function refundable(Order $order): float
    {
        $payment = $this->succeededPayment($order);

        return $payment ? max(0.0, round((float) $payment->amount - (float) $payment->refunded_amount, 2)) : 0.0;
    }

    /**
     * Send money back. `$amount` null returns everything still returnable.
     *
     * @throws PrepaymentFailedException when ЮKassa refuses (e.g. not enough on the shop's balance)
     */
    public function refund(Order $order, ?float $amount = null): float
    {
        $payment = $this->succeededPayment($order);
        $available = $this->refundable($order);
        $amount = round(min($amount ?? $available, $available), 2);

        if (! $payment || $amount < 0.01) {
            return 0.0;
        }

        try {
            $this->gateway($payment)->refund($payment->provider_payment_id, $amount, (string) $payment->refunded_amount);
        } catch (\Throwable $exception) {
            Log::warning('Could not refund a booking prepayment', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'exception' => $exception->getMessage(),
            ]);

            throw new PrepaymentFailedException($exception->getMessage(), 0, $exception);
        }

        DB::transaction(function () use ($payment, $order, $amount) {
            $payment->refresh();
            $payment->refunded_amount = round((float) $payment->refunded_amount + $amount, 2);
            $this->recordRefund($payment, $order);
            $payment->save();
        });

        return $amount;
    }

    private function succeededPayment(Order $order): ?Payment
    {
        return Payment::query()
            ->where('order_id', $order->id)
            ->whereIn('status', [Payment::STATUS_SUCCEEDED, Payment::STATUS_REFUNDED])
            ->latest('id')
            ->first();
    }

    /** A fully refunded payment closes the order's money side. */
    private function recordRefund(Payment $payment, ?Order $order): void
    {
        if ((float) $payment->refunded_amount <= 0) {
            return;
        }

        $full = (float) $payment->refunded_amount >= (float) $payment->amount;

        if ($full) {
            $payment->status = Payment::STATUS_REFUNDED;
        }

        $order?->forceFill([
            'payment_status' => $full ? 'refunded' : $order->payment_status,
            'prepaid_amount' => max(0, round((float) $payment->amount - (float) $payment->refunded_amount, 2)),
        ])->save();
    }

    /**
     * The money is in. Returns what to do once the transaction is over:
     * 'paid' (confirm and tell everyone) or 'refund_late' (the time is gone
     * and cannot be given back).
     */
    private function settlePaid(Payment $payment, Order $order): string
    {
        $unpaidHold = $order->payment_status === 'awaiting';
        $lapsedHold = $order->payment_status === 'expired' && $order->status === 'cancelled';

        if ($lapsedHold && ! $this->slotIsFree($order)) {
            return 'refund_late';
        }

        if (! $unpaidHold && ! $lapsedHold) {
            // Cancelled by the master, or settled already: not a booking to confirm.
            return $order->status === 'cancelled' ? 'refund_late' : 'noop';
        }

        $order->forceFill([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'payment_status' => 'paid',
            'prepaid_amount' => $payment->amount,
            'prepay_expires_at' => null,
        ])->save();

        $appointmentId = (int) ($payment->metadata['appointment_id'] ?? 0);
        Appointment::query()->whereKey($appointmentId)->get()->each(function (Appointment $appointment) {
            $appointment->update([
                'status' => 'confirmed',
                'meta' => array_merge((array) $appointment->meta, ['awaiting_payment' => false]),
            ]);
        });

        return 'paid';
    }

    private function slotIsFree(Order $order): bool
    {
        if (! $order->scheduled_at || $order->scheduled_at->isPast()) {
            return false;
        }

        return $this->conflicts->detectConflict(
            $order->master_id,
            $order->scheduled_at->copy(),
            $this->conflicts->resolveOrderDuration($order),
            $order->id,
        ) === null;
    }

    private function afterPaid(Payment $payment, ?Order $order): void
    {
        $this->booking->completePaidBooking($payment);

        if ($order) {
            $this->markLandingRequest($order, 'booked');
        }
    }

    /** Paid for a time that is gone (or that the master cancelled): give it back. */
    private function refundLatePayment(Payment $payment, ?Order $order): void
    {
        if (! $order) {
            return;
        }

        try {
            $this->refund($order);
        } catch (PrepaymentFailedException) {
            // Logged by refund(); the master sees the paid-but-cancelled order and can return it by hand.
        }
    }

    private function tellClientBookingLapsed(Order $order): void
    {
        $payment = Payment::query()->where('order_id', $order->id)->latest('id')->first();
        $client = Client::query()->find($payment?->metadata['client_card_id'] ?? 0);

        if (! $client) {
            return;
        }

        $label = (string) ($payment->metadata['service_label'] ?? '');
        $startsAt = $order->scheduled_at->copy()->timezone($this->booking->masterTimezone($order->master_id));

        $this->clientNotifications->sendToClient(
            $client,
            __('prepayment.lapsed_title'),
            __('prepayment.lapsed_message', ['service' => $label, 'datetime' => $startsAt->translatedFormat('d.m.Y H:i')]),
        );
    }

    /** Landing requests are kept as a log of who booked; keep their status honest. */
    private function markLandingRequest(Order $order, string $status): void
    {
        $payment = Payment::query()->where('order_id', $order->id)->latest('id')->first();
        $clientId = $payment?->metadata['client_card_id'] ?? null;

        if (! $clientId) {
            return;
        }

        LandingRequest::query()
            ->where('user_id', $order->master_id)
            ->where('client_id', $clientId)
            ->where('status', 'awaiting_payment')
            ->get()
            ->filter(fn (LandingRequest $request) => (int) ($request->meta['order_id'] ?? 0) === (int) $order->id)
            ->each(fn (LandingRequest $request) => $request->update(['status' => $status]));
    }
}
