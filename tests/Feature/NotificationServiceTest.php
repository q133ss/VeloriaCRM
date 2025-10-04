<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_persists_action_url_when_supported(): void
    {
        $user = User::factory()->create();

        $notification = app(NotificationService::class)->send(
            $user->id,
            'Заголовок',
            'Текст',
            '/orders/1/start-confirmation'
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertSame('/orders/1/start-confirmation', $notification->action_url);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'action_url' => '/orders/1/start-confirmation',
        ]);
    }

    public function test_send_skips_action_url_when_column_missing(): void
    {
        $user = User::factory()->create();

        Schema::partialMock()
            ->shouldReceive('hasColumn')
            ->once()
            ->with('notifications', 'action_url')
            ->andReturnFalse();

        $notification = app(NotificationService::class)->send(
            $user->id,
            'Заголовок',
            'Текст',
            '/orders/1/start-confirmation'
        );

        $this->assertNull($notification->action_url);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'action_url' => null,
        ]);

        Schema::clearResolvedInstance('schema');
    }
}
