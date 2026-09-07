<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * «Сколько стоит, сколько идёт и берут ли вообще» — то, ради чего открывают прайс.
 */
class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'ru');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_the_price_list_says_how_long_the_service_really_takes(): void
    {
        $master = $this->master();
        $service = $this->service($master, 'Стрижка женская', 2500, 60);

        // An hour in the price list, two hours twenty on the clock, three times.
        foreach ([140, 145, 135] as $measured) {
            $this->visit($master, [$service], $measured);
        }

        $row = $this->firstService();

        $this->assertSame(60, $row['duration']['planned']);
        $this->assertSame(140, $row['duration']['measured']);
        $this->assertSame(3, $row['duration']['samples']);
        $this->assertTrue($row['duration']['needs_review']);
        $this->assertSame('обычно 140 мин', $row['duration']['hint']);
        $this->assertSame('по 3 визитам', $row['duration']['samples_text']);
    }

    public function test_a_measurement_that_agrees_with_the_plan_is_left_unsaid(): void
    {
        $master = $this->master();
        $service = $this->service($master, 'Укладка', 1500, 60);

        foreach ([60, 65, 55] as $measured) {
            $this->visit($master, [$service], $measured);
        }

        $row = $this->firstService();

        $this->assertFalse($row['duration']['needs_review']);
        $this->assertNull($row['duration']['hint']);
        $this->assertSame(0, $this->getJson('/api/v1/services')->json('meta.stats.needs_review'));
    }

    public function test_two_services_in_one_visit_measure_neither_of_them(): void
    {
        $master = $this->master();
        $cut = $this->service($master, 'Стрижка женская', 2500, 60);
        $styling = $this->service($master, 'Укладка', 1500, 45);

        // The booking keeps its services as one snapshot with one duration, so
        // there is no honest way to split the time between the two.
        foreach ([200, 210, 190] as $measured) {
            $this->visit($master, [$cut, $styling], $measured);
        }

        foreach ($this->getJson('/api/v1/services')->json('data.groups.0.services') as $row) {
            $this->assertNull($row['duration']['measured'], $row['name']);
        }
    }

    public function test_the_list_counts_how_often_a_service_is_booked_and_what_it_earned(): void
    {
        $master = $this->master();
        $service = $this->service($master, 'Окрашивание', 6500, 150);

        $this->visit($master, [$service], 150);
        $this->visit($master, [$service], 150);
        $this->booking($master, [$service], 'cancelled');

        $row = $this->firstService();

        // A cancelled visit was still demand — it just never became money.
        $this->assertSame(3, $row['demand']['bookings']);
        $this->assertSame(2, $row['demand']['completed']);
        $this->assertSame(13000.0, (float) $row['demand']['revenue']);
        $this->assertSame('3 записи', $row['demand']['text']);
        $this->assertNull($row['demand']['note']);
    }

    public function test_a_service_nobody_ever_finished_says_so(): void
    {
        $master = $this->master();
        $service = $this->service($master, 'Маникюр с покрытием', 2800, 90);

        $this->booking($master, [$service], 'confirmed');
        $this->booking($master, [$service], 'no_show');

        $row = $this->firstService();

        $this->assertSame(2, $row['demand']['bookings']);
        $this->assertSame(0.0, (float) $row['demand']['revenue']);
        $this->assertSame('ни одной завершённой', $row['demand']['note']);
    }

    public function test_a_service_never_booked_is_not_dressed_up_as_a_zero(): void
    {
        $master = $this->master();
        $this->service($master, 'Педикюр', 3000, 90);

        $this->assertSame('Ни разу не записывали', $this->firstService()['demand']['text']);
    }

    public function test_the_search_covers_the_category_the_field_always_promised(): void
    {
        $master = $this->master();
        $category = ServiceCategory::create(['user_id' => $master->id, 'name' => 'Ногти']);

        $manicure = $this->service($master, 'Маникюр', 2000, 60);
        $manicure->update(['category_id' => $category->id]);
        $this->service($master, 'Стрижка', 2500, 60);

        $found = collect($this->getJson('/api/v1/services?search=Ногти')->assertOk()->json('data.groups'))
            ->flatMap(fn (array $group) => $group['services'])
            ->pluck('name')
            ->all();

        $this->assertSame(['Маникюр'], $found);
    }

    public function test_a_related_service_has_to_be_one_of_your_own(): void
    {
        $master = $this->master();
        $service = $this->service($master, 'Стрижка', 2500, 60);
        $companion = $this->service($master, 'Укладка', 1500, 45);
        $stranger = $this->service(User::factory()->create(), 'Чужая услуга', 1000, 30);

        $this->patchJson('/api/v1/services/' . $service->id, [
            'name' => 'Стрижка',
            'base_price' => 2500,
            'duration_min' => 60,
            'upsell_suggestions' => [$companion->id],
        ])->assertOk();

        $this->assertSame([$companion->id], $service->refresh()->upsell_suggestions);

        $this->patchJson('/api/v1/services/' . $service->id, [
            'name' => 'Стрижка',
            'base_price' => 2500,
            'duration_min' => 60,
            'upsell_suggestions' => [$stranger->id],
        ])->assertStatus(422);

        // The column is a list of foreign keys, so free text has no business in it.
        $this->patchJson('/api/v1/services/' . $service->id, [
            'name' => 'Стрижка',
            'base_price' => 2500,
            'duration_min' => 60,
            'upsell_suggestions' => ['уходовая маска'],
        ])->assertStatus(422);
    }

    private function master(): User
    {
        $master = User::factory()->create(['timezone' => 'Europe/Moscow']);
        Sanctum::actingAs($master);

        return $master;
    }

    private function service(User $master, string $name, float $price, int $minutes): Service
    {
        return Service::create([
            'user_id' => $master->id,
            'name' => $name,
            'base_price' => $price,
            'cost' => 0,
            'duration_min' => $minutes,
        ]);
    }

    /**
     * @param  array<int, Service>  $services
     */
    private function visit(User $master, array $services, int $measured): Order
    {
        return $this->booking($master, $services, 'completed', $measured);
    }

    /**
     * @param  array<int, Service>  $services
     */
    private function booking(User $master, array $services, string $status, ?int $measured = null): Order
    {
        $snapshot = array_map(fn (Service $service) => [
            'id' => $service->id,
            'name' => $service->name,
            'price' => (float) $service->base_price,
            'duration' => $service->duration_min,
        ], $services);

        return Order::query()->create([
            'master_id' => $master->id,
            'client_id' => User::factory()->create()->id,
            'services' => $snapshot,
            'duration_forecast' => array_sum(array_column($snapshot, 'duration')),
            'duration' => $measured,
            'scheduled_at' => Carbon::parse('2026-05-01 10:00'),
            'total_price' => array_sum(array_column($snapshot, 'price')),
            'status' => $status,
            'source' => 'manual',
        ]);
    }

    private function firstService(): array
    {
        return $this->getJson('/api/v1/services')->assertOk()->json('data.groups.0.services.0');
    }
}
