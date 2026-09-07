<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * What the day sheet says when it has nothing to show.
 *
 * Three of its four blocks used to disappear when their data was empty, so a
 * new account saw one card and a blank screen — and «all three days booked»
 * looked exactly like «this feature does not exist».
 */
class DashboardEmptyStatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'ru');
    }

    public function test_a_brand_new_account_still_gets_all_four_blocks(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Moscow']);

        $response = $this->actingAs($user)->get('/dashboard')->assertOk();

        $response->assertSee(__('dashboard.day.title'));
        $response->assertSee(__('dashboard.week.title'));
        $response->assertSee(__('dashboard.due.title'));
        $response->assertSee(__('dashboard.free.title'));

        // And each one says what will appear there.
        $response->assertSee(__('dashboard.week.empty'), false);
        $response->assertSee(__('dashboard.due.empty_new'), false);
        $response->assertSee(__('dashboard.free.empty_no_schedule'), false);
    }

    /**
     * The hero and the card underneath used to print the same sentence.
     */
    public function test_the_hero_does_not_repeat_the_card_below_it(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Moscow']);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee(__('dashboard.day.hero_setup'), false)
            ->assertSee(__('dashboard.day.setup_empty.title'), false);
    }

    public function test_three_days_off_are_said_out_loud(): void
    {
        // A schedule that exists but has no working day in the next three.
        $farAway = Carbon::now('Europe/Moscow')->addDays(4);
        $user = $this->masterWithSchedule([strtolower($farAway->locale('en')->isoFormat('ddd')) => ['11:00']]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee(__('dashboard.free.empty_day_off'), false);
    }

    public function test_a_fully_booked_stretch_is_good_news_not_a_missing_block(): void
    {
        $tomorrow = Carbon::now('Europe/Moscow')->addDay();
        $user = $this->masterWithSchedule([strtolower($tomorrow->locale('en')->isoFormat('ddd')) => ['11:00']]);

        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Клиентка',
            'phone' => '+79990000001',
        ]);

        Order::create([
            'master_id' => $user->id,
            'client_id' => $client->id,
            'services' => [],
            'status' => 'confirmed',
            'scheduled_at' => $tomorrow->copy()->setTime(11, 0)->format('Y-m-d H:i:s'),
            'total_price' => 0,
        ]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee(__('dashboard.free.empty_all_booked'), false);
    }

    public function test_a_free_slot_is_shown_when_there_is_one(): void
    {
        $tomorrow = Carbon::now('Europe/Moscow')->addDay();
        $user = $this->masterWithSchedule([strtolower($tomorrow->locale('en')->isoFormat('ddd')) => ['11:00']]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('11:00')
            ->assertDontSee(__('dashboard.free.empty_no_schedule'), false);
    }

    /**
     * @param  array<string, array<int, string>>  $days  weekday key => slots
     */
    private function masterWithSchedule(array $days): User
    {
        $user = User::factory()->create(['timezone' => 'Europe/Moscow']);

        $weekly = [];

        foreach (['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $day) {
            $slots = $days[$day] ?? [];
            $weekly[$day] = ['enabled' => $slots !== [], 'slots' => $slots];
        }

        Setting::create([
            'user_id' => $user->id,
            'schedule_rules' => ['mode' => 'weekly', 'weekly' => $weekly],
            'work_days' => array_keys($days),
            'work_hours' => $days,
        ]);

        return $user;
    }
}
