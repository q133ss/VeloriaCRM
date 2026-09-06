<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\Booking\DayScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CalendarDayGapsTest extends TestCase
{
    use RefreshDatabase;

    private const HOURS = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
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

        Service::create([
            'user_id' => $master->id,
            'name' => 'Стрижка',
            'base_price' => 2500,
            'cost' => 700,
            'duration_min' => 60,
        ]);

        return $master;
    }

    private function booking(User $master, string $at, int $minutes, array $attributes = []): Order
    {
        return Order::query()->create(array_merge([
            'master_id' => $master->id,
            'client_id' => User::factory()->create()->id,
            'services' => [['id' => 1, 'name' => 'Стрижка', 'price' => 2500, 'duration' => $minutes]],
            'duration_forecast' => $minutes,
            'scheduled_at' => Carbon::parse($at, 'Europe/Moscow'),
            'total_price' => 2500,
            'status' => 'confirmed',
            'source' => 'manual',
        ], $attributes));
    }

    public function test_two_bookings_leave_the_morning_and_the_hole_between_them(): void
    {
        Carbon::setTestNow('2026-04-01 07:00:00');
        $master = $this->master();

        $this->booking($master, '2026-04-10 11:00', 60);
        $this->booking($master, '2026-04-10 15:00', 90);

        $gaps = app(DayScheduleService::class)->gapsForDate($master->id, Carbon::parse('2026-04-10'));

        $this->assertCount(2, $gaps);

        $this->assertSame('09:00', $gaps[0]['start']);
        $this->assertSame('11:00', $gaps[0]['end']);

        $this->assertSame('12:00', $gaps[1]['start']);
        $this->assertSame('15:00', $gaps[1]['end']);
        $this->assertSame(180, $gaps[1]['minutes']);
        $this->assertSame(['12:00', '13:00', '14:00'], $gaps[1]['slots']);
    }

    public function test_unsold_morning_before_the_first_booking_counts_as_a_gap(): void
    {
        Carbon::setTestNow('2026-04-01 07:00:00');
        $master = $this->master();

        $this->booking($master, '2026-04-10 14:00', 60);

        $gaps = app(DayScheduleService::class)->gapsForDate($master->id, Carbon::parse('2026-04-10'));

        $this->assertCount(1, $gaps);
        $this->assertSame('09:00', $gaps[0]['start']);
        $this->assertSame('14:00', $gaps[0]['end']);
    }

    public function test_a_day_without_bookings_reports_no_gaps(): void
    {
        Carbon::setTestNow('2026-04-01 07:00:00');
        $master = $this->master();

        $this->assertSame([], app(DayScheduleService::class)->gapsForDate($master->id, Carbon::parse('2026-04-10')));
    }

    public function test_past_days_report_no_gaps(): void
    {
        Carbon::setTestNow('2026-04-20 07:00:00');
        $master = $this->master();

        $this->booking($master, '2026-04-10 11:00', 60);
        $this->booking($master, '2026-04-10 15:00', 60);

        $this->assertSame([], app(DayScheduleService::class)->gapsForDate($master->id, Carbon::parse('2026-04-10')));
    }

    public function test_today_is_clipped_to_what_is_still_ahead(): void
    {
        // 13:20 Moscow time, so the 12:00-15:00 hole starts from 13:35 onwards.
        Carbon::setTestNow(Carbon::parse('2026-04-10 13:20', 'Europe/Moscow'));
        $master = $this->master();

        $this->booking($master, '2026-04-10 11:00', 60);
        $this->booking($master, '2026-04-10 15:00', 60);

        $gaps = app(DayScheduleService::class)->gapsForDate($master->id, Carbon::parse('2026-04-10'));

        $this->assertCount(1, $gaps);
        $this->assertSame('13:35', $gaps[0]['start']);
        $this->assertSame(85, $gaps[0]['minutes']);
        $this->assertSame(['14:00'], $gaps[0]['slots']);
    }

    public function test_a_hole_shorter_than_the_shortest_service_is_not_reported(): void
    {
        Carbon::setTestNow('2026-04-01 07:00:00');
        $master = $this->master();

        // 30 free minutes, while the only service on offer takes an hour.
        $this->booking($master, '2026-04-10 09:00', 90);
        $this->booking($master, '2026-04-10 11:00', 60);

        $this->assertSame([], app(DayScheduleService::class)->gapsForDate($master->id, Carbon::parse('2026-04-10')));
    }

    public function test_estimated_value_needs_enough_completed_history(): void
    {
        Carbon::setTestNow('2026-04-01 07:00:00');
        $master = $this->master();

        $this->booking($master, '2026-04-10 11:00', 60);
        $this->booking($master, '2026-04-10 15:00', 60);

        $service = app(DayScheduleService::class);
        $this->assertNull($service->gapsForDate($master->id, Carbon::parse('2026-04-10'))[1]['estimated_value']);

        // Ten completed hours at 2500 each: the rate becomes measurable.
        for ($i = 0; $i < 10; $i++) {
            $this->booking($master, '2026-03-0' . ($i % 9 + 1) . ' 10:00', 60, ['status' => 'completed']);
        }

        // The 12:00-15:00 hole is three hours at 2500 per hour.
        $gaps = $service->gapsForDate($master->id, Carbon::parse('2026-04-10'));
        $this->assertSame(7500.0, $gaps[1]['estimated_value']);
    }

    public function test_cancelled_bookings_do_not_block_time(): void
    {
        Carbon::setTestNow('2026-04-01 07:00:00');
        $master = $this->master();

        $this->booking($master, '2026-04-10 11:00', 60);
        $this->booking($master, '2026-04-10 13:00', 60, ['status' => 'cancelled']);
        $this->booking($master, '2026-04-10 15:00', 60);

        $gaps = app(DayScheduleService::class)->gapsForDate($master->id, Carbon::parse('2026-04-10'));

        // Morning, then the whole 12:00-15:00 stretch the cancellation freed up.
        $this->assertCount(2, $gaps);
        $this->assertSame(180, $gaps[1]['minutes']);
    }

    public function test_free_slots_respect_booking_length(): void
    {
        // The regression this whole change exists for: free time used to be the
        // schedule grid minus booked start times, so a 90-minute booking at 10:00
        // still advertised 10:30 and 11:00 as available.
        Carbon::setTestNow('2026-04-01 07:00:00');
        $master = $this->master();

        $this->booking($master, '2026-04-10 10:00', 90);

        Sanctum::actingAs($master);

        $response = $this->getJson('/api/v1/calendar/day?date=2026-04-10')->assertOk();
        $slots = $response->json('data.available_slots');

        $this->assertNotContains('10:00', $slots);
        $this->assertNotContains('11:00', $slots);
        $this->assertContains('12:00', $slots);
    }
}
