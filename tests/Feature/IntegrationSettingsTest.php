<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
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
            ->assertJsonPath('settings.integrations.smsaero.fields.email.value', 'owner@example.com')
            ->assertJsonPath('settings.integrations.telegram.fields.bot_token.filled', true);

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
            ->assertJsonPath('integrations.smtp.fields.host.value', 'smtp.example.com')
            ->assertJsonPath('integrations.yookassa.fields.shop_id.value', 'shop-id');

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

    public function test_pro_user_can_save_allergy_reminder_settings(): void
    {
        $plan = Plan::query()->create([
            'name' => 'pro',
            'price' => 999,
        ]);

        $user = User::factory()->create([
            'timezone' => 'Europe/Moscow',
            'time_format' => '24h',
        ]);
        $user->plans()->attach($plan->id, ['ends_at' => Carbon::now()->addMonth()]);

        Setting::query()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/settings', [
            'name' => 'Pro Master',
            'email' => 'pro@example.com',
            'timezone' => 'Europe/Moscow',
            'time_format' => '24h',
            'notifications' => [
                'email' => true,
                'telegram' => false,
                'sms' => false,
            ],
            'holidays' => [],
            'allergy_reminder_enabled' => true,
            'allergy_reminder_minutes' => 25,
            'allergy_reminder_exclusions' => [
                'allergies' => ['Латекс', 'Отдушка'],
                'services' => [],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('settings.features.allergy_reminders.available', true)
            ->assertJsonPath('settings.features.allergy_reminders.enabled', true)
            ->assertJsonPath('settings.features.allergy_reminders.minutes', 25)
            ->assertJsonPath('settings.features.allergy_reminders.exclusions.allergies.0', 'Латекс');

        $this->assertDatabaseHas('settings', [
            'user_id' => $user->id,
            'allergy_reminder_enabled' => true,
            'allergy_reminder_minutes' => 25,
        ]);

        $this->assertSame(
            ['allergies' => ['Латекс', 'Отдушка'], 'services' => []],
            $user->fresh()->setting->allergy_reminder_exclusions
        );
    }

    /**
     * The page promised that secret keys are not shown to anyone and then
     * refilled them into visible text inputs on every load.
     */
    public function test_a_secret_never_comes_back_to_the_browser(): void
    {
        $user = User::factory()->create();
        Setting::create([
            'user_id' => $user->id,
            'telegram_bot_token' => '1234567890:AAHsecrettoken',
            'telegram_sender' => 'veloria_bot',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/settings/integrations')
            ->assertOk()
            ->assertJsonPath('integrations.telegram.fields.bot_token.value', null)
            ->assertJsonPath('integrations.telegram.fields.bot_token.filled', true)
            ->assertJsonPath('integrations.telegram.fields.bot_token.preview', '••••oken')
            ->assertJsonPath('integrations.telegram.fields.sender.value', 'veloria_bot')
            ->assertJsonMissing(['1234567890:AAHsecrettoken']);
    }

    /**
     * Because the secret is not on the page, the page cannot send it back —
     * leaving it out has to mean «keep it».
     */
    public function test_a_stored_secret_survives_a_save_that_leaves_it_out(): void
    {
        $user = User::factory()->create();
        Setting::create([
            'user_id' => $user->id,
            'telegram_bot_token' => 'kept-token',
            'telegram_sender' => 'old_bot',
        ]);

        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/settings/integrations', [
            'integrations' => ['telegram' => ['sender' => 'new_bot']],
        ])->assertOk();

        $setting = $user->fresh()->setting;
        $this->assertSame('kept-token', $setting->telegram_bot_token);
        $this->assertSame('new_bot', $setting->telegram_sender);
    }

    /**
     * «Подключено» used to mean «the field is not empty»: the word «ляляля» in
     * the token field produced a green badge.
     */
    public function test_filled_fields_alone_are_not_called_connected(): void
    {
        $user = User::factory()->create();
        Setting::create(['user_id' => $user->id, 'telegram_bot_token' => 'ляляля']);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/settings/integrations')
            ->assertOk()
            ->assertJsonPath('integrations.telegram.status.state', 'filled');
    }

    public function test_the_status_reports_what_is_missing(): void
    {
        $user = User::factory()->create();
        Setting::create(['user_id' => $user->id, 'smtp_host' => 'smtp.example.com']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/settings/integrations')->assertOk();

        $this->assertSame('partial', $response->json('integrations.smtp.status.state'));
        $this->assertSame(
            ['port', 'username', 'password', 'from_address'],
            $response->json('integrations.smtp.status.missing')
        );
    }

    public function test_a_channel_is_verified_only_after_the_service_answered(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['username' => 'veloria_bot']]),
        ]);

        $user = User::factory()->create();
        Setting::create(['user_id' => $user->id, 'telegram_bot_token' => 'real-token']);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/settings/integrations/telegram/check')
            ->assertOk()
            ->assertJsonPath('result.ok', true)
            ->assertJsonPath('integrations.telegram.status.state', 'verified');

        // A new token is a new question: the old answer stops counting.
        $this->patchJson('/api/v1/settings/integrations', [
            'integrations' => ['telegram' => ['bot_token' => 'another-token']],
        ])->assertOk()->assertJsonPath('integrations.telegram.status.state', 'filled');
    }

    public function test_a_refused_token_is_reported_as_not_working(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401),
        ]);

        $user = User::factory()->create();
        Setting::create(['user_id' => $user->id, 'telegram_bot_token' => 'ляляля']);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/settings/integrations/telegram/check')
            ->assertOk()
            ->assertJsonPath('result.ok', false)
            ->assertJsonPath('integrations.telegram.status.state', 'failed');
    }

    public function test_a_channel_can_be_turned_off_in_one_action(): void
    {
        $user = User::factory()->create();
        Setting::create([
            'user_id' => $user->id,
            'telegram_bot_token' => 'token',
            'telegram_sender' => 'bot',
            'smtp_host' => 'smtp.example.com',
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/settings/integrations/telegram')
            ->assertOk()
            ->assertJsonPath('integrations.telegram.status.state', 'empty');

        $setting = $user->fresh()->setting;
        $this->assertNull($setting->telegram_bot_token);
        $this->assertNull($setting->telegram_sender);
        $this->assertSame('smtp.example.com', $setting->smtp_host);
    }

    public function test_an_unknown_provider_is_not_a_channel(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/settings/integrations/whatever/check')->assertNotFound();
        $this->deleteJson('/api/v1/settings/integrations/whatever')->assertNotFound();
    }
}
