<?php

namespace Tests\Feature;

use App\Mail\ClientMagicLinkMail;
use App\Mail\ClientOtpCodeMail;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientPortalAuthAndBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_client_can_login_via_email_code_and_book_appointment(): void
    {
        $master = User::factory()->create();

        $category = ServiceCategory::create([
            'user_id' => $master->id,
            'name' => 'Hair',
        ]);

        $service = Service::create([
            'user_id' => $master->id,
            'category_id' => $category->id,
            'name' => 'Haircut',
            'base_price' => 1500,
            'cost' => 500,
            'duration_min' => 60,
        ]);

        $date = Carbon::now(config('app.timezone'))->addDay()->format('Y-m-d');
        $dayKey = strtolower(Carbon::parse($date, config('app.timezone'))->format('D'));

        Setting::create([
            'user_id' => $master->id,
            'work_hours' => [
                $dayKey => ['10:00', '11:00'],
            ],
        ]);

        Client::create([
            'user_id' => $master->id,
            'name' => 'Client',
            'email' => 'client@example.com',
            'phone' => '79518677099',
        ]);

        Mail::fake();

        $loginResponse = $this->postJson('/api/v1/client/login', [
            'email' => 'client@example.com',
        ]);

        $loginResponse->assertOk()
            ->assertJsonPath('data.expires_in', 600);

        $verificationId = $loginResponse->json('data.verification_id');
        $this->assertNotEmpty($verificationId);

        $code = null;
        Mail::assertSent(ClientOtpCodeMail::class, function (ClientOtpCodeMail $mail) use (&$code) {
            $code = $mail->code;
            return true;
        });
        $this->assertNotEmpty($code);

        $verifyResponse = $this->postJson('/api/v1/client/login/verify', [
            'verification_id' => $verificationId,
            'code' => $code,
        ]);

        $verifyResponse->assertOk()
            ->assertJsonPath('data.client.email', 'client@example.com')
            ->assertJsonPath('data.master.id', $master->id);

        $token = $verifyResponse->json('data.token');
        $this->assertNotEmpty($token);

        $me = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/client/me');
        $me->assertOk()->assertJsonPath('data.client.email', 'client@example.com');

        $cats = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/client/service-categories');
        $cats->assertOk()->assertJsonPath('data.categories.0.id', $category->id);

        $services = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/client/services?category_id=' . $category->id);
        $services->assertOk()->assertJsonPath('data.services.0.id', $service->id);

        $slots = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/client/services/' . $service->id . '/slots?date=' . $date);
        $slots->assertOk()->assertJsonPath('data.slots.0', '10:00');

        $book = $this->withHeader('Authorization', 'Bearer ' . $token)->postJson('/api/v1/client/appointments', [
            'service_id' => $service->id,
            'date' => $date,
            'time' => '10:00',
        ]);

        $book->assertCreated()
            ->assertJsonPath('data.appointment.user_id', $master->id);

        $this->assertDatabaseHas('appointments', [
            'user_id' => $master->id,
            'client_id' => Client::query()->where('user_id', $master->id)->where('email', 'client@example.com')->value('id'),
            'status' => 'scheduled',
        ]);

        $this->assertDatabaseHas('orders', [
            'master_id' => $master->id,
            'source' => 'client_portal',
            'status' => 'new',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $master->id,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/services')->assertStatus(401);
    }

    public function test_client_can_load_generic_slots_and_book_without_service(): void
    {
        $master = User::factory()->create([
            'timezone' => 'Europe/Moscow',
        ]);

        $date = Carbon::now('Europe/Moscow')->addDay()->format('Y-m-d');
        $dayKey = strtolower(Carbon::parse($date, 'Europe/Moscow')->format('D'));

        Setting::create([
            'user_id' => $master->id,
            'work_hours' => [
                $dayKey => ['10:00', '11:00'],
            ],
        ]);

        $client = Client::create([
            'user_id' => $master->id,
            'name' => 'Portal Client',
            'phone' => '79990000002',
            'email' => 'portal-noservice@example.com',
        ]);

        Sanctum::actingAs($client);

        $slots = $this->getJson('/api/v1/client/slots?date=' . $date);
        $slots->assertOk()
            ->assertJsonPath('data.service_id', null)
            ->assertJsonPath('data.duration_min', 60)
            ->assertJsonPath('data.slots.0', '10:00');

        $book = $this->postJson('/api/v1/client/appointments', [
            'date' => $date,
            'time' => '10:00',
            'note' => 'Подберу услугу на месте',
        ]);

        $book->assertCreated()
            ->assertJsonPath('data.appointment.user_id', $master->id)
            ->assertJsonPath('data.appointment.meta.service_specified', false)
            ->assertJsonPath('data.appointment.meta.service_label', 'Услуга уточняется');

        $appointment = \App\Models\Appointment::query()->latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertSame([], $appointment->service_ids ?? []);

        $order = \App\Models\Order::query()->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame('client_portal', $order->source);
        $this->assertSame(60, $order->duration_forecast);
        // Empty, like a booking the master makes by hand for a client who has
        // not decided. The stand-in service that used to live here was read as
        // a real one by everything counting a client's history — including the
        // return-message draft, which wrote its name back to the client.
        $this->assertSame([], $order->services);
        $this->assertSame(0.0, (float) $order->total_price);
    }

    public function test_client_can_list_own_appointments_split_into_upcoming_and_past(): void
    {
        $master = User::factory()->create(['timezone' => 'Europe/Moscow']);

        $service = Service::create([
            'user_id' => $master->id,
            'name' => 'Manicure',
            'base_price' => 2000,
            'cost' => 600,
            'duration_min' => 60,
        ]);

        $client = Client::create([
            'user_id' => $master->id,
            'name' => 'Client',
            'email' => 'appointments@example.com',
            'phone' => '79518677096',
        ]);

        $otherClient = Client::create([
            'user_id' => $master->id,
            'name' => 'Other client',
            'email' => 'other@example.com',
            'phone' => '79518677095',
        ]);

        $future = Appointment::create([
            'user_id' => $master->id,
            'client_id' => $client->id,
            'service_ids' => [$service->id],
            'starts_at' => Carbon::now('Europe/Moscow')->addDay()->setTime(10, 0),
            'ends_at' => Carbon::now('Europe/Moscow')->addDay()->setTime(11, 0),
            'status' => 'scheduled',
            'meta' => ['service_label' => 'Manicure', 'service_specified' => true],
        ]);

        $past = Appointment::create([
            'user_id' => $master->id,
            'client_id' => $client->id,
            'service_ids' => [],
            'starts_at' => Carbon::now('Europe/Moscow')->subDay()->setTime(9, 0),
            'ends_at' => Carbon::now('Europe/Moscow')->subDay()->setTime(10, 0),
            'status' => 'scheduled',
            'meta' => ['service_label' => 'Услуга уточняется', 'service_specified' => false],
        ]);

        // Belongs to a different client of the same master — must never leak in.
        Appointment::create([
            'user_id' => $master->id,
            'client_id' => $otherClient->id,
            'service_ids' => [],
            'starts_at' => Carbon::now('Europe/Moscow')->addDay()->setTime(12, 0),
            'ends_at' => Carbon::now('Europe/Moscow')->addDay()->setTime(13, 0),
            'status' => 'scheduled',
            'meta' => [],
        ]);

        Sanctum::actingAs($client);

        $response = $this->getJson('/api/v1/client/appointments');

        $response->assertOk()
            ->assertJsonCount(2, 'data.appointments')
            ->assertJsonPath('data.appointments.0.id', $future->id)
            ->assertJsonPath('data.appointments.0.is_upcoming', true)
            ->assertJsonPath('data.appointments.0.service_label', 'Manicure')
            ->assertJsonPath('data.appointments.1.id', $past->id)
            ->assertJsonPath('data.appointments.1.is_upcoming', false)
            ->assertJsonPath('data.appointments.1.service_label', 'Услуга уточняется');
    }

    public function test_client_can_login_via_email_code_when_client_exists(): void
    {
        $master = User::factory()->create();

        Client::create([
            'user_id' => $master->id,
            'name' => 'client',
            'email' => 'client@example.com',
            'phone' => '79518677099',
        ]);

        Mail::fake();

        $loginResponse = $this->postJson('/api/v1/client/login', [
            'email' => 'client@example.com',
        ]);

        $loginResponse->assertOk();

        $verificationId = $loginResponse->json('data.verification_id');
        $this->assertNotEmpty($verificationId);

        $code = null;
        Mail::assertSent(ClientOtpCodeMail::class, function (ClientOtpCodeMail $mail) use (&$code) {
            $code = $mail->code;
            return true;
        });

        $verify = $this->postJson('/api/v1/client/login/verify', [
            'verification_id' => $verificationId,
            'code' => $code,
        ]);

        $verify->assertOk()
            ->assertJsonPath('data.client.email', 'client@example.com')
            ->assertJsonPath('data.master.id', $master->id);
    }

    public function test_login_returns_master_selection_when_email_belongs_to_multiple_masters(): void
    {
        $firstMaster = User::factory()->create([
            'name' => 'Mira',
        ]);
        $secondMaster = User::factory()->create([
            'name' => 'Olga',
        ]);

        Client::create([
            'user_id' => $firstMaster->id,
            'name' => 'Existing',
            'email' => 'client@example.com',
            'phone' => '79518677099',
        ]);

        Client::create([
            'user_id' => $secondMaster->id,
            'name' => 'Existing',
            'email' => 'client@example.com',
            'phone' => '79518677100',
        ]);

        Mail::fake();

        $login = $this->postJson('/api/v1/client/login', [
            'email' => 'client@example.com',
        ]);

        $login->assertOk();

        $verificationId = $login->json('data.verification_id');
        $this->assertNotEmpty($verificationId);

        $code = null;
        Mail::assertSent(ClientOtpCodeMail::class, function (ClientOtpCodeMail $mail) use (&$code) {
            $code = $mail->code;
            return true;
        });
        $this->assertNotEmpty($code);

        $verify = $this->postJson('/api/v1/client/login/verify', [
            'verification_id' => $verificationId,
            'code' => $code,
        ]);

        $verify->assertOk()
            ->assertJsonPath('data.requires_master_selection', true)
            ->assertJsonCount(2, 'data.masters');

        $selectionToken = $verify->json('data.selection_token');
        $this->assertNotEmpty($selectionToken);

        $chooseMaster = $this->postJson('/api/v1/client/login/verify', [
            'selection_token' => $selectionToken,
            'master_id' => $secondMaster->id,
        ]);

        $chooseMaster->assertOk()
            ->assertJsonPath('data.client.email', 'client@example.com')
            ->assertJsonPath('data.master.id', $secondMaster->id)
            ->assertJsonPath('data.master.name', 'Olga');
    }

    public function test_client_can_login_via_magic_link(): void
    {
        $master = User::factory()->create();

        Client::create([
            'user_id' => $master->id,
            'name' => 'Client',
            'email' => 'client@example.com',
            'phone' => '79518677099',
        ]);

        Mail::fake();

        $response = $this->postJson('/api/v1/client/login/magic-link', [
            'email' => 'client@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.expires_in', 600);

        $verificationId = $response->json('data.verification_id');
        $this->assertNotEmpty($verificationId);

        $link = null;
        Mail::assertSent(ClientMagicLinkMail::class, function (ClientMagicLinkMail $mail) use (&$link) {
            $link = $mail->link;
            return true;
        });
        $this->assertNotEmpty($link);
        $this->assertStringStartsWith(
            'https://' . config('services.client_portal.app_link_host') . '/auth/verify',
            $link,
        );

        parse_str((string) parse_url($link, PHP_URL_QUERY), $params);
        $this->assertSame($verificationId, $params['vid']);
        $this->assertNotEmpty($params['code']);

        // The link's own path is the browser-fallback redirect page, not the API —
        // Android opens the app directly only once the App Link is verified.
        $redirect = $this->get('/auth/verify?vid=' . $params['vid'] . '&code=' . $params['code']);
        $redirect->assertOk()
            ->assertSee('veloriaclient://auth/verify?vid=' . $params['vid'] . '&code=' . $params['code'])
            ->assertSee($params['code']);

        $verify = $this->postJson('/api/v1/client/login/verify', [
            'verification_id' => $params['vid'],
            'code' => $params['code'],
        ]);

        $verify->assertOk()
            ->assertJsonPath('data.client.email', 'client@example.com')
            ->assertJsonPath('data.master.id', $master->id);
    }

    public function test_assetlinks_json_exposes_the_android_package(): void
    {
        config(['services.client_portal.android_package' => 'ru.veloria.client']);
        config(['services.client_portal.android_sha256_fingerprints' => 'AA:BB, CC:DD']);

        $response = $this->getJson('/.well-known/assetlinks.json');

        $response->assertOk()
            ->assertJsonPath('0.target.package_name', 'ru.veloria.client')
            ->assertJsonPath('0.target.sha256_cert_fingerprints', ['AA:BB', 'CC:DD']);
    }

    public function test_me_endpoint_returns_the_resolved_master(): void
    {
        $master = User::factory()->create(['name' => 'Mira']);

        $client = Client::create([
            'user_id' => $master->id,
            'name' => 'Client',
            'email' => 'client@example.com',
            'phone' => '79518677099',
        ]);

        Sanctum::actingAs($client);

        $this->getJson('/api/v1/client/me')
            ->assertOk()
            ->assertJsonPath('data.master.id', $master->id)
            ->assertJsonPath('data.master.name', 'Mira');
    }

    public function test_me_endpoint_exposes_branding_for_a_pro_master(): void
    {
        $master = User::factory()->create(['name' => 'Mira']);
        $plan = Plan::create(['name' => 'pro', 'price' => 999]);
        $master->plans()->attach($plan->id, ['ends_at' => Carbon::now()->addMonth()]);

        Setting::create([
            'user_id' => $master->id,
            'branding' => [
                'app_display_name' => 'Mira Beauty',
                'primary_color' => '#FF00FC',
                'secondary_color' => '#111111',
                'logo_url' => 'https://cdn.example.com/mira-logo.png',
            ],
        ]);

        $client = Client::create([
            'user_id' => $master->id,
            'name' => 'Client',
            'email' => 'client-pro@example.com',
            'phone' => '79518677098',
        ]);

        Sanctum::actingAs($client);

        $this->getJson('/api/v1/client/me')
            ->assertOk()
            ->assertJsonPath('data.master.has_custom_branding', true)
            ->assertJsonPath('data.master.branding.app_display_name', 'Mira Beauty')
            ->assertJsonPath('data.master.branding.primary_color', '#FF00FC')
            ->assertJsonPath('data.master.branding.logo_url', 'https://cdn.example.com/mira-logo.png');
    }

    /**
     * A Lite master's clients see the default look no matter what is stored —
     * hasProAccess() gates this here, not just in the settings UI, so a
     * downgrade takes effect for clients immediately.
     */
    public function test_me_endpoint_hides_branding_for_a_lite_master_even_if_stored(): void
    {
        $master = User::factory()->create(['name' => 'Lite Master']);

        Setting::create([
            'user_id' => $master->id,
            'branding' => [
                'app_display_name' => 'Should not leak',
                'primary_color' => '#000000',
                'secondary_color' => '#000000',
                'logo_url' => 'https://cdn.example.com/leaked-logo.png',
            ],
        ]);

        $client = Client::create([
            'user_id' => $master->id,
            'name' => 'Client',
            'email' => 'client-lite@example.com',
            'phone' => '79518677097',
        ]);

        Sanctum::actingAs($client);

        $this->getJson('/api/v1/client/me')
            ->assertOk()
            ->assertJsonPath('data.master.has_custom_branding', false)
            ->assertJsonPath('data.master.branding', null);
    }

    public function test_magic_link_rejects_unknown_email(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/client/login/magic-link', [
            'email' => 'nobody@example.com',
        ])->assertNotFound();

        Mail::assertNothingSent();
    }

    public function test_client_register_routes_are_not_available(): void
    {
        $this->postJson('/api/v1/client/register', [])->assertNotFound();
        $this->postJson('/api/v1/client/register/verify', [])->assertNotFound();
    }

    public function test_client_slots_support_shift_cycle_schedule(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-09 08:00:00', 'Europe/Moscow'));

        $master = User::factory()->create([
            'timezone' => 'Europe/Moscow',
        ]);

        $service = Service::create([
            'user_id' => $master->id,
            'name' => 'Color',
            'base_price' => 2000,
            'cost' => 600,
            'duration_min' => 60,
        ]);

        Setting::create([
            'user_id' => $master->id,
            'schedule_rules' => [
                'mode' => 'cycle',
                'cycle' => [
                    'anchor_date' => '2026-03-10',
                    'work_days' => 2,
                    'rest_days' => 2,
                    'slots' => ['09:00', '19:00'],
                ],
            ],
        ]);

        $client = Client::create([
            'user_id' => $master->id,
            'name' => 'Portal Client',
            'phone' => '79990000000',
            'email' => 'portal@example.com',
        ]);

        Sanctum::actingAs($client);

        try {
            $this->getJson('/api/v1/client/services/' . $service->id . '/slots?date=2026-03-10')
                ->assertOk()
                ->assertJsonPath('data.slots.0', '09:00')
                ->assertJsonPath('data.slots.1', '19:00');

            $this->getJson('/api/v1/client/services/' . $service->id . '/slots?date=2026-03-12')
                ->assertOk()
                ->assertJsonPath('data.slots', []);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_client_slots_support_custom_month_schedule_with_half_hour_slots(): void
    {
        // The schedule under test is pinned to a fixed date, so the clock has to be
        // pinned too: past slots are filtered out, and this test used to start
        // failing on its own once real time passed 2026-03-20.
        Carbon::setTestNow('2026-03-19 08:00:00');

        $master = User::factory()->create([
            'timezone' => 'Europe/Moscow',
        ]);

        $service = Service::create([
            'user_id' => $master->id,
            'name' => 'Styling',
            'base_price' => 2500,
            'cost' => 800,
            'duration_min' => 30,
        ]);

        Setting::create([
            'user_id' => $master->id,
            'schedule_rules' => [
                'mode' => 'monthly',
                'monthly' => [
                    'dates' => [
                        '2026-03-20' => ['09:00', '15:30', '19:00'],
                    ],
                ],
            ],
        ]);

        $client = Client::create([
            'user_id' => $master->id,
            'name' => 'Portal Client',
            'phone' => '79990000001',
            'email' => 'portal-month@example.com',
        ]);

        Sanctum::actingAs($client);

        try {
            $this->getJson('/api/v1/client/services/' . $service->id . '/slots?date=2026-03-20')
                ->assertOk()
                ->assertJsonPath('data.slots.1', '15:30');
        } finally {
            Carbon::setTestNow();
        }
    }
}
