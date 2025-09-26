<?php

namespace App\Services;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use JsonSerializable;
use Stringable;

class OpenAIService extends BaseService
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    public function respond(string $prompt, mixed $context = null, array $options = []): array
    {
        $messages = $this->buildMessages($prompt, $context, $options);

        return $this->createChatCompletion($messages, $options);
    }

    public function createChatCompletion(array $messages, array $options = []): array
    {
        $payload = [
            'model' => $options['model'] ?? config('openai.default_model'),
            'temperature' => $this->resolveTemperature($options['temperature'] ?? config('openai.default_temperature')),
            'messages' => $this->normalizeMessages($messages),
        ];

        $payload = array_filter($payload, static fn ($value) => !is_null($value));

        if ($responseFormat = $this->normalizeResponseFormat($options['response_format'] ?? config('openai.response_format'))) {
            $payload['response_format'] = $responseFormat;
        }

        if (($maxTokens = $options['max_tokens'] ?? config('openai.max_tokens')) !== null) {
            $payload['max_tokens'] = (int) $maxTokens;
        }

        if (!empty($options['additional_parameters']) && is_array($options['additional_parameters'])) {
            $payload = array_merge($payload, $options['additional_parameters']);
        }

        try {
            $response = $this->client()->post('/chat/completions', $payload)->throw()->json();
        } catch (RequestException $exception) {
            $error = $exception->response?->json('error') ?? [];

            $this->throwError(
                'openai.request_failed',
                $error['message'] ?? 'Unable to communicate with OpenAI.',
                array_filter([
                    'type' => $error['type'] ?? null,
                    'param' => $error['param'] ?? null,
                    'code' => $error['code'] ?? null,
                ]),
                $exception->response?->status() ?? 500,
            );
        }

        return [
            'content' => Arr::get($response, 'choices.0.message.content'),
            'usage' => Arr::get($response, 'usage', []),
            'raw' => $response,
        ];
    }

    protected function buildMessages(string $prompt, mixed $context, array $options): array
    {
        $messages = [];

        if ($systemMessage = $options['system_message'] ?? config('openai.system_message')) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemMessage,
            ];
        }

        if ($contextMessage = $this->contextToMessage($context, $options)) {
            $messages[] = $contextMessage;
        }

        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        if (!empty($options['messages']) && is_array($options['messages'])) {
            $messages = array_merge($messages, $this->normalizeMessages($options['messages']));
        }

        return $messages;
    }

    protected function contextToMessage(mixed $context, array $options): ?array
    {
        if ($context === null || $context === '' || $context === []) {
            return null;
        }

        $role = $options['context_role'] ?? 'user';
        $content = $this->stringifyContext($context);

        if ($prefix = $options['context_prefix'] ?? config('openai.context_prefix')) {
            $content = trim($prefix . "\n\n" . $content);
        }

        return [
            'role' => $role,
            'content' => $content,
        ];
    }

    protected function stringifyContext(mixed $context): string
    {
        if (is_string($context)) {
            return $context;
        }

        if ($context instanceof Stringable) {
            return (string) $context;
        }

        if ($context instanceof Arrayable) {
            $context = $context->toArray();
        } elseif ($context instanceof JsonSerializable) {
            $context = $context->jsonSerialize();
        }

        if (is_array($context)) {
            return $this->encodeJson($context);
        }

        if (is_object($context)) {
            return $this->encodeJson($context);
        }

        if (is_bool($context)) {
            return $context ? 'true' : 'false';
        }

        if ($context === null) {
            return '';
        }

        return (string) $context;
    }

    protected function normalizeMessages(array $messages): array
    {
        return array_map(function ($message) {
            if (!is_array($message)) {
                $this->throwError('openai.invalid_message', 'Each message must be an associative array.');
            }

            if (!isset($message['role'], $message['content'])) {
                $this->throwError('openai.invalid_message_structure', 'Message array must contain role and content keys.');
            }

            $normalized = [
                'role' => $message['role'],
                'content' => $this->stringifyContext($message['content']),
            ];

            if (isset($message['name'])) {
                $normalized['name'] = $message['name'];
            }

            if (isset($message['metadata']) && is_array($message['metadata'])) {
                $normalized['metadata'] = $message['metadata'];
            }

            return $normalized;
        }, $messages);
    }

    protected function normalizeResponseFormat(mixed $responseFormat): ?array
    {
        if ($responseFormat === null || $responseFormat === '') {
            return null;
        }

        if (is_string($responseFormat)) {
            return ['type' => $responseFormat];
        }

        if (is_array($responseFormat)) {
            return $responseFormat;
        }

        $this->throwError('openai.invalid_response_format', 'Response format must be a string or array.');

        return null;
    }

    protected function encodeJson(mixed $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return $encoded === false ? print_r($value, true) : $encoded;
    }

    protected function resolveTemperature(mixed $temperature): ?float
    {
        if ($temperature === null || $temperature === '') {
            return null;
        }

        return (float) $temperature;
    }

    protected function client(): PendingRequest
    {
        $apiKey = config('openai.api_key');

        if (blank($apiKey)) {
            $this->throwError('openai.missing_api_key', 'OpenAI API key is not configured.');
        }

        $baseUrl = rtrim((string) config('openai.base_url'), '/');

        return $this->http
            ->baseUrl($baseUrl)
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('openai.timeout', 60));
    }
}
