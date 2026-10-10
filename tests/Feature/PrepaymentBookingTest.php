<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\ClientNotification;
use App\Models\Landing;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PrepaymentRule;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\Booking\ClientBookingService;
use App\Services\Integrations\IntegrationCatalog;
use App\Services\Landing\TemplateRegistry;
use App\Services\YooKassaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * Booking that has to be prepaid: held unpaid, the slot stays blocked, nobody
 * is told until the money arrives.
 */
class PrepaymentBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $master;

    private Service $service;

    private Setting $settings;

    public bool $gatewayDown = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA ignore_check_constraints = ON');
        }

        Carbon::setTestNow('2026-10-05 08:00:00');

        $this->master = User::factory()->create(['timezone' => 'Europe/Moscow']);

        $this->settings = Setting::create([
            'user_id' => $this->master->id,
            'work_hours' => ['mon' => ['10:00', '11:00', '12:00']],
            'yookassa_shop_id' => '123456',
            'yookassa_secret_key' => 'live_secret',
            'deposit_policy' => ['enabled' => true, 'default_mode' => 'percent', 'default_value' => 30],
        ]);
        $this->verifyShop();

        $this->service = Service::create([
            'user_id' => $this->master->id,
            'name' => 'Маникюр',
            'base_price' => 2000,
            'cost' => 500,
            'duration_min' => 60,
        ]);

        Landing::create([
            'user_id' => $this->master->id,
            'title' => 'Студия',
            'type' => 'general',
            'landing' => app(TemplateRegistry::class)->defaultTemplate('general'),
            'slug' => 'studio',
            'settings' => ['primary_color' => 'indigo', 'background_type' => 'preset', 'show_all_services' => true],
            'is_active' => true,
        ]);

        $test = $this;
        $this->app->bind(YooKassaService::class, fn () => new class($test) extends YooKassaService {
            public function __construct(private $test)
            {
            }

            public function enabled(): bool
            {
                return true;
            }

            public function createBookingPayment(\App\Models\Order $order, float $amount, string $returnUrl, string $description): array
            {
                if ($this->test->gatewayDown) {
                    throw new RuntimeException('down');
                }

                return [
                    'id' => 'pay-' . $order->id,
                    'status' => 'pending',
                    'paid' => false,
                    'amount' => number_format($amount, 2, '.', ''),
                    'currency' => 'RUB',
                    'confirmation_url' => 'https://yookassa.test/pay/' . $order->id . '?return=' . urlencode($returnUrl),
                ];
            }
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function verifyShop(): void
    {
        $this->settings->refresh();
        $this->settings->forceFill(['integration_checks' => ['yookassa' => [
            'ok' => true,
            'fingerprint' => IntegrationCatalog::fingerprint($this->settings, 'yookassa'),
        ]]])->save();
    }

    private function policy(array $policy): void
    {
        $this->settings->forceFill(['deposit_policy' => $policy])->save();
    }

    private function book(array $override = [])
    {
        return $this->postJson('/l/studio/book', array_merge([
            'client_name' => 'Мария',
            'client_phone' => '+7(911)555-66-77',
            'service_id' => $this->service->id,
            'date' => '2026-10-05',
            'time' => '10:00',
        ], $override));
    }

    public function test_a_prepaid_booking_is_held_unpaid_with_the_amount_and_a_payment_link(): void
    {
        $this->book()->assertCreated()
            ->assertJsonPath('data.kind', 'payment_required')
            ->assertJsonPath('data.payment.amount', 600)
            ->assertJsonPath('data.payment.expires_at', now()->addMinutes(15)->toIso8601String());

        $order = Order::firstOrFail();
        $this->assertSame('awaiting', $order->payment_status);
        $this->assertSame('default', $order->prepay_rule['source']);
        $this->assertEquals(600, Appointment::firstOrFail()->deposit_amount);

        $payment = Payment::firstOrFail();
        $this->assertSame($order->id, $payment->order_id);
        $this->assertSame($this->master->id, $payment->user_id);
        $this->assertStringContainsString('/l/studio/paid/', $payment->confirmation_url ? urldecode($payment->confirmation_url) : '');

        // Nobody is told about a booking that is not paid yet.
        $this->assertSame(0, ClientNotification::count());
        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_an_unpaid_booking_still_blocks_its_slot(): void
    {
        $this->book()->assertCreated();

        $slots = collect($this->getJson('/l/studio/availability?service_id=' . $this->service->id)->json('data.days'))
            ->keyBy('date')['2026-10-05']['slots'];

        $this->assertNotContains('10:00', $slots);
    }

    public function test_paying_turns_it_into_an_ordinary_booking_and_tells_everyone(): void
    {
        $this->book()->assertCreated();

        app(ClientBookingService::class)->completePaidBooking(Payment::firstOrFail());

        $this->assertSame(1, ClientNotification::count());
        $this->assertGreaterThan(0, DB::table('notifications')->count());
    }

    public function test_no_prepayment_when_the_shop_is_not_verified_or_the_switch_is_off(): void
    {
        $this->settings->forceFill(['integration_checks' => null])->save();
        $this->book()->assertCreated()->assertJsonPath('data.kind', 'booked');

        $this->verifyShop();
        $this->policy(['enabled' => false, 'default_mode' => 'percent', 'default_value' => 30]);
        $this->book(['time' => '11:00'])->assertCreated()->assertJsonPath('data.kind', 'booked');

        $this->assertSame(0, Payment::count());
    }

    public function test_changing_the_shop_keys_drops_the_verification(): void
    {
        $this->settings->forceFill(['yookassa_shop_id' => '999999'])->save();

        $this->book()->assertCreated()->assertJsonPath('data.kind', 'booked');
    }

    public function test_a_service_can_opt_out_or_ask_for_its_own_amount(): void
    {
        $this->service->update(['prepay_mode' => 'none']);
        $this->book()->assertCreated()->assertJsonPath('data.kind', 'booked');

        $this->service->update(['prepay_mode' => 'fixed', 'prepay_value' => 900]);
        $this->book(['time' => '11:00'])->assertCreated()
            ->assertJsonPath('data.payment.amount', 900);
    }

    public function test_the_largest_matching_rule_wins_and_never_exceeds_the_price(): void
    {
        PrepaymentRule::create([
            'user_id' => $this->master->id, 'name' => 'Новый год', 'type' => 'period',
            'starts_on' => '2026-10-01', 'ends_on' => '2026-10-31', 'mode' => 'fixed', 'value' => 5000,
        ]);
        PrepaymentRule::create([
            'user_id' => $this->master->id, 'name' => 'Не сейчас', 'type' => 'period',
            'starts_on' => '2026-12-01', 'ends_on' => '2026-12-31', 'mode' => 'fixed', 'value' => 1,
        ]);

        $this->book()->assertCreated()->assertJsonPath('data.payment.amount', 2000);
        $this->assertSame('Новый год', Order::firstOrFail()->prepay_rule['label']);
    }

    public function test_a_weekday_rule_matches_only_its_days(): void
    {
        $this->policy(['enabled' => true]);
        PrepaymentRule::create([
            'user_id' => $this->master->id, 'name' => 'Понедельники', 'type' => 'weekday',
            'weekdays' => [1], 'mode' => 'percent', 'value' => 50,
        ]);

        $this->book()->assertCreated()->assertJsonPath('data.payment.amount', 1000);
    }

    public function test_client_override_always_asks_and_never_does_not(): void
    {
        $this->policy(['enabled' => true]);
        $client = Client::create(['user_id' => $this->master->id, 'name' => 'Мария', 'phone' => '+7(911)555-66-77', 'prepay_override' => 'always']);

        $this->book()->assertCreated()->assertJsonPath('data.payment.amount', 600); // 30 % fallback

        $client->update(['prepay_override' => 'never']);
        $this->policy(['enabled' => true, 'default_mode' => 'percent', 'default_value' => 30]);
        $this->book(['time' => '11:00'])->assertCreated()->assertJsonPath('data.kind', 'booked');
    }

    public function test_the_general_rule_can_be_limited_to_new_clients(): void
    {
        $this->policy(['enabled' => true, 'default_mode' => 'fixed', 'default_value' => 500, 'new_clients_only' => true]);

        $this->book()->assertCreated()->assertJsonPath('data.payment.amount', 500);

        $user = User::where('phone', '+79115556677')->firstOrFail();
        Order::query()->update(['status' => 'completed']);
        $this->assertSame($user->id, Order::firstOrFail()->client_id);

        $this->book(['time' => '11:00'])->assertCreated()->assertJsonPath('data.kind', 'booked');
    }

    public function test_repeated_no_shows_switch_prepayment_on(): void
    {
        $this->policy(['enabled' => true, 'no_show_threshold' => 2]);
        $this->book()->assertCreated()->assertJsonPath('data.kind', 'booked');

        $user = User::where('phone', '+79115556677')->firstOrFail();
        foreach ([1, 2] as $_) {
            Order::create([
                'master_id' => $this->master->id, 'client_id' => $user->id, 'services' => [],
                'scheduled_at' => now()->subDays(3), 'total_price' => 100, 'status' => 'no_show',
            ]);
        }

        $this->book(['time' => '11:00'])->assertCreated()
            ->assertJsonPath('data.payment.amount', 600);
        $this->assertSame('no_shows', Order::latest('id')->first()->prepay_rule['source']);
    }

    public function test_an_unpaid_hold_is_not_picked_up_by_visit_reminders(): void
    {
        $this->book()->assertCreated();
        $held = Order::firstOrFail();

        $this->assertFalse(Order::query()->notAwaitingPayment()->whereKey($held->id)->exists());

        $held->update(['payment_status' => 'paid']);
        $this->assertTrue(Order::query()->notAwaitingPayment()->whereKey($held->id)->exists());
    }

    public function test_a_gateway_failure_leaves_no_booking_behind(): void
    {
        $this->gatewayDown = true;

        $this->book()->assertStatus(503);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, Appointment::count());
        $this->assertSame(0, Payment::count());
    }

    public function test_the_client_app_gets_the_payment_in_the_booking_response(): void
    {
        $client = Client::create(['user_id' => $this->master->id, 'name' => 'Мария', 'phone' => '79115556677', 'email' => 'm@example.com']);
        Sanctum::actingAs($client);

        $this->postJson('/api/v1/client/appointments', ['service_id' => $this->service->id, 'date' => '2026-10-05', 'time' => '10:00'])
            ->assertCreated()
            ->assertJsonPath('data.payment.amount', 600)
            ->assertJsonStructure(['data' => ['payment' => ['confirmation_url', 'expires_at']]]);

        $this->assertSame('awaiting', Order::firstOrFail()->payment_status);
    }
}
