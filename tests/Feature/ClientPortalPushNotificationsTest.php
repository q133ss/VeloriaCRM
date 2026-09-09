<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\User;
use App\Services\AppointmentReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientPortalPushNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function makeProMaster(array $attributes = []): User
    {
        $master = User::factory()->create($attributes);
        $plan = Plan::firstOrCreate(['name' => 'pro'], ['price' => 999]);
        $master->plans()->attach($plan->id, ['ends_at' => Carbon::now()->addMonth()]);

        return $master;
    }

    public function test_client_can_register_a_device_token(): void
    {
        $master = User::factory()->create();

        $client = Client::create([
            'user_id' => $master->id,
            'name' => 'Client',
            'email' => 'client@example.com',
            'phone' => '79518677099',
        ]);

        Sanctum::actingAs($client);

        $response = $this->postJson('/api/v1/client/device-token', [
            'expo_push_token' => 'ExponentPushToken[abc123]',
        ]);

        $response->assertOk();

        $this->assertSame('ExponentPushToken[abc123]', $client->fresh()->expo_push_token);
        $this->assertNotNull($client->fresh()->expo_push_token_updated_at);
    }

    public function test_booking_confirmation_creates_a_client_notification_and_sends_a_push(): void
    {
        Http::fake([
            'exp.host/*' => Http::response(['data' => ['status' => 'ok']], 200),
        ]);

        $master = User::factory()->create();

        $category = ServiceCategory::create(['user_id' => $master->id, 'name' => 'Hair']);
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
            'work_hours' => [$dayKey => ['10:00', '11:00']],
        ]);

        $client = Client::create([
            'user_id' => $master->id,
            'name' => 'Client',
            'email' => 'client@example.com',
            'phone' => '79518677099',
            'expo_push_token' => 'ExponentPushToken[abc123]',
        ]);

        Sanctum::actingAs($client);

        $this->postJson('/api/v1/client/appointments', [
            'service_id' => $service->id,
            'date' => $date,
            'time' => '10:00',
        ])->assertCreated();

        $this->assertDatabaseHas('client_notifications', [
            'client_id' => $client->id,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'exp.host')
                && $request['to'] === 'ExponentPushToken[abc123]';
        });

        $notifications = $this->getJson('/api/v1/client/notifications');
        $notifications->assertOk()->assertJsonCount(1, 'data.notifications');

        $notificationId = $notifications->json('data.notifications.0.id');

        $this->postJson('/api/v1/client/notifications/mark-as-read', [
            'ids' => [$notificationId],
        ])->assertOk()->assertJsonPath('updated', 1);
    }

    public function test_publishing_a_master_post_notifies_every_client_of_that_master(): void
    {
        Http::fake();

        $master = $this->makeProMaster();

        $firstClient = Client::create([
            'user_id' => $master->id,
            'name' => 'First',
            'email' => 'first@example.com',
            'phone' => '79518677091',
        ]);

        $secondClient = Client::create([
            'user_id' => $master->id,
            'name' => 'Second',
            'email' => 'second@example.com',
            'phone' => '79518677092',
        ]);

        $otherMaster = User::factory()->create();
        $unrelatedClient = Client::create([
            'user_id' => $otherMaster->id,
            'name' => 'Unrelated',
            'email' => 'unrelated@example.com',
            'phone' => '79518677093',
        ]);

        Sanctum::actingAs($master);

        $this->postJson('/api/v1/master-posts', [
            'title' => 'Новая услуга',
            'body' => 'Добавили массаж лица.',
        ])->assertCreated();

        $this->assertDatabaseHas('client_notifications', ['client_id' => $firstClient->id]);
        $this->assertDatabaseHas('client_notifications', ['client_id' => $secondClient->id]);
        $this->assertDatabaseMissing('client_notifications', ['client_id' => $unrelatedClient->id]);
    }

    public function test_a_draft_master_post_does_not_notify_clients(): void
    {
        Http::fake();

        $master = $this->makeProMaster();

        $client = Client::create([
            'user_id' => $master->id,
            'name' => 'Client',
            'email' => 'client@example.com',
            'phone' => '79518677099',
        ]);

        Sanctum::actingAs($master);

        $this->postJson('/api/v1/master-posts', [
            'title' => 'Черновик',
            'body' => 'Пока не готово.',
            'is_published' => false,
        ])->assertCreated();

        $this->assertDatabaseMissing('client_notifications', ['client_id' => $client->id]);
    }

    public function test_appointment_reminder_command_notifies_once_for_appointments_tomorrow(): void
    {
        Http::fake();

        Carbon::setTestNow('2026-03-10 09:00:00');

        try {
            $master = User::factory()->create(['timezone' => 'Europe/Moscow']);

            $client = Client::create([
                'user_id' => $master->id,
                'name' => 'Client',
                'email' => 'client@example.com',
                'phone' => '79518677099',
            ]);

            $dueAppointment = Appointment::create([
                'user_id' => $master->id,
                'client_id' => $client->id,
                'service_ids' => [],
                'starts_at' => Carbon::now()->addHours(24)->addMinutes(10),
                'ends_at' => Carbon::now()->addHours(25),
                'status' => 'scheduled',
                'meta' => ['service_label' => 'Маникюр'],
            ]);

            $notDueAppointment = Appointment::create([
                'user_id' => $master->id,
                'client_id' => $client->id,
                'service_ids' => [],
                'starts_at' => Carbon::now()->addHours(48),
                'ends_at' => Carbon::now()->addHours(49),
                'status' => 'scheduled',
                'meta' => ['service_label' => 'Педикюр'],
            ]);

            /** @var AppointmentReminderService $service */
            $service = app(AppointmentReminderService::class);

            $firstRun = $service->dispatchDueReminders(Carbon::now());
            $this->assertSame(1, $firstRun['sent']);

            $this->assertNotNull($dueAppointment->fresh()->reminder_notified_at);
            $this->assertNull($notDueAppointment->fresh()->reminder_notified_at);

            $this->assertDatabaseHas('client_notifications', [
                'client_id' => $client->id,
                'title' => __('client_portal.notifications.appointment_reminder_title'),
            ]);

            // Running again in the same window must not send a second reminder.
            $secondRun = $service->dispatchDueReminders(Carbon::now());
            $this->assertSame(0, $secondRun['sent']);
        } finally {
            Carbon::setTestNow();
        }
    }
}
