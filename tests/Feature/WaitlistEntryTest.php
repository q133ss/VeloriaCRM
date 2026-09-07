<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WaitlistEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // SetLocale reads the header; a Russian master's browser always sends one.
        $this->withHeader('Accept-Language', 'ru');
    }

    public function test_a_window_that_ends_when_it_starts_is_refused(): void
    {
        [$master, $service] = $this->master();
        Sanctum::actingAs($master);

        $response = $this->postJson('/api/v1/waitlist', $this->payload($service, [
            'preferred_time_windows' => [['start' => '12:30', 'end' => '12:30']],
        ]))->assertStatus(422);

        // The key holds dots of its own, so assertJsonPath cannot address it.
        $this->assertSame(
            ['Конец окна должен быть позже начала.'],
            $response->json('error.fields')['preferred_time_windows.0.end'],
        );

        $this->assertSame(0, WaitlistEntry::query()->count());
    }

    public function test_a_normal_window_goes_through(): void
    {
        [$master, $service] = $this->master();
        Sanctum::actingAs($master);

        $this->postJson('/api/v1/waitlist', $this->payload($service, [
            'preferred_time_windows' => [['start' => '12:30', 'end' => '16:00']],
        ]))->assertCreated();

        $this->assertSame(1, WaitlistEntry::query()->count());
    }

    public function test_the_phone_is_normalised_the_way_the_calendar_does_it(): void
    {
        [$master, $service] = $this->master();
        Sanctum::actingAs($master);

        $this->postJson('/api/v1/waitlist', $this->payload($service, [
            'client_phone' => '8 (916) 000-11-22',
        ]))->assertCreated();

        $card = Client::query()->where('user_id', $master->id)->firstOrFail();

        $this->assertSame('+79160001122', $card->phone);
        // A waiting client is a real person, not a card with nothing behind it:
        // she has to be recognisable when she is finally booked.
        $this->assertNotNull($card->client_user_id);
        $this->assertSame('+79160001122', User::findOrFail($card->client_user_id)->phone);
    }

    public function test_the_same_phone_written_two_ways_is_one_client(): void
    {
        [$master, $service] = $this->master();
        Sanctum::actingAs($master);

        $this->postJson('/api/v1/waitlist', $this->payload($service, [
            'client_phone' => '+7(916)000-11-22',
            'preferred_dates' => ['2026-09-10'],
        ]))->assertCreated();

        $this->postJson('/api/v1/waitlist', $this->payload($service, [
            'client_phone' => '89160001122',
            'preferred_dates' => ['2026-09-11'],
        ]))->assertCreated();

        $this->assertSame(1, Client::query()->where('user_id', $master->id)->count());
        $this->assertSame(2, WaitlistEntry::query()->count());
    }

    public function test_the_same_client_cannot_wait_twice_for_the_same_day(): void
    {
        [$master, $service] = $this->master();
        Sanctum::actingAs($master);

        $this->postJson('/api/v1/waitlist', $this->payload($service))->assertCreated();

        $this->postJson('/api/v1/waitlist', $this->payload($service))
            ->assertStatus(422)
            ->assertJsonPath('errors.client_phone.0', 'Марина уже ждёт эту услугу на 10.09.2026.');

        $this->assertSame(1, WaitlistEntry::query()->count());
    }

    public function test_waiting_for_another_day_is_not_a_duplicate(): void
    {
        [$master, $service] = $this->master();
        Sanctum::actingAs($master);

        $this->postJson('/api/v1/waitlist', $this->payload($service))->assertCreated();
        $this->postJson('/api/v1/waitlist', $this->payload($service, [
            'preferred_dates' => ['2026-09-12'],
        ]))->assertCreated();

        $this->assertSame(2, WaitlistEntry::query()->count());
    }

    public function test_a_service_is_required(): void
    {
        [$master, $service] = $this->master();
        Sanctum::actingAs($master);

        $payload = $this->payload($service);
        unset($payload['service_id']);

        $this->postJson('/api/v1/waitlist', $payload)
            ->assertStatus(422)
            ->assertJsonPath('error.fields.service_id.0', 'Выберите услугу, на которую она ждёт.');
    }

    /**
     * @return array{0: User, 1: Service}
     */
    private function master(): array
    {
        $master = User::factory()->create(['timezone' => 'Europe/Moscow']);

        $service = Service::create([
            'user_id' => $master->id,
            'name' => 'Маникюр с покрытием',
            'base_price' => 2800,
            'cost' => 0,
            'duration_min' => 90,
        ]);

        return [$master, $service];
    }

    private function payload(Service $service, array $overrides = []): array
    {
        return array_merge([
            'client_name' => 'Марина',
            'client_phone' => '+79160001122',
            'service_id' => $service->id,
            'preferred_dates' => ['2026-09-10'],
            'flexibility_days' => 0,
            'priority_manual' => 0,
            'source' => 'manual',
        ], $overrides);
    }
}
