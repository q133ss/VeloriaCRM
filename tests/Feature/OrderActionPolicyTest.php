<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderActionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrderActionPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function bookingAt(string $time): Order
    {
        $master = User::factory()->create();
        $client = User::factory()->create();

        return Order::query()->create([
            'master_id' => $master->id,
            'client_id' => $client->id,
            'services' => [['id' => 1, 'name' => 'Стрижка', 'price' => 2500, 'duration' => 60]],
            'duration_forecast' => 60,
            'scheduled_at' => Carbon::parse($time),
            'total_price' => 2500,
            'status' => 'confirmed',
            'source' => 'manual',
        ]);
    }

    public function test_starting_around_the_booked_minute_asks_nothing(): void
    {
        Carbon::setTestNow('2026-05-05 15:00:00');
        $policy = app(OrderActionPolicy::class);

        $this->assertFalse($policy->for($this->bookingAt('2026-05-05 15:00'))['start_needs_confirm']);
        // Ten minutes early and ten minutes late are both normal.
        $this->assertFalse($policy->for($this->bookingAt('2026-05-05 15:10'))['start_needs_confirm']);
        $this->assertFalse($policy->for($this->bookingAt('2026-05-05 14:50'))['start_needs_confirm']);
    }

    public function test_starting_hours_early_asks_first(): void
    {
        // Booked for 15:00, the master presses Start at 13:00: two hours that
        // never happened would be recorded as the duration.
        Carbon::setTestNow('2026-05-05 13:00:00');

        $this->assertTrue(
            app(OrderActionPolicy::class)->for($this->bookingAt('2026-05-05 15:00'))['start_needs_confirm'],
        );
    }

    public function test_starting_a_booking_from_earlier_today_asks_first(): void
    {
        Carbon::setTestNow('2026-05-05 15:00:00');

        $this->assertTrue(
            app(OrderActionPolicy::class)->for($this->bookingAt('2026-05-05 10:00'))['start_needs_confirm'],
        );
    }

    public function test_a_past_booking_can_be_marked_as_a_no_show_and_a_future_one_cannot(): void
    {
        Carbon::setTestNow('2026-05-05 15:00:00');
        $policy = app(OrderActionPolicy::class);

        $this->assertTrue($policy->for($this->bookingAt('2026-05-05 10:00'))['can_mark_no_show']);
        $this->assertFalse($policy->for($this->bookingAt('2026-05-05 18:00'))['can_mark_no_show']);
    }

    public function test_a_finished_booking_offers_no_start_button(): void
    {
        Carbon::setTestNow('2026-05-05 15:00:00');
        $policy = app(OrderActionPolicy::class);

        $open = $this->bookingAt('2026-05-05 14:00');
        $done = $this->bookingAt('2026-05-05 10:00');
        $done->update(['status' => 'completed']);

        $this->assertTrue($policy->for($open)['can_start']);
        $this->assertFalse($policy->for($done->fresh())['can_start']);

        // can_start_now still answers the narrower question it always did.
        $this->assertTrue($policy->for($done->fresh())['can_start_now']);
    }
}
