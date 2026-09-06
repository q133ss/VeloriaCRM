<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\Booking\ServiceDurationEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ServiceDurationEstimatorTest extends TestCase
{
    use RefreshDatabase;

    private User $master;

    protected function setUp(): void
    {
        parent::setUp();

        $this->master = User::factory()->create();
    }

    /**
     * @param  array<int, array{0: int, 1: int}>  $services  [id, planned minutes]
     */
    private function completed(array $services, int $measured): Order
    {
        return Order::query()->create([
            'master_id' => $this->master->id,
            'client_id' => User::factory()->create()->id,
            'services' => array_map(
                fn ($service) => ['id' => $service[0], 'name' => 'Услуга ' . $service[0], 'price' => 2000, 'duration' => $service[1]],
                $services,
            ),
            'duration_forecast' => array_sum(array_column($services, 1)),
            'scheduled_at' => Carbon::parse('2026-02-01 10:00'),
            'total_price' => 2000,
            'status' => 'completed',
            'duration' => $measured,
            'source' => 'manual',
        ]);
    }

    public function test_nothing_is_offered_below_three_measurements(): void
    {
        $this->completed([[1, 60]], 140);
        $this->completed([[1, 60]], 145);

        $this->assertNull(app(ServiceDurationEstimator::class)->estimateForServices($this->master->id, [1]));
    }

    public function test_the_median_survives_a_forgotten_timer(): void
    {
        $this->completed([[1, 60]], 140);
        $this->completed([[1, 60]], 145);
        $this->completed([[1, 60]], 135);
        // Finish pressed the next morning. A mean would report over two hours more.
        $this->completed([[1, 60]], 900);

        $estimate = app(ServiceDurationEstimator::class)->estimateForServices($this->master->id, [1]);

        $this->assertSame(140, $estimate['minutes']);
        $this->assertSame(3, $estimate['samples']);
    }

    public function test_an_estimate_that_agrees_with_the_plan_is_not_worth_saying(): void
    {
        $this->completed([[1, 60]], 62);
        $this->completed([[1, 60]], 58);
        $this->completed([[1, 60]], 60);

        $this->assertNull(app(ServiceDurationEstimator::class)->estimateForServices($this->master->id, [1]));
    }

    public function test_estimates_are_keyed_on_the_whole_service_set(): void
    {
        // Three of the pair, and three of the single service on its own.
        $this->completed([[1, 60], [2, 45]], 180);
        $this->completed([[1, 60], [2, 45]], 175);
        $this->completed([[1, 60], [2, 45]], 185);

        $this->completed([[1, 60]], 140);
        $this->completed([[1, 60]], 140);
        $this->completed([[1, 60]], 145);

        $estimator = app(ServiceDurationEstimator::class);

        $this->assertSame(180, $estimator->estimateForServices($this->master->id, [1, 2])['minutes']);
        $this->assertSame(140, $estimator->estimateForServices($this->master->id, [1])['minutes']);
        // Order of ids must not matter.
        $this->assertSame(180, $estimator->estimateForServices($this->master->id, [2, 1])['minutes']);
    }

    public function test_unmeasured_bookings_are_ignored(): void
    {
        $this->completed([[1, 60]], 140);
        $this->completed([[1, 60]], 145);

        // Completed but never started, so there is no measurement to learn from.
        Order::query()->create([
            'master_id' => $this->master->id,
            'client_id' => User::factory()->create()->id,
            'services' => [['id' => 1, 'name' => 'Услуга 1', 'price' => 2000, 'duration' => 60]],
            'scheduled_at' => Carbon::parse('2026-02-02 10:00'),
            'total_price' => 2000,
            'status' => 'completed',
            'duration' => null,
            'source' => 'manual',
        ]);

        $this->assertNull(app(ServiceDurationEstimator::class)->estimateForServices($this->master->id, [1]));
    }
}
