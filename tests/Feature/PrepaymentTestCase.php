<?php

namespace Tests\Feature;

use App\Models\Landing;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\Integrations\IntegrationCatalog;
use App\Services\Landing\TemplateRegistry;
use App\Services\YooKassaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakeYooKassa;
use Tests\TestCase;

/**
 * A master (Monday 10:00-12:00 slots) with a verified ЮKassa shop that asks
 * 30 % up front, a landing page, and a fake ЮKassa.
 */
abstract class PrepaymentTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $master;

    protected Service $service;

    protected Setting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA ignore_check_constraints = ON');
        }

        Carbon::setTestNow('2026-10-05 08:00:00');
        FakeYooKassa::reset();

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

        $this->app->bind(YooKassaService::class, fn () => new FakeYooKassa());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function verifyShop(): void
    {
        $this->settings->refresh();
        $this->settings->forceFill(['integration_checks' => ['yookassa' => [
            'ok' => true,
            'fingerprint' => IntegrationCatalog::fingerprint($this->settings, 'yookassa'),
        ]]])->save();
    }

    protected function policy(array $policy): void
    {
        $this->settings->forceFill(['deposit_policy' => $policy])->save();
    }

    protected function book(array $override = [])
    {
        return $this->postJson('/l/studio/book', array_merge([
            'client_name' => 'Мария',
            'client_phone' => '+7(911)555-66-77',
            'service_id' => $this->service->id,
            'date' => '2026-10-05',
            'time' => '10:00',
        ], $override));
    }
}
