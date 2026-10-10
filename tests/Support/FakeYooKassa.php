<?php

namespace Tests\Support;

use App\Models\Order;
use App\Services\YooKassaService;
use RuntimeException;

/**
 * ЮKassa stand-in for tests. State is static because the service container
 * builds a fresh instance for every master.
 */
class FakeYooKassa extends YooKassaService
{
    /** @var array<string, array{status:string,paid:bool,amount:float,refunded:float,captured_at:?string}> */
    public static array $payments = [];

    /** @var array<int, array{payment:string,amount:float}> */
    public static array $refunds = [];

    public static bool $down = false;

    public static bool $refundFails = false;

    public function __construct()
    {
    }

    public static function reset(): void
    {
        self::$payments = [];
        self::$refunds = [];
        self::$down = false;
        self::$refundFails = false;
    }

    /** What ЮKassa would now report after the client paid. */
    public static function markPaid(string $id): void
    {
        self::$payments[$id]['status'] = 'succeeded';
        self::$payments[$id]['paid'] = true;
        self::$payments[$id]['captured_at'] = now()->toIso8601String();
    }

    public function enabled(): bool
    {
        return true;
    }

    public function createBookingPayment(Order $order, float $amount, string $returnUrl, string $description): array
    {
        if (self::$down) {
            throw new RuntimeException('down');
        }

        $id = 'pay-' . $order->id;
        self::$payments[$id] = ['status' => 'pending', 'paid' => false, 'amount' => $amount, 'refunded' => 0.0, 'captured_at' => null];

        return [
            'id' => $id,
            'status' => 'pending',
            'paid' => false,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'RUB',
            'confirmation_url' => 'https://yookassa.test/pay/' . $order->id . '?return=' . urlencode($returnUrl),
        ];
    }

    public function getPaymentInfo(string $paymentId): array
    {
        if (self::$down) {
            throw new RuntimeException('down');
        }

        $payment = self::$payments[$paymentId];

        return [
            'id' => $paymentId,
            'status' => $payment['status'],
            'paid' => $payment['paid'],
            'amount' => number_format($payment['amount'], 2, '.', ''),
            'refunded_amount' => number_format($payment['refunded'], 2, '.', ''),
            'captured_at' => $payment['captured_at'],
            'created_at' => null,
            'metadata' => [],
            'cancellation_reason' => null,
        ];
    }

    public function refund(string $paymentId, float $amount, string $idempotenceSuffix = ""): array
    {
        if (self::$refundFails) {
            throw new RuntimeException('insufficient funds');
        }

        self::$refunds[] = ['payment' => $paymentId, 'amount' => $amount];
        self::$payments[$paymentId]['refunded'] += $amount;

        return ['id' => 'refund-' . count(self::$refunds), 'status' => 'succeeded'];
    }
}
