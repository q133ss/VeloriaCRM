<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActiveTimerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function booking(User $master, array $attributes = []): Order
    {
        return Order::query()->create(array_merge([
            'master_id' => $master->id,
            'client_id' => User::factory()->create(['name' => 'Ирина'])->id,
            'services' => [['id' => 1, 'name' => 'Стрижка', 'price' => 2500, 'duration' => 60]],
            'duration_forecast' => 60,
            'scheduled_at' => Carbon::now(),
            'total_price' => 2500,
            'status' => 'confirmed',
            'source' => 'manual',
        ], $attributes));
    }

    public function test_no_running_visit_returns_null(): void
    {
        $master = User::factory()->create();
        $this->booking($master);

        Sanctum::actingAs($master);

        $this->getJson('/api/v1/orders/active')->assertOk()->assertJsonPath('data', null);
    }

    public function test_a_running_visit_is_reported_with_its_elapsed_time(): void
    {
        Carbon::setTestNow('2026-05-05 12:00:00');
        $master = User::factory()->create();

        $order = $this->booking($master, [
            'status' => 'in_progress',
            'actual_started_at' => Carbon::now()->subMinutes(37),
        ]);

        Sanctum::actingAs($master);

        $this->getJson('/api/v1/orders/active')
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.client_name', 'Ирина')
            ->assertJsonPath('data.elapsed_minutes', 37)
            ->assertJsonPath('data.planned_minutes', 60);
    }

    public function test_another_masters_visit_is_not_reported(): void
    {
        Carbon::setTestNow('2026-05-05 12:00:00');
        $master = User::factory()->create();
        $other = User::factory()->create();

        $this->booking($other, [
            'status' => 'in_progress',
            'actual_started_at' => Carbon::now()->subMinutes(10),
        ]);

        Sanctum::actingAs($master);

        $this->getJson('/api/v1/orders/active')->assertOk()->assertJsonPath('data', null);
    }

    public function test_finishing_records_a_whole_number_of_minutes(): void
    {
        // Carbon 3 hands back a float, and `duration` is an integer column. On
        // Postgres the raw value was rejected outright, so every finish through
        // the API returned a 500 and no duration was ever stored.
        Carbon::setTestNow('2026-05-05 12:00:00');
        $master = User::factory()->create();

        $order = $this->booking($master, [
            'status' => 'in_progress',
            'actual_started_at' => Carbon::now()->subMinutes(97)->subSeconds(19),
        ]);

        Sanctum::actingAs($master);

        $this->postJson('/api/v1/orders/' . $order->id . '/complete')->assertOk();

        $order->refresh();

        $this->assertSame('completed', $order->status);
        $this->assertSame(97, $order->duration);

        // The model casts to integer on read, so only the stored value proves the
        // rounding happened before the write — which is where Postgres objected.
        $this->assertSame('97', (string) $order->getRawOriginal('duration'));

        // And the header timer stops offering it.
        $this->getJson('/api/v1/orders/active')->assertOk()->assertJsonPath('data', null);
    }
}
