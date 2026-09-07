<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingIntentParseTest extends TestCase
{
    use RefreshDatabase;

    private const LOCAL = 'http://ai-service.test/generate';

    private const OPENAI = 'https://api.openai.com/v1/chat/completions';

    private const HOURS = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

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
            'ai.booking_intent.daily_ai_calls_free' => 40,
            'ai.booking_intent.daily_ai_calls_pro' => 400,
            'openai.api_key' => 'test-key',
            'openai.base_url' => 'https://api.openai.com/v1',
        ]);

        Cache::flush();
        // A Monday, mid-morning.
        Carbon::setTestNow('2026-09-07 08:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_known_client_and_one_service_fill_everything_without_asking_a_model(): void
    {
        Http::fake();

        $master = $this->master();
        $this->service($master, 'Маникюр с покрытием', 2800, 90);
        $this->clientWithVisit($master, 'Марина Белова', '+79161234502');

        $response = $this->parse($master, 'марина завтра маникюр в 3 дня')->assertOk();

        $response->assertJsonPath('engine', 'rules');
        $response->assertJsonPath('provider', null);
        $response->assertJsonPath('filled.scheduled_at', '2026-09-08T15:00');
        $response->assertJsonPath('filled.client.name', 'Марина Белова');
        $response->assertJsonPath('filled.services.0.name', 'Маникюр с покрытием');
        $this->assertSame([], $response->json('choices'));

        Http::assertNothingSent();
    }

    public function test_the_parser_still_works_with_ai_switched_off(): void
    {
        config(['ai.routes.booking_intent' => 'off']);
        Http::fake();

        $master = $this->master();
        $this->service($master, 'Наращивание ногтей', 4500, 180);
        $this->clientWithVisit($master, 'Марина Белова', '+79161234502');

        $response = $this->parse($master, 'марина завтра ногти в 3 дня')->assertOk();

        $response->assertJsonPath('engine', 'rules');
        $response->assertJsonPath('provider', null);
        $response->assertJsonPath('filled.scheduled_at', '2026-09-08T15:00');
        // «ногти» reaches «ногтей» on a shared four-letter stem, with no model.
        $response->assertJsonPath('filled.services.0.name', 'Наращивание ногтей');
        $response->assertJsonPath('filled.client.name', 'Марина Белова');

        Http::assertNothingSent();
    }

    public function test_two_clients_with_the_same_name_become_a_choice_not_a_guess(): void
    {
        config(['ai.routes.booking_intent' => 'off']);
        Http::fake();

        $master = $this->master();
        $this->service($master, 'Маникюр', 2800, 90);
        $this->clientWithVisit($master, 'Марина Белова', '+79161234502');
        $this->clientWithVisit($master, 'Марина Котова', '+79161234503');

        $response = $this->parse($master, 'марина завтра маникюр в 15:00')->assertOk();

        $response->assertJsonPath('filled.client', null);
        $response->assertJsonPath('choices.0.field', 'client');
        $this->assertCount(3, $response->json('choices.0.options'), 'two clients plus "new"');
        $this->assertSame('new', $response->json('choices.0.options.2.value'));
    }

    public function test_an_unknown_name_becomes_a_new_client_from_the_phrase(): void
    {
        config(['ai.routes.booking_intent' => 'off']);
        Http::fake();

        $master = $this->master();
        $this->service($master, 'Маникюр', 2800, 90);

        $response = $this->parse($master, 'светлана завтра маникюр в 15:00')->assertOk();

        $response->assertJsonPath('filled.client', null);
        $response->assertJsonPath('filled.new_client.name', 'Светлана');
        $this->assertContains('client_phone', $response->json('unresolved'));
    }

    public function test_a_card_only_client_is_offered_as_a_new_client_not_as_a_selection(): void
    {
        config(['ai.routes.booking_intent' => 'off']);
        Http::fake();

        $master = $this->master();
        $this->service($master, 'Маникюр', 2800, 90);

        // A card the master typed in herself: no account, so no id. Feeding it to
        // setCreateClientSelection() would blank the form it had just filled in.
        \App\Models\Client::query()->create([
            'user_id' => $master->id,
            'name' => 'Оксана Лаврова',
            'phone' => '+79160000077',
        ]);

        $response = $this->parse($master, 'оксана завтра маникюр в 15:00')->assertOk();

        $response->assertJsonPath('filled.client', null);
        $response->assertJsonPath('filled.new_client.name', 'Оксана Лаврова');
        $response->assertJsonPath('filled.new_client.phone', '+79160000077');
    }

    public function test_a_lowercase_name_finds_the_client_on_postgres(): void
    {
        config(['ai.routes.booking_intent' => 'off']);
        Http::fake();

        $master = $this->master();
        $this->service($master, 'Маникюр', 2800, 90);
        $this->clientWithVisit($master, 'Марина Белова', '+79161234502');

        // `like` is case sensitive on Postgres and a dictated phrase is lowercase.
        $this->parse($master, 'марина завтра маникюр в 15:00')
            ->assertOk()
            ->assertJsonPath('filled.client.name', 'Марина Белова');
    }

    public function test_a_phone_in_the_phrase_wins_over_the_name(): void
    {
        config(['ai.routes.booking_intent' => 'off']);
        Http::fake();

        $master = $this->master();
        $this->service($master, 'Маникюр', 2800, 90);
        $this->clientWithVisit($master, 'Марина Белова', '+79161234502');
        $this->clientWithVisit($master, 'Марина Котова', '+79161234503');

        $response = $this->parse($master, 'марина +7 916 123-45-02 завтра маникюр в 15:00')->assertOk();

        $response->assertJsonPath('filled.client.name', 'Марина Белова');
        $this->assertSame([], $response->json('choices'));
    }

    public function test_openai_narrows_an_ambiguous_service_word_to_one_id(): void
    {
        $master = $this->master();
        $manicure = $this->service($master, 'Маникюр с покрытием', 2800, 90);
        $this->service($master, 'Наращивание ногтей', 4500, 180);
        $this->service($master, 'Ремонт ногтя', 500, 20);
        $this->clientWithVisit($master, 'Марина Белова', '+79161234502');

        config(['ai.routes.booking_intent' => 'openai_only']);
        Http::fake([self::OPENAI => $this->openAiJson([
            'understood' => true,
            'service_ids' => [$manicure->id],
        ])]);

        $response = $this->parse($master, 'марина завтра ногти в 3 дня')->assertOk();

        $response->assertJsonPath('engine', 'ai');
        $response->assertJsonPath('provider', 'openai');
        $this->assertCount(1, $response->json('filled.services'));
        $response->assertJsonPath('filled.services.0.name', 'Маникюр с покрытием');
        $this->assertSame([], $response->json('choices'));
    }

    public function test_a_service_id_the_model_invented_is_ignored(): void
    {
        $master = $this->master();
        $this->service($master, 'Наращивание ногтей', 4500, 180);
        $this->service($master, 'Ремонт ногтя', 500, 20);
        $this->clientWithVisit($master, 'Марина Белова', '+79161234502');

        $stranger = $this->service($this->master(), 'Чужая услуга', 100, 10);

        config(['ai.routes.booking_intent' => 'openai_only']);
        Http::fake([self::OPENAI => $this->openAiJson([
            'understood' => true,
            'service_ids' => [$stranger->id, 999999],
        ])]);

        $response = $this->parse($master, 'марина завтра ногти в 3 дня')->assertOk();

        $this->assertSame([], $response->json('filled.services'));
        $response->assertJsonPath('choices.0.field', 'services');
    }

    public function test_a_local_answer_missing_the_required_key_falls_through_to_the_rules_result(): void
    {
        $master = $this->master();
        $this->service($master, 'Наращивание ногтей', 4500, 180);
        $this->service($master, 'Ремонт ногтя', 500, 20);
        $this->clientWithVisit($master, 'Марина Белова', '+79161234502');

        config(['ai.routes.booking_intent' => 'local_only']);
        // No `understood`, so AiGateway throws the whole answer away.
        Http::fake([self::LOCAL => Http::response(['text' => '{"service_ids": [1]}'], 200)]);

        $response = $this->parse($master, 'марина завтра ногти в 3 дня')->assertOk();

        $response->assertJsonPath('engine', 'rules');
        $response->assertJsonPath('choices.0.field', 'services');
    }

    public function test_the_prompt_stays_inside_the_local_budget(): void
    {
        $master = $this->master();

        for ($i = 1; $i <= 80; $i++) {
            $this->service($master, 'Услуга номер ' . $i . ' длинное название', 1000 + $i, 60);
        }

        config(['ai.routes.booking_intent' => 'local_only']);
        Http::fake([self::LOCAL => Http::response(['text' => '{"understood": false}'], 200)]);

        $this->parse($master, 'светлана завтра в 15:00 что-нибудь')->assertOk();

        Http::assertSent(function ($request) {
            return mb_strlen((string) $request['prompt']) <= (int) config('ai.local.max_prompt_chars');
        });
    }

    public function test_a_busy_slot_is_reported_without_blocking_the_form(): void
    {
        config(['ai.routes.booking_intent' => 'off']);
        Http::fake();

        $master = $this->master();
        $service = $this->service($master, 'Маникюр', 2800, 90);
        $this->clientWithVisit($master, 'Марина Белова', '+79161234502');
        $this->booking($master, '2026-09-08 15:00', 90, 'Юлия Титова');

        $response = $this->parse($master, 'марина завтра маникюр в 3 дня')->assertOk();

        $response->assertJsonPath('availability.status', 'busy');
        $this->assertStringContainsString('В 15:00 занято', $response->json('availability.message'));
        $this->assertNotEmpty($response->json('availability.suggestions'));
        // Advisory only: masters double-book on purpose.
        $response->assertJsonPath('filled.scheduled_at', '2026-09-08T15:00');
        $response->assertJsonPath('filled.services.0.id', $service->id);
    }

    public function test_an_empty_schedule_never_claims_the_day_is_closed(): void
    {
        config(['ai.routes.booking_intent' => 'off']);
        Http::fake();

        $master = User::factory()->create(['timezone' => 'Europe/Moscow']);
        $this->service($master, 'Маникюр', 2800, 90);
        $this->clientWithVisit($master, 'Марина Белова', '+79161234502');
        $this->booking($master, '2026-09-08 15:00', 90, 'Юлия Титова');

        $response = $this->parse($master, 'марина завтра маникюр в 3 дня')->assertOk();

        $response->assertJsonPath('availability.status', 'busy');
        $this->assertSame([], $response->json('availability.suggestions'));
    }

    public function test_the_endpoint_is_available_without_a_paid_plan(): void
    {
        config(['ai.routes.booking_intent' => 'off']);
        Http::fake();

        $master = $this->master();
        $this->service($master, 'Маникюр', 2800, 90);

        $this->parse($master, 'светлана завтра маникюр в 15:00')->assertOk();
    }

    public function test_the_same_phrase_twice_asks_the_model_once(): void
    {
        $master = $this->master();
        $this->service($master, 'Наращивание ногтей', 4500, 180);
        $this->service($master, 'Ремонт ногтя', 500, 20);
        $this->clientWithVisit($master, 'Марина Белова', '+79161234502');

        config(['ai.routes.booking_intent' => 'openai_only']);
        Http::fake([self::OPENAI => $this->openAiJson(['understood' => true, 'service_ids' => []])]);

        $this->parse($master, 'марина завтра ногти в 3 дня')->assertOk();
        $this->parse($master, 'марина в пятницу ногти в 3 дня')->assertOk();

        // Same residue, same price list: one cache entry between them.
        Http::assertSentCount(1);
    }

    public function test_the_daily_budget_degrades_to_rules_instead_of_failing(): void
    {
        $master = $this->master();
        $this->service($master, 'Наращивание ногтей', 4500, 180);
        $this->service($master, 'Ремонт ногтя', 500, 20);
        $this->clientWithVisit($master, 'Марина Белова', '+79161234502');

        config(['ai.routes.booking_intent' => 'openai_only', 'ai.booking_intent.daily_ai_calls_free' => 2]);
        Cache::put('orders:intent:ai:' . $master->id . ':' . Carbon::now()->toDateString(), 2, now()->endOfDay());
        Http::fake([self::OPENAI => $this->openAiJson(['understood' => true, 'service_ids' => []])]);

        $response = $this->parse($master, 'марина завтра ногти в 3 дня')->assertOk();

        $response->assertJsonPath('engine', 'rules');
        $response->assertJsonPath('meta.ai_calls_left', 0);
        Http::assertNothingSent();
    }

    public function test_a_paid_plan_gets_the_larger_budget(): void
    {
        $master = $this->master();
        $this->service($master, 'Наращивание ногтей', 4500, 180);
        $this->service($master, 'Ремонт ногтя', 500, 20);
        $this->clientWithVisit($master, 'Марина Белова', '+79161234502');

        $plan = Plan::create(['name' => 'pro', 'price' => 1000, 'duration_days' => 30]);
        $master->plans()->attach($plan->id, ['ends_at' => Carbon::now()->addMonth()]);

        config(['ai.routes.booking_intent' => 'openai_only', 'ai.booking_intent.daily_ai_calls_free' => 1]);
        Cache::put('orders:intent:ai:' . $master->id . ':' . Carbon::now()->toDateString(), 5, now()->endOfDay());
        Http::fake([self::OPENAI => $this->openAiJson(['understood' => true, 'service_ids' => []])]);

        $this->parse($master, 'марина завтра ногти в 3 дня')
            ->assertOk()
            ->assertJsonPath('engine', 'ai');
    }

    public function test_a_client_belonging_to_another_master_is_never_returned(): void
    {
        config(['ai.routes.booking_intent' => 'off']);
        Http::fake();

        $mine = $this->master();
        $theirs = $this->master();
        $this->service($mine, 'Маникюр', 2800, 90);
        $this->clientWithVisit($theirs, 'Марина Белова', '+79161234502');

        $response = $this->parse($mine, 'марина завтра маникюр в 15:00')->assertOk();

        $response->assertJsonPath('filled.client', null);
        $response->assertJsonPath('filled.new_client.name', 'Марина');
    }

    public function test_an_empty_phrase_is_rejected_in_russian(): void
    {
        $master = $this->master();
        Sanctum::actingAs($master);

        $this->postJson('/api/v1/orders/parse-intent', ['text' => ''])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonPath('error.fields.text.0', 'Напишите, кого и на что записать.');
    }

    // ----------------------------------------------------------------- helpers

    private function parse(User $master, string $text, ?string $date = null): TestResponse
    {
        Sanctum::actingAs($master);

        return $this->postJson('/api/v1/orders/parse-intent', array_filter([
            'text' => $text,
            'date' => $date,
        ]));
    }

    private function master(): User
    {
        $master = User::factory()->create(['timezone' => 'Europe/Moscow']);

        Setting::create([
            'user_id' => $master->id,
            'work_days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'work_hours' => array_fill_keys(
                ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
                self::HOURS,
            ),
        ]);

        return $master;
    }

    private function service(User $master, string $name, float $price, int $minutes): Service
    {
        return Service::create([
            'user_id' => $master->id,
            'name' => $name,
            'base_price' => $price,
            'cost' => 0,
            'duration_min' => $minutes,
        ]);
    }

    /**
     * A client is only "selectable" once an order links her to this master.
     */
    private function clientWithVisit(User $master, string $name, string $phone): User
    {
        $client = User::factory()->create(['name' => $name, 'phone' => $phone]);

        Order::query()->create([
            'master_id' => $master->id,
            'client_id' => $client->id,
            'services' => [],
            'duration_forecast' => 60,
            'scheduled_at' => Carbon::parse('2026-08-20 12:00', 'Europe/Moscow'),
            'total_price' => 1000,
            'status' => 'completed',
            'source' => 'manual',
        ]);

        return $client;
    }

    private function booking(User $master, string $at, int $minutes, string $clientName): Order
    {
        return Order::query()->create([
            'master_id' => $master->id,
            'client_id' => User::factory()->create(['name' => $clientName])->id,
            'services' => [],
            'duration_forecast' => $minutes,
            'scheduled_at' => Carbon::parse($at, 'Europe/Moscow'),
            'total_price' => 1000,
            'status' => 'confirmed',
            'source' => 'manual',
        ]);
    }

    private function openAiJson(array $payload): \Closure
    {
        return fn () => Http::response([
            'choices' => [['message' => ['content' => json_encode($payload, JSON_UNESCAPED_UNICODE)]]],
        ], 200);
    }
}
