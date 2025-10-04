<?php

namespace Tests\Feature;

use App\Jobs\SendOrderStartReminderJob;
use App\Models\Order;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_start_reminder_dispatches_job_with_expected_delay(): void
    {
        Carbon::setTestNow('2025-01-01 12:00:00');
        Queue::fake();

        $master = User::factory()->create();
        $client = User::factory()->create();

        $order = Order::query()->create([
            'master_id' => $master->id,
            'client_id' => $client->id,
            'services' => [[
                'id' => 1,
                'name' => 'Стрижка',
                'price' => 1500,
                'duration' => 60,
            ]],
            'scheduled_at' => Carbon::now()->addMinutes(5),
            'total_price' => 1500,
            'status' => 'new',
            'source' => 'manual',
        ]);

        app(OrderService::class)->scheduleStartReminder($order->fresh());

        Queue::assertPushed(SendOrderStartReminderJob::class, function (SendOrderStartReminderJob $job) use ($order) {
            return $job->orderId === $order->id
                && $job->scheduledAtTimestamp === $order->scheduled_at->getTimestamp()
                && $job->delay === 900;
        });

        Carbon::setTestNow();
    }

    public function test_send_order_start_reminder_job_creates_notification(): void
    {
        Carbon::setTestNow('2025-01-01 12:30:00');
        $master = User::factory()->create();
        $client = User::factory()->create();

        $order = Order::query()->create([
            'master_id' => $master->id,
            'client_id' => $client->id,
            'services' => [[
                'id' => 1,
                'name' => 'Стрижка',
                'price' => 1500,
                'duration' => 60,
            ]],
            'scheduled_at' => Carbon::now()->subMinutes(20),
            'total_price' => 1500,
            'status' => 'new',
            'source' => 'manual',
        ]);

        $job = new SendOrderStartReminderJob($order->id, $order->scheduled_at->getTimestamp());
        $job->handle(app(NotificationService::class));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $master->id,
            'title' => 'Подтвердите начало процедуры',
            'message' => 'Пожалуйста, подтвердите, что процедура началась!',
        ]);

        $this->assertNotNull($order->fresh()->start_confirmation_notified_at);

        Carbon::setTestNow();
    }
}
