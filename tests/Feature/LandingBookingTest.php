<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Landing;
use App\Models\LandingRequest;
use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\Landing\TemplateRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Booking straight from a landing page: the visitor sees the master's free
 * windows and takes one, and the booking lands in the master's calendar.
 */
class LandingBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $master;

    private Service $service;

    private Landing $landing;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA ignore_check_constraints = ON');
        }

        // A Monday morning: the schedule below is weekly, and past slots are filtered out.
        Carbon::setTestNow('2026-10-05 08:00:00');

        $this->master = User::factory()->create(['timezone' => 'Europe/Moscow']);

        Setting::create([
            'user_id' => $this->master->id,
            'work_hours' => [
                'mon' => ['10:00', '11:00', '12:00'],
                'tue' => ['10:00', '11:00'],
            ],
        ]);

        $this->service = Service::create([
            'user_id' => $this->master->id,
            'name' => 'Маникюр',
            'base_price' => 2000,
            'cost' => 500,
            'duration_min' => 60,
        ]);

        $this->landing = Landing::create([
            'user_id' => $this->master->id,
            'title' => 'Студия Анны',
            'type' => 'general',
            'landing' => app(TemplateRegistry::class)->defaultTemplate('general'),
            'slug' => 'studio-anny',
            'settings' => ['primary_color' => 'indigo', 'background_type' => 'preset', 'show_all_services' => true, 'address' => 'Москва, Тверская 10'],
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'client_name' => 'Мария',
            'client_phone' => '+7(911)555-66-77',
            'service_id' => $this->service->id,
            'date' => '2026-10-05',
            'time' => '10:00',
            'message' => 'Первый раз',
        ], $override);
    }

    public function test_availability_lists_the_masters_free_days_and_windows(): void
    {
        $response = $this->getJson('/l/studio-anny/availability')->assertOk();

        $days = collect($response->json('data.days'))->keyBy('date');

        $this->assertSame(['10:00', '11:00', '12:00'], $days['2026-10-05']['slots']);
        $this->assertSame(['10:00', '11:00'], $days['2026-10-06']['slots']);
        // Days off are not offered.
        $this->assertFalse($days->has('2026-10-07'));
        $this->assertSame('Europe/Moscow', $response->json('data.timezone'));
    }

    public function test_availability_drops_windows_that_are_already_booked(): void
    {
        $this->postJson('/l/studio-anny/book', $this->payload())->assertCreated();

        $slots = collect($this->getJson('/l/studio-anny/availability?service_id=' . $this->service->id)->json('data.days'))
            ->keyBy('date')['2026-10-05']['slots'];

        $this->assertNotContains('10:00', $slots);
        $this->assertContains('11:00', $slots);
    }

    public function test_booking_creates_the_order_the_appointment_and_the_client(): void
    {
        $response = $this->postJson('/l/studio-anny/book', $this->payload())->assertCreated();

        $response->assertJsonPath('data.kind', 'booked')
            ->assertJsonPath('data.time', '10:00')
            ->assertJsonPath('data.service', 'Маникюр')
            ->assertJsonPath('data.address', 'Москва, Тверская 10');

        $order = Order::query()->where('master_id', $this->master->id)->firstOrFail();
        $this->assertSame('landing', $order->source);
        $this->assertSame('new', $order->status);
        $this->assertSame('Первый раз', $order->note);
        // 10:00 Moscow is 07:00 UTC; the calendar reads it back in the master's timezone.
        $this->assertSame('2026-10-05 10:00', $order->scheduled_at->copy()->timezone('Europe/Moscow')->format('Y-m-d H:i'));

        $this->assertSame(1, Appointment::query()->where('user_id', $this->master->id)->count());

        $client = Client::query()->where('user_id', $this->master->id)->firstOrFail();
        $this->assertSame('Мария', $client->name);
        $this->assertNotNull($client->client_user_id);

        $request = LandingRequest::query()->where('landing_id', $this->landing->id)->firstOrFail();
        $this->assertSame('booked', $request->status);
        $this->assertSame($order->id, $request->meta['order_id']);
    }

    public function test_booking_without_a_service_is_allowed(): void
    {
        $this->postJson('/l/studio-anny/book', $this->payload(['service_id' => null]))
            ->assertCreated()
            ->assertJsonPath('data.service', null);

        $this->assertSame([], Order::query()->firstOrFail()->services);
    }

    public function test_a_taken_or_out_of_schedule_window_is_refused(): void
    {
        $this->postJson('/l/studio-anny/book', $this->payload())->assertCreated();

        // Same window again.
        $this->postJson('/l/studio-anny/book', $this->payload(['client_phone' => '+7(922)000-00-00']))
            ->assertStatus(422)->assertJsonValidationErrors('time');

        // Outside the schedule (a day off, and an hour nobody works).
        $this->postJson('/l/studio-anny/book', $this->payload(['date' => '2026-10-07']))->assertStatus(422)->assertJsonValidationErrors('time');
        $this->postJson('/l/studio-anny/book', $this->payload(['time' => '15:00']))->assertStatus(422)->assertJsonValidationErrors('time');

        $this->assertSame(1, Order::query()->count());
    }

    public function test_a_time_in_the_past_is_refused(): void
    {
        Carbon::setTestNow('2026-10-05 11:30:00');

        $this->postJson('/l/studio-anny/book', $this->payload(['time' => '10:00']))
            ->assertStatus(422)->assertJsonValidationErrors('time');
    }

    public function test_overlapping_windows_are_refused_for_a_long_service(): void
    {
        $long = Service::create([
            'user_id' => $this->master->id, 'name' => 'Наращивание', 'base_price' => 6000, 'cost' => 1000, 'duration_min' => 120,
        ]);

        $this->postJson('/l/studio-anny/book', $this->payload(['service_id' => $long->id, 'time' => '10:00']))->assertCreated();

        // 11:00 is inside the two hours already taken.
        $this->postJson('/l/studio-anny/book', $this->payload(['client_phone' => '+7(933)000-00-00', 'time' => '11:00']))
            ->assertStatus(422)->assertJsonValidationErrors('time');
    }

    public function test_input_is_validated(): void
    {
        $this->postJson('/l/studio-anny/book', $this->payload(['client_name' => '']))->assertStatus(422)->assertJsonValidationErrors('client_name');
        $this->postJson('/l/studio-anny/book', $this->payload(['client_phone' => '123']))->assertStatus(422)->assertJsonValidationErrors('client_phone');
        $this->postJson('/l/studio-anny/book', $this->payload(['date' => 'завтра']))->assertStatus(422)->assertJsonValidationErrors('date');
        $this->postJson('/l/studio-anny/book', $this->payload(['time' => '25:99']))->assertStatus(422)->assertJsonValidationErrors('time');
        $this->postJson('/l/studio-anny/book', $this->payload(['date' => '2027-06-01']))->assertStatus(422)->assertJsonValidationErrors('date');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_a_service_the_page_does_not_offer_is_refused(): void
    {
        $this->landing->update(['settings' => array_merge($this->landing->settings, ['show_all_services' => false, 'service_ids' => [$this->service->id]])]);

        $other = Service::create(['user_id' => $this->master->id, 'name' => 'Педикюр', 'base_price' => 2500, 'cost' => 700, 'duration_min' => 60]);
        $strangers = Service::create(['user_id' => User::factory()->create()->id, 'name' => 'Чужая', 'base_price' => 1, 'cost' => 1, 'duration_min' => 30]);

        $this->postJson('/l/studio-anny/book', $this->payload(['service_id' => $other->id]))->assertStatus(422)->assertJsonValidationErrors('service_id');
        $this->postJson('/l/studio-anny/book', $this->payload(['service_id' => $strangers->id]))->assertStatus(422)->assertJsonValidationErrors('service_id');
        $this->getJson('/l/studio-anny/availability?service_id=' . $other->id)->assertStatus(422);
    }

    public function test_one_phone_cannot_hold_more_than_three_upcoming_bookings(): void
    {
        foreach (['10:00', '11:00', '12:00'] as $time) {
            $this->postJson('/l/studio-anny/book', $this->payload(['time' => $time]))->assertCreated();
        }

        $this->postJson('/l/studio-anny/book', $this->payload(['date' => '2026-10-06', 'time' => '10:00']))
            ->assertStatus(422)->assertJsonValidationErrors('client_phone');
    }

    public function test_an_inactive_or_unknown_page_takes_no_bookings(): void
    {
        $this->landing->update(['is_active' => false]);

        $this->getJson('/l/studio-anny/availability')->assertNotFound();
        $this->postJson('/l/studio-anny/book', $this->payload())->assertNotFound();
        $this->postJson('/l/nope/book', $this->payload())->assertNotFound();
    }

    public function test_a_plain_request_without_a_time_still_works(): void
    {
        $this->postJson('/l/studio-anny/request', [
            'client_name' => 'Ольга', 'client_phone' => '+7(999)777-66-55', 'service_id' => $this->service->id, 'message' => null,
        ])->assertCreated();

        $this->assertSame(0, Order::query()->count());
        $this->assertSame('new', LandingRequest::query()->firstOrFail()->status);
    }

    public function test_every_template_includes_the_booking_widget_and_the_phone_mask(): void
    {
        foreach (app(TemplateRegistry::class)->layouts() as $layout) {
            $landing = Landing::create([
                'user_id' => $this->master->id, 'title' => 'T', 'type' => 'general', 'landing' => $layout['templates']['general'],
                'slug' => 'page-' . $layout['slug'], 'settings' => ['primary_color' => 'indigo', 'background_type' => 'preset', 'show_all_services' => true],
                'is_active' => true,
            ]);

            $this->get('/l/' . $landing->slug)
                ->assertOk()
                ->assertSee('landing-booking/booking.js', false)
                ->assertSee('data-phone-mask', false)
                // The URL sits in a JSON config, where slashes are escaped.
                ->assertSee(str_replace('/', '\/', '/l/' . $landing->slug . '/book'), false);
        }
    }
}
