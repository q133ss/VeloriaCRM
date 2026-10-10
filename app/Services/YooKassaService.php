<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use YooKassa\Client;

class YooKassaService
{
    private ?Client $client = null;

    /**
     * Without arguments this is the platform's own shop (subscriptions). A
     * master's shop for booking prepayments is built with forMaster().
     */
    public function __construct(?string $shopId = null, ?string $secretKey = null)
    {
        $shopId ??= config('services.yookassa.shop_id');
        $secretKey ??= config('services.yookassa.secret_key');

        if ($shopId !== null && $shopId !== '' && $secretKey !== null && $secretKey !== '') {
            $client = new Client();
            $client->setAuth($shopId, $secretKey);
            $this->client = $client;
        }
    }

    /** The master's own shop: prepayments go straight to her, not through the platform. */
    public static function forMaster(?Setting $settings): self
    {
        $shopId = trim((string) ($settings?->yookassa_shop_id ?? ''));
        $secretKey = trim((string) ($settings?->yookassa_secret_key ?? ''));

        // Never fall back to the platform's keys here: that would route a
        // master's client money into the platform's account.
        return app(self::class, ['shopId' => $shopId, 'secretKey' => $secretKey]);
    }

    public function enabled(): bool
    {
        return $this->client instanceof Client;
    }

    public function createPayment(User $user, Plan $plan, ?string $returnUrl = null, ?string $description = null): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('YooKassa credentials are not configured.');
        }

        $currency = strtoupper((string) config('services.yookassa.currency', 'RUB'));
        $returnUrl ??= (string) config('services.yookassa.return_url', url('/subscription'));
        $description ??= __('subscription.payment.description', ['plan' => $plan->name]);

        $amount = number_format((float) $plan->price, 2, '.', '');
        if ($amount <= 0) {
            throw new RuntimeException('Cannot create payment for free plan.');
        }

        $payload = [
            'amount' => [
                'value' => $amount,
                'currency' => $currency,
            ],
            'capture' => true,
            'description' => $description,
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $returnUrl,
            ],
            'metadata' => [
                'user_id' => $user->getKey(),
                'plan_id' => $plan->getKey(),
                'plan_slug' => $plan->slug,
            ],
        ];

        $idempotenceKey = Str::uuid()->toString();
        $response = $this->client->createPayment($payload, $idempotenceKey);

        $confirmation = $response->getConfirmation();

        return [
            'id' => $response->getId(),
            'status' => $response->getStatus(),
            'paid' => $response->getPaid(),
            'amount' => Arr::get($payload, 'amount.value'),
            'currency' => $currency,
            'confirmation_url' => $confirmation ? $confirmation->getConfirmationUrl() : null,
            'raw' => $response,
        ];
    }

    /**
     * One-off payment for a booking. The idempotence key is derived from the
     * order, so a retried request returns the same payment instead of a second.
     *
     * @return array{id:string,status:string,paid:bool,amount:string,currency:string,confirmation_url:?string}
     */
    public function createBookingPayment(Order $order, float $amount, string $returnUrl, string $description): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('YooKassa credentials are not configured.');
        }

        $currency = strtoupper((string) config('services.yookassa.currency', 'RUB'));
        $value = number_format($amount, 2, '.', '');

        if ((float) $value < 1) {
            throw new RuntimeException('Prepayment amount is below the minimum.');
        }

        $payload = [
            'amount' => ['value' => $value, 'currency' => $currency],
            'capture' => true,
            'description' => Str::limit($description, 128, ''),
            'confirmation' => ['type' => 'redirect', 'return_url' => $returnUrl],
            'metadata' => [
                'order_id' => $order->getKey(),
                'master_id' => $order->master_id,
                'kind' => 'booking_prepayment',
            ],
        ];

        $response = $this->client->createPayment($payload, 'order-' . $order->getKey() . '-' . sha1($value . $returnUrl));
        $confirmation = $response->getConfirmation();

        return [
            'id' => $response->getId(),
            'status' => $response->getStatus(),
            'paid' => (bool) $response->getPaid(),
            'amount' => $value,
            'currency' => $currency,
            'confirmation_url' => $confirmation ? $confirmation->getConfirmationUrl() : null,
        ];
    }

    /**
     * @return array{id:string,status:string}
     */
    public function refund(string $paymentId, float $amount, string $idempotenceSuffix = ''): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('YooKassa credentials are not configured.');
        }

        $currency = strtoupper((string) config('services.yookassa.currency', 'RUB'));

        $response = $this->client->createRefund([
            'payment_id' => $paymentId,
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency' => $currency,
            ],
        ], 'refund-' . $paymentId . '-' . number_format($amount, 2, '.', '') . '-' . $idempotenceSuffix);

        return ['id' => $response->getId(), 'status' => $response->getStatus()];
    }
    public function getPaymentInfo(string $paymentId): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('YooKassa credentials are not configured.');
        }

        $response = $this->client->getPaymentInfo($paymentId);
        $capturedAt = $response->getCapturedAt();
        $createdAt = $response->getCreatedAt();

        return [
            'id' => $response->getId(),
            'status' => $response->getStatus(),
            'paid' => $response->getPaid(),
            'amount' => $response->getAmount() ? (string) $response->getAmount()->getValue() : null,
            'refunded_amount' => $response->getRefundedAmount() ? (string) $response->getRefundedAmount()->getValue() : '0',
            'cancellation_reason' => $response->getCancellationDetails() ? $response->getCancellationDetails()->getReason() : null,
            'metadata' => $response->getMetadata() ? $response->getMetadata()->toArray() : [],
            'captured_at' => $capturedAt ? Carbon::instance($capturedAt)->toIso8601String() : null,
            'created_at' => $createdAt ? Carbon::instance($createdAt)->toIso8601String() : null,
        ];
    }
}
