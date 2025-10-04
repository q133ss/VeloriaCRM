<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_sms_integration_before_sending_sms(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Test Client',
            'phone' => '+79991234567',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/clients/{$client->id}/reminders", [
            'channel' => 'sms',
            'message' => 'Напоминание',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.message', 'Укажите API для SMS в настройках.');
    }

    public function test_sends_reminder_when_whatsapp_configured(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Test Client',
            'phone' => '+79991234567',
        ]);

        Setting::create([
            'user_id' => $user->id,
            'whatsapp_api_key' => 'test-key',
            'whatsapp_sender' => '+10000000000',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/clients/{$client->id}/reminders", [
            'channel' => 'whatsapp',
            'message' => 'Напоминание',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Напоминание отправлено.');
    }
}

