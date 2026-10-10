<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\YooKassaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrepaymentFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_shop_never_falls_back_to_platform_keys(): void
    {
        config(['services.yookassa.shop_id' => '111111', 'services.yookassa.secret_key' => 'platform-secret']);

        $this->assertTrue((new YooKassaService())->enabled());
        $this->assertFalse(YooKassaService::forMaster(null)->enabled());
        $this->assertFalse(YooKassaService::forMaster(new Setting(['yookassa_shop_id' => '222222']))->enabled());
        $this->assertTrue(YooKassaService::forMaster(new Setting([
            'yookassa_shop_id' => '222222',
            'yookassa_secret_key' => 'secret',
        ]))->enabled());
    }

    public function test_yookassa_secret_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create();
        $secret = str_repeat('s', 80);

        Setting::create(['user_id' => $user->id, 'yookassa_shop_id' => '222222', 'yookassa_secret_key' => $secret]);

        $this->assertSame($secret, Setting::where('user_id', $user->id)->first()->yookassa_secret_key);
        $this->assertNotSame($secret, DB::table('settings')->where('user_id', $user->id)->value('yookassa_secret_key'));
    }

    public function test_payment_accepts_booking_statuses_and_unique_provider_id(): void
    {
        $user = User::factory()->create();

        Payment::create([
            'user_id' => $user->id,
            'provider_payment_id' => 'pay-1',
            'amount' => 500,
            'status' => Payment::STATUS_WAITING_FOR_CAPTURE,
        ]);

        $this->assertDatabaseHas('payments', ['provider_payment_id' => 'pay-1', 'status' => 'waiting_for_capture']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Payment::create(['user_id' => $user->id, 'provider_payment_id' => 'pay-1', 'amount' => 1, 'status' => 'pending']);
    }
}
