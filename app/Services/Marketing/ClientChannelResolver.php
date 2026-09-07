<?php

namespace App\Services\Marketing;

use App\Models\Client;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Arr;

/**
 * Which channel can actually carry a message to this person right now.
 *
 * A channel counts only when both halves exist: the master configured it, and
 * this particular client can be reached over it. The campaign service answers a
 * narrower version of the same question, but it only ever sees a Client card and
 * therefore always reports Telegram as unreachable — the chat id lives on the
 * client's user account, which is exactly where bot bookings write it.
 */
class ClientChannelResolver
{
    /** Telegram first: it is free, instant, and the client opted into it. */
    private const PREFERENCE = ['telegram', 'whatsapp', 'sms', 'email'];

    /**
     * @return array{channel: string, address: string}|null
     */
    public function resolve(?Setting $settings, ?Client $card, ?User $account): ?array
    {
        foreach ($this->availableChannels($settings, $card, $account) as $channel) {
            $address = $this->addressFor($channel, $card, $account);

            if ($address !== null) {
                return ['channel' => $channel, 'address' => $address];
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public function availableChannels(?Setting $settings, ?Client $card, ?User $account): array
    {
        if (! $settings) {
            return [];
        }

        $channels = [];

        foreach (self::PREFERENCE as $channel) {
            if (! $this->configured($settings, $channel)) {
                continue;
            }

            if ($this->addressFor($channel, $card, $account) === null) {
                continue;
            }

            $channels[] = $channel;
        }

        return $channels;
    }

    private function configured(Setting $settings, string $channel): bool
    {
        return match ($channel) {
            'telegram' => filled($settings->telegram_bot_token),
            'whatsapp' => filled($settings->whatsapp_api_key) && filled($settings->whatsapp_sender),
            'sms' => filled($settings->smsaero_email) && filled($settings->smsaero_api_key),
            'email' => filled($settings->smtp_host) && filled($settings->smtp_from_address),
            default => false,
        };
    }

    private function addressFor(string $channel, ?Client $card, ?User $account): ?string
    {
        return match ($channel) {
            'telegram' => $account?->telegram_id ?: null,
            'whatsapp' => $this->normalizePhone($this->whatsappPhone($card, $account)),
            'sms' => $this->normalizePhone($card?->phone ?: $account?->phone),
            'email' => $card?->email ?: $account?->email,
            default => null,
        };
    }

    private function whatsappPhone(?Client $card, ?User $account): ?string
    {
        $preferences = $card?->preferences ?? [];

        return Arr::get($preferences, 'whatsapp')
            ?? Arr::get($preferences, 'whatsapp_phone')
            ?? $card?->phone
            ?? $account?->phone;
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/[^0-9+]+/', '', $phone);

        return $digits !== '' ? $digits : null;
    }
}
