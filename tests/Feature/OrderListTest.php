<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * «Кто у меня сегодня» — вопрос, с которым открывают список записей.
 */
class OrderListTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_the_nearest_booking_comes_first_and_the_past_falls_in_behind_it(): void
    {
        Carbon::setTestNow('2026-05-05 12:00:00');

        $master = User::factory()->create();
        Sanctum::actingAs($master);

        $morning = $this->booking($master, '2026-05-05 09:00');
        $evening = $this->booking($master, '2026-05-05 18:00');
        $lunch = $this->booking($master, '2026-05-05 13:00');
        $yesterday = $this->booking($master, '2026-05-04 15:00');

        $ids = collect($this->getJson('/api/v1/orders?period=all')->assertOk()->json('data'))
            ->pluck('id')
            ->all();

        // The nearest ones first, ascending; then the past, freshest first.
        $this->assertSame([$lunch->id, $evening->id, $morning->id, $yesterday->id], $ids);
    }

    public function test_the_header_counts_today_and_names_the_next_client(): void
    {
        Carbon::setTestNow('2026-05-05 12:00:00');

        $master = User::factory()->create();
        Sanctum::actingAs($master);

        $this->booking($master, '2026-05-05 09:00');
        $next = $this->booking($master, '2026-05-05 16:00');
        $this->booking($master, '2026-05-05 18:00', 'cancelled');

        // The filter says today, but the header counts the whole schedule —
        // otherwise it would change its answer along with the filter.
        $today = $this->getJson('/api/v1/orders?period=today')->assertOk()->json('meta.today');

        $this->assertSame(2, $today['total']);
        $this->assertSame($next->id, $today['next']['id']);
        $this->assertSame('16:00', $today['next']['time']);
        $this->assertTrue($today['next']['is_today']);
    }

    public function test_a_closed_booking_is_left_alone_by_a_bulk_confirm(): void
    {
        Carbon::setTestNow('2026-05-05 12:00:00');

        $master = User::factory()->create();
        Sanctum::actingAs($master);

        $fresh = $this->booking($master, '2026-05-05 16:00');
        $done = $this->booking($master, '2026-05-05 09:00', 'completed');

        $response = $this->postJson('/api/v1/orders/bulk', [
            'action' => 'confirm',
            'orders' => [$fresh->id, $done->id],
        ])->assertOk();

        $this->assertSame([$fresh->id], $response->json('updated_ids'));
        $this->assertSame([$done->id], $response->json('skipped_ids'));
        $this->assertStringContainsString('Пропущено: 1', $response->json('message'));

        $this->assertSame('confirmed', $fresh->refresh()->status);
        $this->assertSame('completed', $done->refresh()->status);
    }

    public function test_confirming_nothing_but_closed_bookings_says_so_instead(): void
    {
        Carbon::setTestNow('2026-05-05 12:00:00');

        $master = User::factory()->create();
        Sanctum::actingAs($master);

        $cancelled = $this->booking($master, '2026-05-05 16:00', 'cancelled');

        $this->postJson('/api/v1/orders/bulk', [
            'action' => 'confirm',
            'orders' => [$cancelled->id],
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'action_not_applicable');

        $this->assertSame('cancelled', $cancelled->refresh()->status);
        $this->assertNull($cancelled->confirmed_at);
    }

    private function booking(User $master, string $time, string $status = 'new'): Order
    {
        return Order::query()->create([
            'master_id' => $master->id,
            'client_id' => User::factory()->create()->id,
            'services' => [['id' => 1, 'name' => 'Стрижка', 'price' => 2500, 'duration' => 60]],
            'duration_forecast' => 60,
            'scheduled_at' => Carbon::parse($time),
            'total_price' => 2500,
            'status' => $status,
            'source' => 'manual',
        ]);
    }
}
