<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Plain HTTP to Expo's push API — no SDK, sibling to YooKassaService. A
 * backgrounded client app is woken by this, not by the Pusher broadcast
 * alone: `ClientNotificationService::send()` always calls both.
 */
class ExpoPushService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    public function send(string $expoPushToken, string $title, string $body, array $data = []): bool
    {
        if (! str_starts_with($expoPushToken, 'ExponentPushToken')) {
            return false;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Content-Type' => 'application/json',
                ])
                ->post(self::ENDPOINT, [
                    'to' => $expoPushToken,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                ]);
        } catch (\Throwable $exception) {
            Log::warning('Expo push request failed to send.', ['error' => $exception->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('Expo push request rejected.', ['status' => $response->status(), 'body' => $response->body()]);

            return false;
        }

        $status = $response->json('data.status');

        if ($status !== 'ok') {
            Log::warning('Expo push delivery reported an error.', ['response' => $response->json()]);

            return false;
        }

        return true;
    }
}
