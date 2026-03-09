<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use App\Services\YooKassaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class SubscriptionSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_syncs_successful_payment_and_activates_plan(): void
    {
        Carbon::setTestNow('2026-03-10 12:00:00');

        $user = User::factory()->create();
        $oldPlan = Plan::create(['name' => 'pro', 'price' => 999]);
        $newPlan = Plan::create(['name' => 'elite', 'price' => 2999]);

        $user->plans()->attach($oldPlan->id, [
            'ends_at' => Carbon::now()->addDays(4),
            'created_at' => Carbon::now()->subMonth(),
            'updated_at' => Carbon::now()->subMonth(),
        ]);

        $transaction = SubscriptionTransaction::create([
            'user_id' => $user->id,
            'plan_id' => $newPlan->id,
            'payment_id' => '31414ce8-000f-5001-8000-11a9fb69fccf',
            'amount' => $newPlan->price,
            'currency' => 'RUB',
            'status' => 'pending',
            'metadata' => ['initiated_by' => 'upgrade'],
            'created_at' => Carbon::now()->subMinutes(5),
            'updated_at' => Carbon::now()->subMinutes(5),
        ]);

        $mock = Mockery::mock(YooKassaService::class);
        $mock->shouldReceive('enabled')->andReturn(true);
        $mock->shouldReceive('getPaymentInfo')
            ->once()
            ->with($transaction->payment_id)
            ->andReturn([
                'id' => $transaction->payment_id,
                'status' => 'succeeded',
                'paid' => true,
                'captured_at' => Carbon::now()->subMinute()->toIso8601String(),
                'created_at' => Carbon::now()->subMinutes(5)->toIso8601String(),
            ]);

        $this->app->instance(YooKassaService::class, $mock);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/subscription');

        $response->assertOk()
            ->assertJsonPath('current_plan.slug', 'elite')
            ->assertJsonPath('current_plan.is_active', true);

        $this->assertDatabaseHas('subscription_transactions', [
            'id' => $transaction->id,
            'status' => 'succeeded',
        ]);

        $this->assertDatabaseHas('plan_user', [
            'user_id' => $user->id,
            'plan_id' => $newPlan->id,
        ]);

        $this->assertNotNull($transaction->fresh()->paid_at);

        Carbon::setTestNow();
    }
}
