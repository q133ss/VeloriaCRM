<?php

namespace App\Services\Ai;

use App\Services\OpenAIService;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

/**
 * The one door to text generation.
 *
 * Behind it are two providers with opposite trade-offs: the local ai_service is
 * free but slow and answers in prose, OpenAI is fast and can be held to a JSON
 * schema but costs money per call. Callers should not have to care which one
 * answered, so they get a string or an array, or null — and null is not an
 * error, it is the cue to fall back to whatever the caller had written by hand.
 *
 * Which provider goes first is config, not code (`config/ai.php`), so a task
 * can be moved between them without touching the call site.
 */
class AiGateway
{
    public const LOCAL = 'local';
    public const OPENAI = 'openai';

    private ?string $lastProvider = null;

    public function __construct(
        private readonly LocalAiService $local,
        private readonly OpenAIService $openAI,
    ) {
    }

    /**
     * Which provider produced the last successful answer, or null if none did.
     * Callers that meter paid usage need this; nobody else should care.
     */
    public function lastProvider(): ?string
    {
        return $this->lastProvider;
    }

    public function enabled(?string $task = null, array $options = []): bool
    {
        foreach ($this->providers($task, $options) as $provider) {
            if ($this->available($provider)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Plain text out. `$context` is the facts the wording should rest on.
     *
     * Options: max_tokens, temperature, timeout (seconds, for the local
     * provider), local_context (a compact stand-in for $context when the full
     * one would not fit in the local prompt budget), only (pin this one call to
     * a single provider, whatever the configured route says).
     */
    public function text(string $task, string $prompt, mixed $context = null, array $options = []): ?string
    {
        $this->lastProvider = null;

        foreach ($this->providers($task, $options) as $provider) {
            if (! $this->available($provider)) {
                continue;
            }

            $text = $provider === self::LOCAL
                ? $this->local->generate(
                    $this->flatten($prompt, $options['local_context'] ?? $context),
                    $options['timeout'] ?? null,
                )
                : $this->openAiText($prompt, $context, $options);

            if (filled($text)) {
                $this->lastProvider = $provider;

                return $text;
            }
        }

        return null;
    }

    /**
     * Structured output. `$schema` is the same ['name' => ..., 'schema' => ...]
     * array the OpenAI json_schema response format expects, so existing call
     * sites hand over what they already had.
     *
     * The local provider has no schema support, so it is asked in words and its
     * answer is parsed defensively; anything unparseable counts as a miss and
     * the next provider gets a turn.
     */
    public function json(string $task, string $prompt, mixed $context, array $schema, array $options = []): ?array
    {
        $this->lastProvider = null;

        foreach ($this->providers($task, $options) as $provider) {
            if (! $this->available($provider)) {
                continue;
            }

            $decoded = $provider === self::LOCAL
                ? $this->localJson($prompt, $options['local_context'] ?? $context, $schema, $options)
                : $this->openAiJson($prompt, $context, $schema, $options);

            if ($decoded !== null) {
                $this->lastProvider = $provider;

                return $decoded;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function providers(?string $task, array $options = []): array
    {
        if ($only = $options['only'] ?? null) {
            return in_array($only, [self::LOCAL, self::OPENAI], true) ? [$only] : [];
        }

        $route = $task ? config('ai.routes.' . $task) : null;
        $route = $route ?: config('ai.default_route', 'local_first');

        return match ($route) {
            'openai_first' => [self::OPENAI, self::LOCAL],
            'local_only' => [self::LOCAL],
            'openai_only' => [self::OPENAI],
            'off' => [],
            default => [self::LOCAL, self::OPENAI],
        };
    }

    private function available(string $provider): bool
    {
        return $provider === self::LOCAL
            ? $this->local->enabled()
            : filled(config('openai.api_key'));
    }

    private function openAiText(string $prompt, mixed $context, array $options): ?string
    {
        try {
            $response = $this->openAI->respond($prompt, $context, $this->openAiOptions($options));

            return trim((string) Arr::get($response, 'content')) ?: null;
        } catch (Throwable $exception) {
            Log::warning('OpenAI text generation failed.', [
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function openAiJson(string $prompt, mixed $context, array $schema, array $options): ?array
    {
        $options = $this->openAiOptions($options);
        $options['response_format'] = [
            'type' => 'json_schema',
            'json_schema' => $schema,
        ];

        try {
            $response = $this->openAI->respond($prompt, $context, $options);
            $content = (string) Arr::get($response, 'content');

            if ($content === '') {
                return null;
            }

            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable $exception) {
            Log::warning('OpenAI structured generation failed.', [
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function localJson(string $prompt, mixed $context, array $schema, array $options): ?array
    {
        $instruction = $prompt
            . "\n\nВерни ТОЛЬКО JSON, без пояснений, без markdown и без текста вокруг."
            . "\nСтрого такой структуры:\n"
            . $this->describe((array) Arr::get($schema, 'schema', []));

        $text = $this->local->generate(
            $this->flatten($instruction, $context),
            $options['timeout'] ?? null,
        );

        if ($text === null) {
            return null;
        }

        $decoded = $this->extractJson($text);

        if ($decoded === null) {
            Log::warning('Local AI service returned unparseable JSON.', [
                'schema' => Arr::get($schema, 'name'),
            ]);

            return null;
        }

        // A partial object is worse than none: the caller would silently fill
        // the gaps with fallback values and present the mix as one answer.
        foreach ((array) Arr::get($schema, 'schema.required', []) as $key) {
            if (! array_key_exists($key, $decoded)) {
                return null;
            }
        }

        return $decoded;
    }

    /**
     * Strip whatever a chat UI wrapped the JSON in and decode the first object.
     */
    private function extractJson(string $text): ?array
    {
        $text = trim($text);
        $text = preg_replace('/^`{3}(?:json)?\s*|\s*`{3}$/mu', '', $text) ?? $text;

        $start = strpos($text, '{');

        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escaped = false;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($escaped) {
                $escaped = false;

                continue;
            }

            if ($char === '\\') {
                $escaped = true;

                continue;
            }

            if ($char === '"') {
                $inString = ! $inString;

                continue;
            }

            if ($inString) {
                continue;
            }

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;

                if ($depth === 0) {
                    try {
                        $decoded = json_decode(substr($text, $start, $i - $start + 1), true, 512, JSON_THROW_ON_ERROR);
                    } catch (JsonException) {
                        return null;
                    }

                    return is_array($decoded) ? $decoded : null;
                }
            }
        }

        return null;
    }

    /**
     * A skeleton of the expected object, in words a chat model follows.
     */
    private function describe(array $schema): string
    {
        return json_encode($this->skeleton($schema), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}';
    }

    private function skeleton(array $schema): mixed
    {
        return match (Arr::get($schema, 'type')) {
            'object' => collect(Arr::get($schema, 'properties', []))
                ->map(fn ($property) => $this->skeleton((array) $property))
                ->all(),
            'array' => [$this->skeleton((array) Arr::get($schema, 'items', []))],
            'integer', 'number' => 'число',
            'boolean' => 'true или false',
            default => 'строка',
        };
    }

    /**
     * One flat prompt for the local provider. Deliberately not pretty-printed
     * JSON the way OpenAIService renders context: this text is typed into a
     * chat box (or a search field), where prose reads far better than braces.
     */
    private function flatten(string $prompt, mixed $context): string
    {
        $lines = $this->contextLines($context);

        if ($lines === []) {
            return $prompt;
        }

        return $prompt . "\n\nДанные:\n" . implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    private function contextLines(mixed $context, string $prefix = ''): array
    {
        if ($context instanceof Arrayable) {
            $context = $context->toArray();
        }

        if (is_array($context)) {
            $lines = [];

            foreach ($context as $key => $value) {
                if ($value === null || $value === '' || $value === []) {
                    continue;
                }

                $label = is_int($key)
                    ? $prefix
                    : trim($prefix . ($prefix !== '' ? '.' : '') . $key);

                if (is_array($value) && $this->isFlatList($value)) {
                    $lines[] = $this->line($label, implode(', ', $value));

                    continue;
                }

                $lines = array_merge($lines, $this->contextLines($value, $label));
            }

            return $lines;
        }

        if ($context === null || $context === '') {
            return [];
        }

        if (is_bool($context)) {
            return [$this->line($prefix, $context ? 'да' : 'нет')];
        }

        if (is_scalar($context) || $context instanceof \Stringable) {
            return [$this->line($prefix, (string) $context)];
        }

        return [];
    }

    private function line(string $label, string $value): string
    {
        return $label === '' ? $value : $label . ': ' . $value;
    }

    private function isFlatList(array $value): bool
    {
        return array_is_list($value)
            && ! collect($value)->contains(fn ($item) => is_array($item) || is_object($item));
    }

    /**
     * Our own options are for this class; the rest go through untouched.
     */
    private function openAiOptions(array $options): array
    {
        return Arr::except($options, ['timeout', 'local_context', 'only']);
    }
}
