<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IntegrationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_settings_update_does_not_clear_existing_integrations(): void
    {
        $user = User::factory()->create([
            'timezone' => 'Europe/Moscow',
            'time_format' => '24h',
        ]);

        Setting::create([
            'user_id' => $user->id,
            'smsaero_email' => 'owner@example.com',
            'smsaero_api_key' => 'sms-key',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'telegram_bot_token' => 'tg-token',
            'yookassa_shop_id' => 'shop-1',
            'notification_prefs' => ['email' => false],
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/settings', [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'timezone' => 'Europe/Moscow',
            'time_format' => '24h',
            'notifications' => [
                'email' => true,
                'telegram' => false,
                'sms' => false,
            ],
            'work_days' => ['mon'],
            'work_hours' => ['mon' => ['10:00']],
            'holidays' => [],
            'address' => 'Main street 1',
            'map_point' => ['lat' => 55.7558, 'lng' => 37.6173],
            'reminder_message' => 'Reminder text',
        ]);

        $response->assertOk()
            ->assertJsonPath('settings.integrations.smsaero.email', 'owner@example.com')
            ->assertJsonPath('settings.integrations.telegram.bot_token', 'tg-token');

        $this->assertDatabaseHas('settings', [
            'user_id' => $user->id,
            'smsaero_email' => 'owner@example.com',
            'smsaero_api_key' => 'sms-key',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'telegram_bot_token' => 'tg-token',
            'yookassa_shop_id' => 'shop-1',
            'address' => 'Main street 1',
        ]);
    }

    public function test_integrations_endpoint_updates_only_integration_fields(): void
    {
        $user = User::factory()->create();

        Setting::create([
            'user_id' => $user->id,
            'address' => 'Preserved address',
            'reminder_message' => 'Preserved reminder',
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/settings/integrations', [
            'integrations' => [
                'smsaero' => [
                    'email' => 'sms@example.com',
                    'api_key' => 'sms-api',
                ],
                'smtp' => [
                    'host' => 'smtp.example.com',
                    'port' => 465,
                    'username' => 'mailer',
                    'password' => 'secret',
                    'encryption' => 'ssl',
                    'from_address' => 'hello@example.com',
                    'from_name' => 'Veloria',
                ],
                'whatsapp' => [
                    'api_key' => 'wa-key',
                    'sender' => '123456789',
                ],
                'telegram' => [
                    'bot_token' => 'bot-token',
                    'sender' => 'veloria_bot',
                ],
                'yookassa' => [
                    'shop_id' => 'shop-id',
                    'secret_key' => 'secret-key',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('integrations.smtp.host', 'smtp.example.com')
            ->assertJsonPath('integrations.yookassa.shop_id', 'shop-id');

        $this->assertDatabaseHas('settings', [
            'user_id' => $user->id,
            'smsaero_email' => 'sms@example.com',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 465,
            'whatsapp_sender' => '123456789',
            'telegram_bot_token' => 'bot-token',
            'yookassa_secret_key' => 'secret-key',
            'address' => 'Preserved address',
            'reminder_message' => 'Preserved reminder',
        ]);
    }

    public function test_settings_update_persists_schedule_rules_and_legacy_weekly_fields(): void
    {
        $user = User::factory()->create([
            'timezone' => 'Europe/Moscow',
            'time_format' => '24h',
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/settings', [
            'name' => 'Planner',
            'email' => 'planner@example.com',
            'timezone' => 'Europe/Moscow',
            'time_format' => '24h',
            'notifications' => [
                'email' => true,
                'telegram' => false,
                'sms' => false,
            ],
            'schedule_rules' => [
                'mode' => 'weekly',
                'weekly' => [
                    'mon' => ['enabled' => true, 'slots' => ['09:00', '15:30']],
                    'tue' => ['enabled' => false, 'slots' => []],
                    'wed' => ['enabled' => true, 'slots' => ['10:00']],
                ],
            ],
            'holidays' => [],
        ]);

        $response->assertOk()
            ->assertJsonPath('settings.schedule_rules.mode', 'weekly')
            ->assertJsonPath('settings.schedule_rules.weekly.mon.slots.1', '15:30');

        $setting = $user->fresh()->setting;

        $this->assertSame(['mon', 'wed'], $setting->work_days);
        $this->assertSame(['09:00', '15:30'], $setting->work_hours['mon']);
        $this->assertSame('weekly', $setting->schedule_rules['mode']);
    }
}
