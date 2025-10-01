<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use YooKassa\Client;

class YooKassaService
{
    private ?Client $client = null;

    public function __construct()
    {
        $shopId = config('services.yookassa.shop_id');
        $secretKey = config('services.yookassa.secret_key');

        if ($shopId && $secretKey) {
            $client = new Client();
            $client->setAuth($shopId, $secretKey);
            $this->client = $client;
        }
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
}
