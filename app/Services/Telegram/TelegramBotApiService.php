<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramBotApiService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUpdates(string $token, ?int $offset = null, int $timeout = 10): array
    {
        $payload = [
            'timeout' => max(0, $timeout),
            'allowed_updates' => ['message', 'callback_query'],
        ];

        if ($offset !== null) {
            $payload['offset'] = $offset;
        }

        $response = Http::timeout(max(5, $timeout + 5))
            ->asJson()
            ->post($this->endpoint($token, 'getUpdates'), $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Telegram getUpdates request failed: ' . $response->body());
        }

        $data = $response->json();

        if (! is_array($data) || ! ($data['ok'] ?? false)) {
            throw new RuntimeException('Telegram getUpdates response is invalid.');
        }

        $result = $data['result'] ?? [];

        return is_array($result) ? $result : [];
    }

    public function sendMessage(string $token, string|int $chatId, string $text, array $options = []): void
    {
        $payload = array_merge([
            'chat_id' => (string) $chatId,
            'text' => $text,
        ], $options);

        $response = Http::timeout(15)
            ->asJson()
            ->post($this->endpoint($token, 'sendMessage'), $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Telegram sendMessage request failed: ' . $response->body());
        }

        $data = $response->json();

        if (! is_array($data) || ! ($data['ok'] ?? false)) {
            throw new RuntimeException('Telegram sendMessage response is invalid.');
        }
    }

    public function answerCallbackQuery(string $token, string $queryId, ?string $text = null): void
    {
        $payload = ['callback_query_id' => $queryId];

        if ($text !== null && $text !== '') {
            $payload['text'] = $text;
        }

        $response = Http::timeout(10)
            ->asJson()
            ->post($this->endpoint($token, 'answerCallbackQuery'), $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Telegram answerCallbackQuery request failed: ' . $response->body());
        }
    }

    protected function endpoint(string $token, string $method): string
    {
        return sprintf('https://api.telegram.org/bot%s/%s', $token, $method);
    }
}
