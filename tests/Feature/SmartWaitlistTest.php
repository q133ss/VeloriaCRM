<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Services\WaitlistMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SmartWaitlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_cannot_create_overlapping_order(): void
    {
        $master = User::factory()->create();
        $existingClient = User::factory()->create([
            'name' => 'Existing Client',
            'phone' => '+79990000001',
        ]);

        $category = ServiceCategory::create([
            'user_id' => $master->id,
            'name' => 'Hair',
        ]);

        $service = Service::create([
            'user_id' => $master->id,
            'category_id' => $category->id,
            'name' => 'Haircut',
            'base_price' => 2500,
            'cost' => 900,
            'duration_min' => 60,
        ]);

        Order::query()->create([
            'master_id' => $master->id,
            'client_id' => $existingClient->id,
            'services' => [[
                'id' => $service->id,
                'name' => $service->name,
                'price' => 2500,
                'duration' => 60,
            ]],
            'scheduled_at' => Carbon::parse('2026-03-11 10:00:00'),
            'duration_forecast' => 60,
            'total_price' => 2500,
            'status' => 'new',
            'source' => 'manual',
        ]);

        Sanctum::actingAs($master);

        $response = $this->postJson('/api/v1/orders', [
            'client_phone' => '+79990000002',
            'client_name' => 'New Client',
            'scheduled_at' => '2026-03-11T10:30',
            'services' => [$service->id],
            'status' => 'new',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.scheduled_at.0', 'Это время уже занято. Ближайший конфликт начинается в 11.03.2026 10:00.');
    }

    public function test_waitlist_matches_rank_clients_by_score(): void
    {
        $master = User::factory()->create();

        $category = ServiceCategory::create([
            'user_id' => $master->id,
            'name' => 'Hair',
        ]);

        $service = Service::create([
            'user_id' => $master->id,
            'category_id' => $category->id,
            'name' => 'Coloring',
            'base_price' => 4000,
            'cost' => 1200,
            'duration_min' => 90,
        ]);

        $vipUser = User::factory()->create([
            'name' => 'VIP Client',
            'phone' => '+79990000011',
            'email' => 'vip@example.com',
        ]);

        $regularUser = User::factory()->create([
            'name' => 'Regular Client',
            'phone' => '+79990000012',
            'email' => 'regular@example.com',
        ]);

        $vipClient = Client::create([
            'user_id' => $master->id,
            'name' => 'VIP Client',
            'phone' => '+79990000011',
            'email' => 'vip@example.com',
            'loyalty_level' => 'vip',
        ]);

        $regularClient = Client::create([
            'user_id' => $master->id,
            'name' => 'Regular Client',
            'phone' => '+79990000012',
            'email' => 'regular@example.com',
        ]);

        Order::query()->create([
            'master_id' => $master->id,
            'client_id' => $vipUser->id,
            'services' => [[
                'id' => $service->id,
                'name' => $service->name,
                'price' => 12000,
                'duration' => 90,
            ]],
            'scheduled_at' => Carbon::parse('2026-02-20 11:00:00'),
            'duration_forecast' => 90,
            'total_price' => 12000,
            'status' => 'completed',
            'source' => 'manual',
        ]);

        WaitlistEntry::query()->create([
            'user_id' => $master->id,
            'client_id' => $vipClient->id,
            'client_user_id' => $vipUser->id,
            'service_id' => $service->id,
            'preferred_slots' => [],
            'preferred_dates' => ['2026-03-11'],
            'preferred_time_windows' => [['start' => '09:00', 'end' => '12:00']],
            'flexibility_days' => 0,
            'priority' => 3,
            'priority_manual' => 3,
            'status' => 'pending',
            'source' => 'manual',
        ]);

        WaitlistEntry::query()->create([
            'user_id' => $master->id,
            'client_id' => $regularClient->id,
            'client_user_id' => $regularUser->id,
            'service_id' => $service->id,
            'preferred_slots' => [],
            'preferred_dates' => ['2026-03-11'],
            'preferred_time_windows' => [['start' => '14:00', 'end' => '18:00']],
            'flexibility_days' => 0,
            'priority' => 0,
            'priority_manual' => 0,
            'status' => 'pending',
            'source' => 'manual',
        ]);

        Sanctum::actingAs($master);

        $response = $this->getJson('/api/v1/waitlist/matches?date=2026-03-11&time=10:00&service_id=' . $service->id);

        $response->assertOk()
            ->assertJsonPath('data.matches.0.client.name', 'VIP Client')
            ->assertJsonPath('data.matches.0.service.id', $service->id);
    }

    public function test_ranking_many_slots_does_not_scale_the_query_count(): void
    {
        $master = User::factory()->create();

        $service = Service::create([
            'user_id' => $master->id,
            'name' => 'Haircut',
            'base_price' => 2500,
            'cost' => 900,
            'duration_min' => 60,
        ]);

        for ($i = 0; $i < 20; $i++) {
            $account = User::factory()->create(['phone' => '+7999000' . str_pad((string) $i, 4, '0', STR_PAD_LEFT)]);

            $card = Client::create([
                'user_id' => $master->id,
                'client_user_id' => $account->id,
                'name' => 'Client ' . $i,
                'phone' => $account->phone,
            ]);

            Order::query()->create([
                'master_id' => $master->id,
                'client_id' => $account->id,
                'services' => [['id' => $service->id, 'name' => 'Haircut', 'price' => 2500, 'duration' => 60]],
                'scheduled_at' => Carbon::now()->subMonth(),
                'total_price' => 2500,
                'status' => 'completed',
                'source' => 'manual',
            ]);

            WaitlistEntry::create([
                'user_id' => $master->id,
                'client_id' => $card->id,
                'client_user_id' => $account->id,
                'service_id' => $service->id,
                'preferred_slots' => [],
                'preferred_dates' => [Carbon::now()->addWeek()->toDateString()],
                'flexibility_days' => 5,
                'priority_manual' => 0,
                'status' => 'pending',
                'source' => 'manual',
            ]);
        }

        $slots = [];

        foreach ([10, 12, 14, 16] as $hour) {
            $slots['gap-' . $hour] = [
                'start' => Carbon::now()->addWeek()->setTime($hour, 0),
                'duration' => 90,
                'service_id' => $service->id,
            ];
        }

        DB::enableQueryLog();
        $results = app(WaitlistMatchService::class)->rankForSlots($master->id, $slots, 3);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(4, $results);
        $this->assertGreaterThan(0, $results->get('gap-10')->count());

        // Entries and slots must not multiply into queries: one for the entries,
        // their eager loads, one account lookup and one aggregate.
        $this->assertLessThanOrEqual(8, $queries);
    }

    public function test_require_fit_drops_services_longer_than_the_slot(): void
    {
        $master = User::factory()->create();
        $account = User::factory()->create(['phone' => '+79995550001']);

        $card = Client::create([
            'user_id' => $master->id,
            'client_user_id' => $account->id,
            'name' => 'Long Service Client',
            'phone' => $account->phone,
        ]);

        $service = Service::create([
            'user_id' => $master->id,
            'name' => 'Colouring',
            'base_price' => 6500,
            'cost' => 2000,
            'duration_min' => 150,
        ]);

        WaitlistEntry::create([
            'user_id' => $master->id,
            'client_id' => $card->id,
            'client_user_id' => $account->id,
            'service_id' => $service->id,
            'preferred_slots' => [],
            'preferred_dates' => [Carbon::now()->addWeek()->toDateString()],
            'flexibility_days' => 5,
            'priority_manual' => 0,
            'status' => 'pending',
            'source' => 'manual',
        ]);

        $slot = ['start' => Carbon::now()->addWeek()->setTime(12, 0), 'duration' => 60, 'service_id' => null];
        $service9 = app(WaitlistMatchService::class);

        $this->assertCount(1, $service9->rankForSlots($master->id, ['gap' => $slot])->get('gap'));
        $this->assertCount(0, $service9->rankForSlots($master->id, ['gap' => $slot], 8, true)->get('gap'));
    }
}
