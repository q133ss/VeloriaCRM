<?php

namespace Tests\Feature;

use App\Services\Ai\AiGateway;
use App\Services\Ai\LocalAiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiGatewayTest extends TestCase
{
    private const LOCAL = 'http://ai-service.test/generate';
    private const OPENAI = 'https://api.openai.com/v1/chat/completions';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.local.enabled' => true,
            'ai.local.url' => 'http://ai-service.test',
            'ai.local.timeout' => 5,
            'ai.local.max_prompt_chars' => 2000,
            'ai.local.cooldown' => 120,
            'ai.default_route' => 'local_first',
            'ai.routes' => [],
            'openai.api_key' => 'test-key',
            'openai.base_url' => 'https://api.openai.com/v1',
        ]);

        Cache::flush();
    }

    public function test_local_provider_answers_and_openai_is_never_called(): void
    {
        Http::fake([
            self::LOCAL => Http::response(['text' => 'Здравствуйте, давно вас не было.', 'source' => 'duckduckgo'], 200),
            self::OPENAI => Http::response([], 500),
        ]);

        $gateway = app(AiGateway::class);
        $text = $gateway->text('outreach_message', 'Напиши приветствие');

        $this->assertSame('Здравствуйте, давно вас не было.', $text);
        $this->assertSame(AiGateway::LOCAL, $gateway->lastProvider());
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'openai.com'));
    }

    public function test_openai_takes_over_when_the_local_service_returns_502(): void
    {
        Http::fake([
            self::LOCAL => Http::response(['detail' => 'no text'], 502),
            self::OPENAI => Http::response([
                'choices' => [['message' => ['content' => 'Текст от OpenAI']]],
            ], 200),
        ]);

        $gateway = app(AiGateway::class);

        $this->assertSame('Текст от OpenAI', $gateway->text('outreach_message', 'Напиши приветствие'));
        $this->assertSame(AiGateway::OPENAI, $gateway->lastProvider());
    }

    public function test_a_502_leaves_the_local_service_in_rotation(): void
    {
        // 502 is the service saying "both my sources came up empty" — it is
        // alive and worth asking again on the next request.
        Http::fake([
            self::LOCAL => Http::response([], 502),
            self::OPENAI => Http::response(['choices' => [['message' => ['content' => 'ok']]]], 200),
        ]);

        app(AiGateway::class)->text('outreach_message', 'Привет');

        $this->assertFalse(app(LocalAiService::class)->cooling());
    }

    public function test_a_500_puts_the_local_service_into_cooldown(): void
    {
        Http::fake([
            self::LOCAL => Http::response([], 500),
            self::OPENAI => Http::response(['choices' => [['message' => ['content' => 'ok']]]], 200),
        ]);

        app(AiGateway::class)->text('outreach_message', 'Привет');

        $this->assertTrue(app(LocalAiService::class)->cooling());
    }

    public function test_a_cooling_local_service_is_skipped_without_an_http_call(): void
    {
        Cache::put('ai:local:down', true, now()->addMinutes(2));

        Http::fake([
            self::LOCAL => Http::response(['text' => 'never reached'], 200),
            self::OPENAI => Http::response(['choices' => [['message' => ['content' => 'Текст от OpenAI']]]], 200),
        ]);

        $gateway = app(AiGateway::class);

        $this->assertSame('Текст от OpenAI', $gateway->text('outreach_message', 'Привет'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'ai-service.test'));
    }

    public function test_an_oversized_prompt_never_reaches_the_local_service(): void
    {
        Http::fake([
            self::LOCAL => Http::response(['text' => 'never reached'], 200),
            self::OPENAI => Http::response(['choices' => [['message' => ['content' => 'Длинный ответ']]]], 200),
        ]);

        $gateway = app(AiGateway::class);
        $text = $gateway->text('analytics_insights', str_repeat('а', 2500));

        $this->assertSame('Длинный ответ', $text);
        $this->assertSame(AiGateway::OPENAI, $gateway->lastProvider());
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'ai-service.test'));
    }

    public function test_context_reaches_the_local_service_as_flat_prose(): void
    {
        Http::fake([self::LOCAL => Http::response(['text' => 'ok'], 200)]);

        app(AiGateway::class)->text('outreach_message', 'Напиши сообщение', [
            'client_name' => 'Мария',
            'days_since' => 45,
            'free_slots' => ['12:00', '15:30'],
            'usual_service' => null,
        ]);

        Http::assertSent(function ($request) {
            $prompt = $request->data()['prompt'];

            return str_contains($prompt, 'Напиши сообщение')
                && str_contains($prompt, 'client_name: Мария')
                && str_contains($prompt, 'free_slots: 12:00, 15:30')
                && ! str_contains($prompt, 'usual_service')
                && ! str_contains($prompt, '{');
        });
    }

    public function test_json_is_parsed_out_of_a_fenced_local_answer(): void
    {
        $fenced = "Вот результат:\n```json\n{\"title\": \"Идея\", \"idea\": \"Текст\"}\n```\nГотово.";

        Http::fake([self::LOCAL => Http::response(['text' => $fenced], 200)]);

        $decoded = app(AiGateway::class)->json('daily_post_idea', 'Придумай идею', [], [
            'name' => 'daily_post_idea',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'idea' => ['type' => 'string'],
                ],
                'required' => ['title', 'idea'],
            ],
        ]);

        $this->assertSame(['title' => 'Идея', 'idea' => 'Текст'], $decoded);
    }

    public function test_a_local_answer_missing_a_required_key_falls_through_to_openai(): void
    {
        Http::fake([
            self::LOCAL => Http::response(['text' => '{"title": "Идея"}'], 200),
            self::OPENAI => Http::response([
                'choices' => [['message' => ['content' => '{"title":"Идея","idea":"Полный текст"}']]],
            ], 200),
        ]);

        $gateway = app(AiGateway::class);

        $decoded = $gateway->json('daily_post_idea', 'Придумай идею', [], [
            'name' => 'daily_post_idea',
            'schema' => [
                'type' => 'object',
                'properties' => ['title' => ['type' => 'string'], 'idea' => ['type' => 'string']],
                'required' => ['title', 'idea'],
            ],
        ]);

        $this->assertSame('Полный текст', $decoded['idea']);
        $this->assertSame(AiGateway::OPENAI, $gateway->lastProvider());
    }

    public function test_prose_instead_of_json_counts_as_a_miss(): void
    {
        Http::fake([
            self::LOCAL => Http::response(['text' => 'Конечно! Вот моя идея на сегодня.'], 200),
            self::OPENAI => Http::response([], 500),
        ]);

        $decoded = app(AiGateway::class)->json('daily_post_idea', 'Придумай идею', [], [
            'name' => 'daily_post_idea',
            'schema' => ['type' => 'object', 'properties' => [], 'required' => ['title']],
        ]);

        $this->assertNull($decoded);
    }

    public function test_the_route_decides_which_provider_goes_first(): void
    {
        config(['ai.routes.order_recommendations' => 'openai_first']);

        Http::fake([
            self::LOCAL => Http::response(['text' => 'Локальный'], 200),
            self::OPENAI => Http::response(['choices' => [['message' => ['content' => 'Платный']]]], 200),
        ]);

        $gateway = app(AiGateway::class);

        $this->assertSame('Платный', $gateway->text('order_recommendations', 'Подбери услуги'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'ai-service.test'));
    }

    public function test_the_only_option_pins_one_provider_whatever_the_route_says(): void
    {
        Http::fake([
            self::LOCAL => Http::response([], 502),
            self::OPENAI => Http::response(['choices' => [['message' => ['content' => 'Платный']]]], 200),
        ]);

        $gateway = app(AiGateway::class);
        $text = $gateway->text('outreach_message', 'Привет', null, ['only' => AiGateway::LOCAL]);

        $this->assertNull($text);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'openai.com'));
    }

    public function test_the_off_route_generates_nothing_at_all(): void
    {
        config(['ai.routes.outreach_message' => 'off']);

        Http::fake();

        $gateway = app(AiGateway::class);

        $this->assertFalse($gateway->enabled('outreach_message'));
        $this->assertNull($gateway->text('outreach_message', 'Привет'));
        Http::assertNothingSent();
    }
}
