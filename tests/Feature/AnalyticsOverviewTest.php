<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * «Сколько заработали и кто пришёл» — за что открывают аналитику.
 */
class AnalyticsOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'ru');
        Carbon::setTestNow('2026-05-31 18:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_visits_are_counted_from_the_bookings_this_crm_actually_writes(): void
    {
        $master = $this->master();
        $olga = $this->card($master, 'Ольга Ким');

        $this->booking($master, $olga, '2026-05-05 10:00', 'completed');
        $this->booking($master, $olga, '2026-05-12 10:00', 'confirmed');
        $this->booking($master, $olga, '2026-05-19 10:00', 'cancelled');

        $summary = $this->overview()['data']['summary'];

        // Counted from `orders`; it used to count `appointments`, a table only
        // the client portal writes to, and so reported nought all month.
        $this->assertSame(2, $summary['transactions']['current']);
    }

    public function test_a_cancelled_and_a_missed_booking_are_told_apart(): void
    {
        $master = $this->master();
        $olga = $this->card($master, 'Ольга Ким');

        $this->booking($master, $olga, '2026-05-05 10:00', 'completed');
        $this->booking($master, $olga, '2026-05-12 10:00', 'cancelled');
        $this->booking($master, $olga, '2026-05-19 10:00', 'no_show');

        $outcomes = collect($this->overview()['data']['clients']['outcomes'])->keyBy('key');

        $this->assertSame(3, $outcomes['total']['count']);
        $this->assertSame(1, $outcomes['came']['count']);
        $this->assertSame(1, $outcomes['cancelled']['count']);
        $this->assertSame(1, $outcomes['no_show']['count']);
    }

    public function test_retention_is_the_share_of_this_period_who_had_been_before(): void
    {
        $master = $this->master();

        $returning = $this->card($master, 'Ольга Ким');
        $this->booking($master, $returning, '2026-03-02 10:00', 'completed');
        $this->booking($master, $returning, '2026-05-06 10:00', 'completed');

        $firstTimer = $this->card($master, 'Анна Лебедева');
        $this->booking($master, $firstTimer, '2026-05-07 10:00', 'completed');

        $summary = $this->overview()['data']['summary'];

        $this->assertSame(50.0, (float) $summary['retention_rate']['current']);
        $this->assertSame(2, $summary['clients']['served']);
        $this->assertSame(1, $summary['clients']['returning']);
    }

    public function test_every_client_falls_into_exactly_one_group(): void
    {
        $master = $this->master();

        $regular = $this->card($master, 'Ольга Ким');
        $this->booking($master, $regular, '2026-04-10 10:00', 'completed');
        $this->booking($master, $regular, '2026-05-10 10:00', 'completed');

        $newcomer = $this->card($master, 'Анна Лебедева');
        $this->booking($master, $newcomer, '2026-05-20 10:00', 'completed');

        $gone = $this->card($master, 'Юлия Титова');
        $this->booking($master, $gone, '2026-01-15 10:00', 'completed');

        $this->card($master, 'Тестовая Проверка');

        $segments = $this->overview()['data']['clients']['segments'];
        $distribution = $segments['distribution'];

        $this->assertSame(1, $distribution['regular']['count']);
        $this->assertSame(1, $distribution['new']['count']);
        $this->assertSame(1, $distribution['sleeping']['count']);
        $this->assertSame(1, $distribution['never']['count']);

        // The three groups used to overlap and their shares added up to 200%.
        $this->assertSame(4, $segments['total']);
        $this->assertSame(
            $segments['total'],
            collect($distribution)->sum('count'),
        );
        $this->assertSame(100.0, round(collect($distribution)->sum('share'), 1));
    }

    public function test_the_previous_period_line_is_not_flat_at_zero(): void
    {
        $master = $this->master();
        $olga = $this->card($master, 'Ольга Ким');

        // Inside the compare window: 2 April — 1 May for a May period.
        $this->booking($master, $olga, '2026-04-20 10:00', 'completed');
        $this->booking($master, $olga, '2026-05-20 10:00', 'completed');

        $payload = $this->overview(['from' => '2026-05-02', 'to' => '2026-05-31']);
        $trend = $payload['data']['financial']['revenue_trend'];

        // Carbon 3 signs its diffs, so the day offsets used to come out negative
        // and every previous-period bucket was read from before the window.
        $this->assertSame(2500.0, (float) $trend['previous_total']);
        $this->assertSame(
            (float) $payload['data']['summary']['revenue']['previous'],
            (float) $trend['previous_total'],
        );
    }

    public function test_the_period_is_chosen_with_presets_and_the_grouping_follows_it(): void
    {
        $this->master();

        $month = $this->overview();
        $this->assertSame('day', $month['meta']['period']['grouping']);
        $this->assertTrue(
            collect($month['meta']['filters']['presets'])->firstWhere('key', 'month')['active'],
        );

        $year = $this->overview(['from' => '2025-06-01', 'to' => '2026-05-31']);
        $this->assertSame('month', $year['meta']['period']['grouping']);
    }

    public function test_the_export_hands_over_the_bookings_of_the_period(): void
    {
        $master = $this->master();
        $olga = $this->card($master, 'Ольга Ким');

        $this->booking($master, $olga, '2026-05-06 10:00', 'completed');
        $this->booking($master, $olga, '2026-01-06 10:00', 'completed');

        $response = $this->get('/api/v1/analytics/export?from=2026-05-01&to=2026-05-31');
        $response->assertOk();

        $body = $response->streamedContent();

        $this->assertStringContainsString('Ольга Ким', $body);
        $this->assertStringContainsString('06.05.2026', $body);
        $this->assertStringNotContainsString('06.01.2026', $body);
    }

    /**
     * @return array<string, mixed>
     */
    private function overview(array $query = []): array
    {
        return $this->getJson('/api/v1/analytics/overview?' . http_build_query($query))
            ->assertOk()
            ->json();
    }

    private function master(): User
    {
        $master = User::factory()->create(['timezone' => 'Europe/Moscow']);
        Sanctum::actingAs($master);

        return $master;
    }

    private function card(User $master, string $name): Client
    {
        $account = User::factory()->create(['name' => $name]);

        return Client::query()->create([
            'user_id' => $master->id,
            'client_user_id' => $account->id,
            'name' => $name,
            'phone' => '+7916' . str_pad((string) $account->id, 7, '0', STR_PAD_LEFT),
        ]);
    }

    private function booking(User $master, Client $card, string $time, string $status): Order
    {
        return Order::query()->create([
            'master_id' => $master->id,
            'client_id' => $card->client_user_id,
            'services' => [['id' => 1, 'name' => 'Стрижка', 'price' => 2500, 'duration' => 60]],
            'duration_forecast' => 60,
            'scheduled_at' => Carbon::parse($time),
            'total_price' => 2500,
            'status' => $status,
            'source' => 'manual',
        ]);
    }
}
