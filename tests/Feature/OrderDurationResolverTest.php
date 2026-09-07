<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\Booking\OrderDurationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrderDurationResolverTest extends TestCase
{
    use RefreshDatabase;

    private function order(array $attributes = []): Order
    {
        $master = User::factory()->create();
        $client = User::factory()->create();

        return Order::query()->create(array_merge([
            'master_id' => $master->id,
            'client_id' => $client->id,
            'services' => [['id' => 1, 'name' => 'Стрижка', 'price' => 1500, 'duration' => 60]],
            'scheduled_at' => Carbon::parse('2026-04-01 10:00:00'),
            'total_price' => 1500,
            'status' => 'confirmed',
            'source' => 'manual',
        ], $attributes));
    }

    public function test_forecast_wins_over_the_services_snapshot(): void
    {
        // The snapshot holds price-list defaults; the forecast is the field a master
        // can correct, so a manual 140 must not be overruled by a 60 in the snapshot.
        $order = $this->order(['duration_forecast' => 140]);

        $this->assertSame(140, app(OrderDurationResolver::class)->resolve($order));
    }

    public function test_falls_back_to_the_services_snapshot(): void
    {
        $order = $this->order([
            'duration_forecast' => null,
            'services' => [
                ['id' => 1, 'name' => 'Окрашивание', 'price' => 6500, 'duration' => 150],
                ['id' => 2, 'name' => 'Укладка', 'price' => 1500, 'duration' => 45],
            ],
        ]);

        $this->assertSame(195, app(OrderDurationResolver::class)->resolve($order));
    }

    public function test_falls_back_to_the_measured_duration_then_to_an_hour(): void
    {
        $resolver = app(OrderDurationResolver::class);

        $measured = $this->order([
            'duration_forecast' => null,
            'services' => [],
            'duration' => 95,
        ]);

        $this->assertSame(95, $resolver->resolve($measured));

        $empty = $this->order([
            'duration_forecast' => null,
            'services' => [],
            'duration' => null,
        ]);

        $this->assertSame(60, $resolver->resolve($empty));
    }

    public function test_interval_is_null_without_a_scheduled_time(): void
    {
        $order = $this->order(['duration_forecast' => 60]);
        $order->forceFill(['scheduled_at' => null]);

        $this->assertNull(app(OrderDurationResolver::class)->interval($order));
    }

    public function test_overlapping_intervals_are_merged_into_one_block(): void
    {
        $resolver = app(OrderDurationResolver::class);

        $merged = $resolver->merge([
            ['start' => Carbon::parse('2026-04-01 12:00'), 'end' => Carbon::parse('2026-04-01 13:00')],
            ['start' => Carbon::parse('2026-04-01 10:00'), 'end' => Carbon::parse('2026-04-01 11:30')],
            ['start' => Carbon::parse('2026-04-01 11:00'), 'end' => Carbon::parse('2026-04-01 11:15')],
        ]);

        $this->assertCount(2, $merged);
        $this->assertSame('10:00', $merged[0]['start']->format('H:i'));
        $this->assertSame('11:30', $merged[0]['end']->format('H:i'));
        $this->assertSame('12:00', $merged[1]['start']->format('H:i'));
    }
}
