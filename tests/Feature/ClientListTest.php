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
 * «Когда она была и когда придёт» — то, ради чего открывают список клиентов.
 */
class ClientListTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_the_last_visit_is_counted_from_the_bookings_not_typed_in_by_hand(): void
    {
        Carbon::setTestNow('2026-05-05 12:00:00');

        $master = User::factory()->create();
        Sanctum::actingAs($master);

        $card = $this->card($master, 'Ольга Ким');
        $this->booking($master, $card, '2026-04-02 11:00', 'completed');
        $this->booking($master, $card, '2026-04-28 11:00', 'confirmed');
        $this->booking($master, $card, '2026-05-09 11:00', 'confirmed');

        $row = $this->firstRow();

        $this->assertSame(2, $row['visits']);
        $this->assertSame('Была 7 дней назад', $row['last_visit']['text']);
        $this->assertSame('2 визита', $row['visits_text']);
        $this->assertSame('Придёт 9 мая, 11:00', $row['next']['text']);

        // And the column every other page reads is now right as well.
        $this->assertSame('2026-04-28 11:00:00', $card->refresh()->last_visit_at->format('Y-m-d H:i:s'));
    }

    public function test_a_cancelled_or_missed_booking_is_not_a_visit(): void
    {
        Carbon::setTestNow('2026-05-05 12:00:00');

        $master = User::factory()->create();
        Sanctum::actingAs($master);

        $card = $this->card($master, 'Дарья Носова');
        $this->booking($master, $card, '2026-05-01 11:00', 'cancelled');
        $this->booking($master, $card, '2026-05-02 11:00', 'no_show');

        $row = $this->firstRow();

        $this->assertSame(0, $row['visits']);
        $this->assertSame('Ещё не приходила', $row['last_visit']['text']);
        // Stated as a fact, never as a score.
        $this->assertSame('Не пришла 1 раз', $row['no_shows_text']);
        $this->assertNull($card->refresh()->last_visit_at);
    }

    public function test_a_client_who_stopped_coming_is_the_one_the_page_offers_to_write_to(): void
    {
        Carbon::setTestNow('2026-05-05 12:00:00');

        $master = User::factory()->create();
        Sanctum::actingAs($master);

        $gone = $this->card($master, 'Анна Лебедева');
        $this->booking($master, $gone, '2026-01-10 11:00', 'completed');

        $booked = $this->card($master, 'Ирина Кравцова');
        $this->booking($master, $booked, '2026-05-08 11:00', 'confirmed');

        $fresh = $this->card($master, 'Марина Белова');
        $this->booking($master, $fresh, '2026-05-01 11:00', 'completed');

        $never = $this->card($master, 'Юлия Титова');

        $groups = collect($this->getJson('/api/v1/clients')->assertOk()->json('data'))
            ->pluck('group', 'name');

        $this->assertSame('sleeping', $groups['Анна Лебедева']);
        $this->assertSame('upcoming', $groups['Ирина Кравцова']);
        $this->assertSame('active', $groups['Марина Белова']);
        $this->assertSame('new', $groups['Юлия Титова']);

        $counts = $this->getJson('/api/v1/clients')->json('meta.groups');
        $this->assertSame(['all' => 4, 'upcoming' => 1, 'sleeping' => 1, 'new' => 1], $counts);

        $this->assertSame(
            ['Анна Лебедева'],
            collect($this->getJson('/api/v1/clients?group=sleeping')->json('data'))->pluck('name')->all(),
        );

        $this->assertSame($never->id, $this->getJson('/api/v1/clients?group=new')->json('data.0.id'));
    }

    public function test_the_soonest_visit_is_at_the_top_of_the_expected(): void
    {
        Carbon::setTestNow('2026-05-05 12:00:00');

        $master = User::factory()->create();
        Sanctum::actingAs($master);

        $later = $this->card($master, 'Ольга Ким');
        $this->booking($master, $later, '2026-05-20 11:00', 'confirmed');

        $sooner = $this->card($master, 'Анна Лебедева');
        $this->booking($master, $sooner, '2026-05-06 11:00', 'confirmed');

        $this->assertSame(
            ['Анна Лебедева', 'Ольга Ким'],
            collect($this->getJson('/api/v1/clients?group=upcoming')->json('data'))->pluck('name')->all(),
        );
    }

    public function test_a_tag_is_something_you_can_search_by(): void
    {
        $master = User::factory()->create();
        Sanctum::actingAs($master);

        $this->card($master, 'Ольга Ким', ['tags' => ['постоянная', 'блонд']]);
        $this->card($master, 'Дарья Носова');

        $found = collect($this->getJson('/api/v1/clients?search=блонд')->assertOk()->json('data'))
            ->pluck('name')
            ->all();

        $this->assertSame(['Ольга Ким'], $found);
    }

    public function test_a_visit_that_only_the_clock_made_past_is_picked_up_by_the_nightly_pass(): void
    {
        Carbon::setTestNow('2026-05-05 09:00:00');

        $master = User::factory()->create();
        $card = $this->card($master, 'Ольга Ким');
        $this->booking($master, $card, '2026-05-05 16:00', 'confirmed');

        // Booked this morning for this evening: nothing has been to her yet.
        $this->assertNull($card->refresh()->last_visit_at);

        Carbon::setTestNow('2026-05-05 19:00:00');
        $this->artisan('clients:refresh-visits')->assertSuccessful();

        $this->assertSame('2026-05-05 16:00:00', $card->refresh()->last_visit_at->format('Y-m-d H:i:s'));
    }

    public function test_one_master_never_counts_another_masters_bookings(): void
    {
        Carbon::setTestNow('2026-05-05 12:00:00');

        $master = User::factory()->create();
        $stranger = User::factory()->create();

        $card = $this->card($master, 'Ольга Ким');
        $this->booking($stranger, $card, '2026-05-01 11:00', 'completed');

        Sanctum::actingAs($master);

        $this->assertSame(0, $this->firstRow()['visits']);
    }

    private function firstRow(): array
    {
        return $this->getJson('/api/v1/clients')->assertOk()->json('data.0');
    }

    private function card(User $master, string $name, array $attributes = []): Client
    {
        $account = User::factory()->create(['name' => $name]);

        return Client::query()->create(array_merge([
            'user_id' => $master->id,
            'client_user_id' => $account->id,
            'name' => $name,
            'phone' => $account->phone ?? ('+7916' . str_pad((string) $account->id, 7, '0', STR_PAD_LEFT)),
        ], $attributes));
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
