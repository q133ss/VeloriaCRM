<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * «Клиентка записалась, но ещё не решила, на что.»
 */
class OrderWithoutServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'ru');
    }

    public function test_a_booking_can_be_made_with_no_service_and_takes_an_hour(): void
    {
        [$master] = $this->master();
        Sanctum::actingAs($master);

        $this->postJson('/api/v1/orders', $this->payload(['services' => []]))
            ->assertCreated();

        $order = Order::query()->firstOrFail();

        $this->assertSame([], $order->services);
        $this->assertSame(60, $order->duration_forecast);
        $this->assertSame(0.0, (float) $order->total_price);
    }

    public function test_a_null_service_list_is_not_a_crash(): void
    {
        [$master] = $this->master();
        Sanctum::actingAs($master);

        // The natural shape for "no service" from a client that omits the list.
        $this->postJson('/api/v1/orders', $this->payload(['services' => null]))
            ->assertCreated();

        $this->assertSame([], Order::query()->firstOrFail()->services);
    }

    public function test_a_price_can_be_named_without_naming_a_service(): void
    {
        [$master] = $this->master();
        Sanctum::actingAs($master);

        // «Придёт, будет примерно три тысячи.»
        $this->postJson('/api/v1/orders', $this->payload([
            'services' => [],
            'total_price' => 3000,
        ]))->assertCreated();

        $this->assertSame(3000.0, (float) Order::query()->firstOrFail()->total_price);
    }

    public function test_a_service_less_booking_still_blocks_its_hour(): void
    {
        [$master] = $this->master();
        Sanctum::actingAs($master);

        $this->postJson('/api/v1/orders', $this->payload(['services' => []]))->assertCreated();

        $this->postJson('/api/v1/orders', $this->payload([
            'services' => [],
            'scheduled_at' => '2026-09-10T10:30',
        ]))->assertStatus(422);

        $this->assertSame(1, Order::query()->count());
    }

    public function test_clearing_the_services_shortens_the_block(): void
    {
        [$master, $service] = $this->master();
        Sanctum::actingAs($master);

        $this->postJson('/api/v1/orders', $this->payload(['services' => [$service->id]]))->assertCreated();

        $order = Order::query()->firstOrFail();
        $this->assertSame(150, $order->duration_forecast);

        // The edit form sends no duration of its own, so the length has to
        // follow the services or the booking blocks 150 minutes forever.
        $this->patchJson('/api/v1/orders/' . $order->id, $this->payload(['services' => []]))
            ->assertOk();

        $this->assertSame(60, $order->refresh()->duration_forecast);
    }

    public function test_an_edit_that_never_mentions_services_leaves_the_length_alone(): void
    {
        [$master, $service] = $this->master();
        Sanctum::actingAs($master);

        $this->postJson('/api/v1/orders', $this->payload([
            'services' => [$service->id],
            'duration_forecast' => 200,
        ]))->assertCreated();

        $order = Order::query()->firstOrFail();

        $payload = $this->payload();
        unset($payload['services']);
        $payload['note'] = 'Опаздывает на десять минут';

        $this->patchJson('/api/v1/orders/' . $order->id, $payload)->assertOk();

        $this->assertSame(200, $order->refresh()->duration_forecast);
    }

    public function test_a_visit_with_no_service_cannot_be_closed(): void
    {
        [$master] = $this->master();
        Sanctum::actingAs($master);

        $this->postJson('/api/v1/orders', $this->payload(['services' => []]))->assertCreated();
        $order = Order::query()->firstOrFail();

        $this->postJson('/api/v1/orders/' . $order->id . '/complete')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'service_required')
            ->assertJsonPath('error.message', 'Отметьте услугу и сумму — без них визит не закрыть.');

        $this->assertNotSame('completed', $order->refresh()->status);
    }

    public function test_the_same_visit_closes_once_the_service_is_written_in(): void
    {
        [$master, $service] = $this->master();
        Sanctum::actingAs($master);

        $this->postJson('/api/v1/orders', $this->payload(['services' => []]))->assertCreated();
        $order = Order::query()->firstOrFail();

        $this->patchJson('/api/v1/orders/' . $order->id, $this->payload(['services' => [$service->id]]))
            ->assertOk();

        $this->postJson('/api/v1/orders/' . $order->id . '/complete')->assertOk();

        $this->assertSame('completed', $order->refresh()->status);
    }

    /**
     * @return array{0: User, 1: Service}
     */
    private function master(): array
    {
        $master = User::factory()->create(['timezone' => 'Europe/Moscow']);

        $service = Service::create([
            'user_id' => $master->id,
            'name' => 'Окрашивание',
            'base_price' => 6500,
            'cost' => 0,
            'duration_min' => 150,
        ]);

        return [$master, $service];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_phone' => '+79160001122',
            'client_name' => 'Марина',
            'scheduled_at' => '2026-09-10T10:00',
            'services' => [],
            'status' => 'new',
        ], $overrides);
    }
}
