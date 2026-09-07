<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Client for ai_service — the free text generator that drives a real browser.
 *
 * Two things about it shape this class. It never costs money, so it is worth
 * trying first; and it serves one request at a time behind a single browser,
 * so a slow or dead service must not be allowed to make every user in turn sit
 * through a full timeout. Hence the cooldown, and hence the rule that nothing
 * here ever throws: a caller that asked for a nicer sentence should get null
 * and carry on, not an exception.
 */
class LocalAiService
{
    /** Set after a failure; while it is present the service is left alone. */
    private const COOLDOWN_KEY = 'ai:local:down';

    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function enabled(): bool
    {
        return (bool) config('ai.local.enabled')
            && filled(config('ai.local.url'))
            && ! $this->cooling();
    }

    /**
     * Ask for text. Returns null whenever the answer did not arrive, for any
     * reason at all — the caller decides what to show instead.
     */
    public function generate(string $prompt, ?int $timeout = null): ?string
    {
        $prompt = trim($prompt);

        if ($prompt === '' || ! $this->enabled()) {
            return null;
        }

        // ai_service refuses longer prompts outright, so there is nothing to
        // gain from the round trip. This is also what keeps the heavy
        // analytical contexts on OpenAI without anyone classifying them by hand.
        if (mb_strlen($prompt) > (int) config('ai.local.max_prompt_chars', 2000)) {
            return null;
        }

        try {
            $response = $this->client($timeout)->post('/generate', ['prompt' => $prompt]);
        } catch (Throwable $exception) {
            // Connection refused, DNS, timeout: the service is not answering.
            $this->cool($exception->getMessage());

            return null;
        }

        if (! $response->successful()) {
            // 502 means both sources failed on a live service; 503 means it is
            // still starting. Neither is worth a cooldown on its own, but a
            // 5xx that is not 502 suggests the service itself is unwell.
            if ($response->status() !== 502) {
                $this->cool('HTTP ' . $response->status() . ': ' . $response->body());
            } else {
                Log::warning('Local AI service could not produce text.', [
                    'status' => $response->status(),
                ]);
            }

            return null;
        }

        $text = trim((string) $response->json('text'));

        return $text !== '' ? $text : null;
    }

    /**
     * Queue depth and liveness, for the ai:ping command. Null when unreachable.
     */
    public function health(): ?array
    {
        try {
            $response = $this->client(5)->get('/health');
        } catch (Throwable) {
            return null;
        }

        return $response->successful() ? $response->json() : null;
    }

    public function cooling(): bool
    {
        return (bool) Cache::get(self::COOLDOWN_KEY);
    }

    private function cool(string $reason): void
    {
        $seconds = (int) config('ai.local.cooldown', 120);

        if ($seconds > 0) {
            Cache::put(self::COOLDOWN_KEY, true, now()->addSeconds($seconds));
        }

        Log::warning('Local AI service is unavailable, backing off.', [
            'reason' => $reason,
            'cooldown_seconds' => $seconds,
        ]);
    }

    private function client(?int $timeout): PendingRequest
    {
        return $this->http
            ->baseUrl(rtrim((string) config('ai.local.url'), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout($timeout ?: (int) config('ai.local.timeout', 30));
    }
}
