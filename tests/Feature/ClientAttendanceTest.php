<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\ClientAttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function booking(User $master, User $client, string $at, string $status): Order
    {
        return Order::query()->create([
            'master_id' => $master->id,
            'client_id' => $client->id,
            'services' => [['id' => 1, 'name' => 'Стрижка', 'price' => 2500, 'duration' => 60]],
            'duration_forecast' => 60,
            'scheduled_at' => Carbon::parse($at),
            'total_price' => 2500,
            'status' => $status,
            'source' => 'manual',
        ]);
    }

    public function test_cancellations_are_not_counted_as_no_shows(): void
    {
        $master = User::factory()->create();
        $client = User::factory()->create();

        $this->booking($master, $client, '2026-01-10 10:00', 'no_show');
        $this->booking($master, $client, '2026-01-11 10:00', 'no_show');
        $this->booking($master, $client, '2026-01-12 10:00', 'cancelled');
        $this->booking($master, $client, '2026-01-13 10:00', 'completed');

        $counts = app(ClientAttendanceService::class)->noShowCountsFor($master->id, [$client->id]);

        $this->assertSame(2, $counts[$client->id]);
    }

    public function test_counts_are_scoped_to_the_master(): void
    {
        $master = User::factory()->create();
        $other = User::factory()->create();
        $client = User::factory()->create();

        $this->booking($master, $client, '2026-01-10 10:00', 'no_show');
        $this->booking($other, $client, '2026-01-11 10:00', 'no_show');

        $counts = app(ClientAttendanceService::class)->noShowCountsFor($master->id, [$client->id]);

        $this->assertSame(1, $counts[$client->id]);
    }

    public function test_nothing_is_said_about_a_client_who_always_turns_up(): void
    {
        $this->assertNull(app(ClientAttendanceService::class)->factFor(0));
    }

    public function test_day_payload_carries_the_fact_only_for_upcoming_bookings(): void
    {
        Carbon::setTestNow('2026-01-15 09:00:00');

        $master = User::factory()->create(['timezone' => 'Europe/Moscow']);
        Setting::create([
            'user_id' => $master->id,
            'work_days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'work_hours' => array_fill_keys(
                ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
                ['10:00', '11:00', '12:00', '13:00', '14:00'],
            ),
        ]);

        $client = User::factory()->create(['name' => 'Ольга']);

        $this->booking($master, $client, '2026-01-10 10:00', 'no_show');
        $this->booking($master, $client, '2026-01-11 10:00', 'no_show');

        // Later today, still ahead of the clock.
        $upcoming = $this->booking($master, $client, '2026-01-15 14:00', 'confirmed');
        // Earlier today, already past.
        $past = $this->booking($master, $client, '2026-01-15 08:00', 'confirmed');

        Sanctum::actingAs($master);

        $orders = collect($this->getJson('/api/v1/calendar/day?date=2026-01-15')->assertOk()->json('data.orders'))
            ->keyBy('id');

        $this->assertSame(2, $orders[$upcoming->id]['attention']['count']);
        $this->assertNotEmpty($orders[$upcoming->id]['attention']['text']);
        $this->assertNull($orders[$past->id]['attention']);

        // The past one is what the master can now mark, and only that one.
        $this->assertTrue($orders[$past->id]['can_mark_no_show']);
        $this->assertFalse($orders[$upcoming->id]['can_mark_no_show']);
    }

    public function test_marking_a_no_show_requires_a_past_unresolved_booking(): void
    {
        Carbon::setTestNow('2026-01-15 09:00:00');

        $master = User::factory()->create();
        $client = User::factory()->create();

        $future = $this->booking($master, $client, '2026-01-15 14:00', 'confirmed');
        $past = $this->booking($master, $client, '2026-01-15 08:00', 'confirmed');

        Sanctum::actingAs($master);

        $this->postJson('/api/v1/orders/' . $future->id . '/no-show')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'no_show_unavailable');

        $this->postJson('/api/v1/orders/' . $past->id . '/no-show')->assertOk();

        $this->assertSame('no_show', $past->fresh()->status);
    }
}
