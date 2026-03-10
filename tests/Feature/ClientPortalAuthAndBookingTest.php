<?php

namespace Tests\Feature;

use App\Mail\ClientOtpCodeMail;
use App\Models\Client;
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

    public function test_client_can_register_via_email_code_and_book_appointment(): void
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

        Mail::fake();

        $registerResponse = $this->postJson('/api/v1/client/register', [
            'master_id' => $master->id,
            'name' => 'Client',
            'email' => 'client@example.com',
            'phone' => '79518677099',
        ]);

        $registerResponse->assertOk()
            ->assertJsonPath('data.expires_in', 600);

        $verificationId = $registerResponse->json('data.verification_id');
        $this->assertNotEmpty($verificationId);

        $code = null;
        Mail::assertSent(ClientOtpCodeMail::class, function (ClientOtpCodeMail $mail) use (&$code) {
            $code = $mail->code;
            return true;
        });
        $this->assertNotEmpty($code);

        $verifyResponse = $this->postJson('/api/v1/client/register/verify', [
            'verification_id' => $verificationId,
            'code' => $code,
        ]);

        $verifyResponse->assertOk()
            ->assertJsonPath('data.client.email', 'client@example.com');

        $token = $verifyResponse->json('data.token');
        $this->assertNotEmpty($token);

        $this->assertDatabaseHas('clients', [
            'user_id' => $master->id,
            'email' => 'client@example.com',
            'phone' => '79518677099',
        ]);

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

        // Client token must not access master endpoints.
        $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/services')->assertStatus(401);
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
            'master_id' => $master->id,
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
            ->assertJsonPath('data.client.email', 'client@example.com');
    }

    public function test_register_returns_conflict_when_client_already_exists_for_master_and_email(): void
    {
        $master = User::factory()->create();

        Client::create([
            'user_id' => $master->id,
            'name' => 'Existing',
            'email' => 'client@example.com',
            'phone' => '79518677099',
        ]);

        Mail::fake();

        $register = $this->postJson('/api/v1/client/register', [
            'master_id' => $master->id,
            'name' => 'Client',
            'email' => 'client@example.com',
            'phone' => '79518677099',
        ]);

        $register->assertStatus(409)
            ->assertJsonPath('error.code', 'already_registered');
    }

    public function test_client_slots_support_shift_cycle_schedule(): void
    {
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

        $this->getJson('/api/v1/client/services/' . $service->id . '/slots?date=2026-03-10')
            ->assertOk()
            ->assertJsonPath('data.slots.0', '09:00')
            ->assertJsonPath('data.slots.1', '19:00');

        $this->getJson('/api/v1/client/services/' . $service->id . '/slots?date=2026-03-12')
            ->assertOk()
            ->assertJsonPath('data.slots', []);
    }

    public function test_client_slots_support_custom_month_schedule_with_half_hour_slots(): void
    {
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

        $this->getJson('/api/v1/client/services/' . $service->id . '/slots?date=2026-03-20')
            ->assertOk()
            ->assertJsonPath('data.slots.1', '15:30');
    }
}
