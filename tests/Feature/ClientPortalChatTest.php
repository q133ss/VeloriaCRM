<?php

namespace Tests\Feature;

use App\Models\ChatThread;
use App\Models\Client;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientPortalChatTest extends TestCase
{
    use RefreshDatabase;

    private function makeClient(User $master, array $attributes = []): Client
    {
        return Client::create(array_merge([
            'user_id' => $master->id,
            'name' => 'Client',
            'email' => 'client@example.com',
            'phone' => '79518677099',
        ], $attributes));
    }

    public function test_lite_masters_client_can_open_chat(): void
    {
        // Unlike branding/news authoring, chat is open on every plan.
        $master = User::factory()->create();
        $client = $this->makeClient($master);

        Sanctum::actingAs($client);

        $this->getJson('/api/v1/client/chat')->assertOk();
    }

    public function test_client_can_send_a_message_and_master_is_notified(): void
    {
        $master = User::factory()->create();
        $client = $this->makeClient($master);

        Sanctum::actingAs($client);

        $response = $this->postJson('/api/v1/client/chat/messages', [
            'body' => 'Здравствуйте! Можно перенести запись?',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.messages.0.body', 'Здравствуйте! Можно перенести запись?')
            ->assertJsonPath('data.messages.0.from_me', true);

        $this->assertDatabaseHas('chat_threads', [
            'user_id' => $master->id,
            'client_id' => $client->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $master->id,
        ]);

        $notification = Notification::where('user_id', $master->id)->first();
        $this->assertSame(__('chat.master_notification.title'), $notification->title);
    }

    public function test_master_can_reply_and_client_is_notified(): void
    {
        $master = User::factory()->create();
        $client = $this->makeClient($master);

        Sanctum::actingAs($client);
        $this->postJson('/api/v1/client/chat/messages', ['body' => 'Привет!'])->assertCreated();

        $threadId = ChatThread::first()->id;

        Sanctum::actingAs($master);

        $index = $this->getJson('/api/v1/chat/threads');
        $index->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.unread', true);

        $reply = $this->postJson("/api/v1/chat/threads/{$threadId}/messages", [
            'body' => 'Добрый день! Да, конечно.',
        ]);

        $reply->assertOk()->assertJsonPath('data.messages.1.body', 'Добрый день! Да, конечно.');

        $this->assertDatabaseHas('client_notifications', [
            'client_id' => $client->id,
        ]);

        Sanctum::actingAs($client);
        $clientView = $this->getJson('/api/v1/client/chat');
        $clientView->assertOk()->assertJsonCount(2, 'data.messages');
    }

    public function test_master_cannot_view_another_masters_thread(): void
    {
        $master = User::factory()->create();
        $otherMaster = User::factory()->create();
        $client = $this->makeClient($master);

        Sanctum::actingAs($client);
        $this->postJson('/api/v1/client/chat/messages', ['body' => 'Привет!'])->assertCreated();
        $threadId = ChatThread::first()->id;

        Sanctum::actingAs($otherMaster);
        $this->getJson("/api/v1/chat/threads/{$threadId}")->assertNotFound();
    }

    public function test_message_requires_body_or_attachment(): void
    {
        $master = User::factory()->create();
        $client = $this->makeClient($master);

        Sanctum::actingAs($client);

        $this->postJson('/api/v1/client/chat/messages', [])->assertStatus(422);
    }
}
