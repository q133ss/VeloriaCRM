<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Plan;
use App\Models\User;
use App\Services\ClientOutreachService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClientOutreachServiceTest extends TestCase
{
    use RefreshDatabase;

    private const LOCAL = 'http://ai-service.test/generate';
    private const OPENAI = 'https://api.openai.com/v1/chat/completions';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.local.enabled' => true,
            'ai.local.url' => 'http://ai-service.test',
            'ai.local.sync_timeout' => 5,
            'ai.local.max_prompt_chars' => 2000,
            'ai.local.cooldown' => 120,
            'ai.default_route' => 'local_first',
            'ai.routes' => [],
            'openai.api_key' => 'test-key',
            'openai.base_url' => 'https://api.openai.com/v1',
        ]);
    }

    public function test_a_free_generation_costs_nothing_when_the_local_service_answers(): void
    {
        Http::fake([
            self::LOCAL => Http::response(['text' => 'Мария, здравствуйте! Пора обновить маникюр.'], 200),
        ]);

        [$master, $card] = $this->master();

        $result = app(ClientOutreachService::class)->draft($master, $card);

        $this->assertSame('ai', $result['source']);
        $this->assertSame('Мария, здравствуйте! Пора обновить маникюр.', $result['text']);
        // The free monthly allowance is for paid calls only, so it is untouched.
        $this->assertSame(ClientOutreachService::FREE_MONTHLY_LIMIT, $result['remaining']);
        $this->assertSame(0, (int) Cache::get($this->usageKey($master), 0));
    }

    public function test_falling_back_to_openai_spends_one_free_generation(): void
    {
        Http::fake([
            self::LOCAL => Http::response([], 502),
            self::OPENAI => Http::response([
                'choices' => [['message' => ['content' => 'Текст от платного провайдера']]],
            ], 200),
        ]);

        [$master, $card] = $this->master();

        $result = app(ClientOutreachService::class)->draft($master, $card);

        $this->assertSame('ai', $result['source']);
        $this->assertSame(ClientOutreachService::FREE_MONTHLY_LIMIT - 1, $result['remaining']);
        $this->assertSame(1, (int) Cache::get($this->usageKey($master), 0));
    }

    public function test_an_exhausted_allowance_still_gets_a_real_text_from_the_local_service(): void
    {
        Http::fake([
            self::LOCAL => Http::response(['text' => 'Бесплатный, но живой текст.'], 200),
            self::OPENAI => Http::response(['choices' => [['message' => ['content' => 'платно']]]], 200),
        ]);

        [$master, $card] = $this->master();
        Cache::put($this->usageKey($master), ClientOutreachService::FREE_MONTHLY_LIMIT, now()->addDay());

        $result = app(ClientOutreachService::class)->draft($master, $card);

        $this->assertSame('ai', $result['source']);
        $this->assertSame('Бесплатный, но живой текст.', $result['text']);
        $this->assertSame(0, $result['remaining']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'openai.com'));
    }

    public function test_an_exhausted_allowance_with_no_local_service_falls_back_to_the_template(): void
    {
        Http::fake([
            self::LOCAL => Http::response([], 502),
            self::OPENAI => Http::response(['choices' => [['message' => ['content' => 'платно']]]], 200),
        ]);

        [$master, $card] = $this->master();
        Cache::put($this->usageKey($master), ClientOutreachService::FREE_MONTHLY_LIMIT, now()->addDay());

        $result = app(ClientOutreachService::class)->draft($master, $card);

        $this->assertSame('limit', $result['source']);
        $this->assertStringContainsString('Мария', $result['text']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'openai.com'));
    }

    public function test_a_paid_plan_is_never_metered(): void
    {
        Http::fake([
            self::LOCAL => Http::response([], 502),
            self::OPENAI => Http::response([
                'choices' => [['message' => ['content' => 'Текст от платного провайдера']]],
            ], 200),
        ]);

        [$master, $card] = $this->master();
        $plan = Plan::query()->create(['name' => 'pro', 'price' => 999]);
        $master->plans()->attach($plan->id, ['ends_at' => Carbon::now()->addMonth()]);

        $result = app(ClientOutreachService::class)->draft($master, $card);

        $this->assertSame('ai', $result['source']);
        $this->assertNull($result['remaining']);
        $this->assertSame(0, (int) Cache::get($this->usageKey($master), 0));
    }

    public function test_both_providers_failing_still_produces_something_sendable(): void
    {
        Http::fake([
            self::LOCAL => Http::response([], 502),
            self::OPENAI => Http::response([], 500),
        ]);

        [$master, $card] = $this->master();

        $result = app(ClientOutreachService::class)->draft($master, $card, [
            'free_day' => 'Завтра',
            'free_slots' => ['12:00', '15:30'],
        ]);

        $this->assertSame('template', $result['source']);
        $this->assertStringContainsString('Мария', $result['text']);
        $this->assertStringContainsString('12:00', $result['text']);
    }

    /**
     * @return array{0: User, 1: Client}
     */
    private function master(): array
    {
        $master = User::factory()->create();
        $client = User::factory()->create(['name' => 'Мария']);

        $card = Client::query()->create([
            'user_id' => $master->id,
            'client_user_id' => $client->id,
            'name' => 'Мария',
            'phone' => '+79990000001',
        ]);

        return [$master, $card];
    }

    private function usageKey(User $master): string
    {
        return sprintf('outreach:usage:%d:%s', $master->id, Carbon::now()->format('Y-m'));
    }
}
