<?php

namespace App\Services\Integrations;

use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Throwable;

/**
 * Asks the service itself whether the keys work.
 *
 * The page used to call a channel «Подключено» as soon as its fields were not
 * empty, so the word «ляляля» in the token field produced a green badge and a
 * card reading «Бот подключен и может принимать записи».
 *
 * Every check below only reads: getMe, a balance, an SMTP handshake, one page
 * of payments. Nothing is sent to a client.
 */
class IntegrationChecker
{
    private const TIMEOUT = 8;

    /**
     * @return array{ok: bool, message: string}
     */
    public function check(?Setting $settings, string $provider): array
    {
        if (! IntegrationCatalog::isFilled($settings, $provider)) {
            return $this->fail(__('integrations.checks.missing'));
        }

        $values = IntegrationCatalog::values($settings, $provider);

        try {
            return match ($provider) {
                'telegram' => $this->checkTelegram($values),
                'smsaero' => $this->checkSmsAero($values),
                'smtp' => $this->checkSmtp($values),
                'yookassa' => $this->checkYooKassa($values),
                'whatsapp' => $this->checkWhatsapp($values),
                default => $this->fail(__('integrations.checks.unsupported')),
            };
        } catch (Throwable $exception) {
            Log::info('Integration check could not reach the service.', [
                'provider' => $provider,
                'exception' => $exception->getMessage(),
            ]);

            return $this->fail(__('integrations.checks.network'));
        }
    }

    /**
     * @param  array<string, string|null>  $values
     * @return array{ok: bool, message: string}
     */
    protected function checkTelegram(array $values): array
    {
        $response = Http::timeout(self::TIMEOUT)
            ->get(sprintf('https://api.telegram.org/bot%s/getMe', $values['bot_token']));

        if (! $response->successful() || ! Arr::get($response->json(), 'ok')) {
            return $this->fail(__('integrations.checks.telegram.fail'));
        }

        $username = Arr::get($response->json(), 'result.username');

        return $this->ok($username
            ? __('integrations.checks.telegram.ok_named', ['name' => '@' . $username])
            : __('integrations.checks.telegram.ok'));
    }

    /**
     * @param  array<string, string|null>  $values
     * @return array{ok: bool, message: string}
     */
    protected function checkSmsAero(array $values): array
    {
        $response = Http::withBasicAuth((string) $values['email'], (string) $values['api_key'])
            ->timeout(self::TIMEOUT)
            ->get('https://gate.smsaero.ru/v2/balance');

        if (! $response->successful() || Arr::get($response->json(), 'success') !== true) {
            return $this->fail(__('integrations.checks.smsaero.fail'));
        }

        $balance = Arr::get($response->json(), 'data.balance');

        return $this->ok($balance === null
            ? __('integrations.checks.smsaero.ok')
            : __('integrations.checks.smsaero.ok_balance', ['balance' => (string) round((float) $balance, 2)]));
    }

    /**
     * A login handshake and nothing more — no message leaves the account.
     *
     * @param  array<string, string|null>  $values
     * @return array{ok: bool, message: string}
     */
    protected function checkSmtp(array $values): array
    {
        $transport = new EsmtpTransport(
            (string) $values['host'],
            (int) $values['port'],
            ($values['encryption'] ?? null) === 'ssl',
        );

        $transport->setUsername((string) $values['username']);
        $transport->setPassword((string) $values['password']);
        $transport->getStream()->setTimeout(self::TIMEOUT);

        try {
            $transport->start();
            $transport->stop();
        } catch (Throwable $exception) {
            $reason = $exception->getMessage();

            if (preg_match('/authenticat|password|credential|535|534/i', $reason)) {
                return $this->fail(__('integrations.checks.smtp.auth'));
            }

            return $this->fail(__('integrations.checks.smtp.fail'));
        }

        return $this->ok(__('integrations.checks.smtp.ok'));
    }

    /**
     * @param  array<string, string|null>  $values
     * @return array{ok: bool, message: string}
     */
    protected function checkYooKassa(array $values): array
    {
        $response = Http::withBasicAuth((string) $values['shop_id'], (string) $values['secret_key'])
            ->timeout(self::TIMEOUT)
            ->get('https://api.yookassa.ru/v3/payments', ['limit' => 1]);

        if (! $response->successful()) {
            return $this->fail(__('integrations.checks.yookassa.fail'));
        }

        return $this->ok(__('integrations.checks.yookassa.ok'));
    }

    /**
     * @param  array<string, string|null>  $values
     * @return array{ok: bool, message: string}
     */
    protected function checkWhatsapp(array $values): array
    {
        $response = Http::withToken((string) $values['api_key'])
            ->timeout(self::TIMEOUT)
            ->get(sprintf('https://graph.facebook.com/v17.0/%s', $values['sender']));

        if (! $response->successful() || ! Arr::get($response->json(), 'id')) {
            return $this->fail(__('integrations.checks.whatsapp.fail'));
        }

        return $this->ok(__('integrations.checks.whatsapp.ok'));
    }

    /**
     * @return array{ok: bool, message: string}
     */
    protected function ok(string $message): array
    {
        return ['ok' => true, 'message' => $message];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    protected function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }
}
