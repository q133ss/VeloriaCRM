<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClientNotification;
use App\Models\LandingRequest;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeYooKassa;

/**
 * After the booking was created: the money arrives, the time runs out, the
 * booking is cancelled and the money goes back.
 */
class PrepaymentSettlementTest extends PrepaymentTestCase
{
    private function heldBooking(string $time = '10:00'): Order
    {
        $this->book(['time' => $time])->assertCreated();

        return Order::query()->latest('id')->firstOrFail();
    }

    private function webhook(string $paymentId, string $event = 'payment.succeeded')
    {
        return $this->postJson('/api/v1/payments/yookassa/webhook', ['event' => $event, 'object' => ['id' => $paymentId]]);
    }

    private function freeSlots(): array
    {
        return collect($this->getJson('/l/studio/availability?service_id=' . $this->service->id)->json('data.days'))
            ->keyBy('date')['2026-10-05']['slots'] ?? [];
    }

    public function test_the_webhook_confirms_a_paid_booking_once(): void
    {
        $order = $this->heldBooking();
        FakeYooKassa::markPaid('pay-' . $order->id);

        $this->webhook('pay-' . $order->id)->assertOk();
        $this->webhook('pay-' . $order->id)->assertOk(); // delivered twice

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->status);
        $this->assertEquals(600, $order->prepaid_amount);
        $this->assertNull($order->prepay_expires_at);
        $this->assertSame('confirmed', Appointment::firstOrFail()->status);
        $this->assertFalse(Appointment::firstOrFail()->meta['awaiting_payment']);
        $this->assertSame('booked', LandingRequest::firstOrFail()->status);
        $this->assertSame(1, ClientNotification::count());
    }

    public function test_the_webhook_is_not_believed_without_asking_yookassa(): void
    {
        $order = $this->heldBooking();

        // ЮKassa still says "pending", whatever the body claims.
        $this->webhook('pay-' . $order->id)->assertOk();

        $this->assertSame('awaiting', $order->fresh()->payment_status);
        $this->assertSame(0, ClientNotification::count());
    }

    public function test_the_webhook_ignores_payments_it_does_not_know(): void
    {
        $this->webhook('not-ours')->assertOk()->assertJsonPath('ignored', true);
        $this->postJson('/api/v1/payments/yookassa/webhook', [])->assertOk();
    }

    public function test_the_webhook_asks_for_a_retry_when_yookassa_cannot_be_reached(): void
    {
        $order = $this->heldBooking();
        FakeYooKassa::$down = true;

        $this->webhook('pay-' . $order->id)->assertStatus(500);
    }

    public function test_the_return_page_learns_the_result_without_the_webhook(): void
    {
        $order = $this->heldBooking();
        $token = Payment::firstOrFail()->return_token;

        $this->get('/l/studio/paid/' . $token)->assertOk()->assertSee($token, false);
        $this->get('/pay/return/' . $token)->assertOk();

        $this->getJson('/api/v1/payments/status/' . $token)
            ->assertOk()->assertJsonPath('data.state', 'awaiting');

        FakeYooKassa::markPaid('pay-' . $order->id);

        $this->getJson('/api/v1/payments/status/' . $token)
            ->assertOk()
            ->assertJsonPath('data.state', 'paid')
            ->assertJsonPath('data.amount', 600)
            ->assertJsonPath('data.time', '10:00')
            ->assertJsonPath('data.service', 'Маникюр');

        $this->getJson('/api/v1/payments/status/nonsense')->assertNotFound();
    }

    public function test_the_minute_sync_picks_up_a_payment_nobody_told_us_about(): void
    {
        $order = $this->heldBooking();
        FakeYooKassa::markPaid('pay-' . $order->id);

        $this->artisan('prepayment:sync')->assertSuccessful();

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_an_unpaid_booking_is_released_when_its_time_runs_out(): void
    {
        $order = $this->heldBooking();
        $this->assertNotContains('10:00', $this->freeSlots());

        Carbon::setTestNow(now()->addMinutes(14));
        $this->artisan('prepayment:sync')->assertSuccessful();
        $this->assertSame('awaiting', $order->fresh()->payment_status);

        Carbon::setTestNow(now()->addMinutes(2));
        $this->artisan('prepayment:sync')->assertSuccessful();

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('expired', $order->payment_status);
        $this->assertSame('cancelled', Appointment::firstOrFail()->status);
        $this->assertSame('expired', LandingRequest::firstOrFail()->status);
        $this->assertSame(1, ClientNotification::count()); // "not confirmed, book again"
        $this->assertContains('10:00', $this->freeSlots());
    }

    public function test_a_payment_made_in_the_last_second_beats_the_timeout(): void
    {
        $order = $this->heldBooking();
        FakeYooKassa::markPaid('pay-' . $order->id);

        Carbon::setTestNow(now()->addMinutes(16));
        $this->artisan('prepayment:sync')->assertSuccessful();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_a_failed_payment_frees_the_time_at_once(): void
    {
        $order = $this->heldBooking();
        FakeYooKassa::$payments['pay-' . $order->id]['status'] = 'canceled';

        $this->webhook('pay-' . $order->id, 'payment.canceled')->assertOk();

        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertContains('10:00', $this->freeSlots());
    }

    public function test_a_late_payment_revives_the_booking_when_the_time_is_still_free(): void
    {
        $order = $this->heldBooking();
        Carbon::setTestNow(now()->addMinutes(16));
        $this->artisan('prepayment:sync')->assertSuccessful();
        $this->assertSame('expired', $order->fresh()->payment_status);

        FakeYooKassa::markPaid('pay-' . $order->id);
        $this->artisan('prepayment:sync')->assertSuccessful();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->status);
        $this->assertNull($order->cancelled_at);
        $this->assertSame('confirmed', Appointment::firstOrFail()->status);
        $this->assertSame([], FakeYooKassa::$refunds);
    }

    public function test_a_late_payment_is_refunded_when_someone_else_took_the_time(): void
    {
        $order = $this->heldBooking();
        Carbon::setTestNow(now()->addMinutes(16));
        $this->artisan('prepayment:sync')->assertSuccessful();

        $this->policy(['enabled' => false]);
        $this->book(['client_phone' => '+7(922)000-11-22', 'client_name' => 'Ольга'])->assertCreated();

        FakeYooKassa::markPaid('pay-' . $order->id);
        $this->artisan('prepayment:sync')->assertSuccessful();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertSame([['payment' => 'pay-' . $order->id, 'amount' => 600.0]], FakeYooKassa::$refunds);
    }

    public function test_the_master_cancelling_a_paid_booking_returns_the_prepayment(): void
    {
        $order = $this->heldBooking();
        FakeYooKassa::markPaid('pay-' . $order->id);
        $this->webhook('pay-' . $order->id);
        Sanctum::actingAs($this->master);

        $this->postJson("/api/v1/orders/{$order->id}/cancel", ['reason' => 'Заболела'])
            ->assertOk()
            ->assertJsonPath('prepayment.refunded', 600)
            ->assertJsonPath('prepayment.kept', 0);

        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertSame('refunded', Payment::firstOrFail()->status);
        $this->assertEquals(600, Payment::firstOrFail()->refunded_amount);
        $this->assertSame('cancelled', Appointment::firstOrFail()->status);
    }

    public function test_the_master_can_keep_the_prepayment(): void
    {
        $order = $this->heldBooking();
        FakeYooKassa::markPaid('pay-' . $order->id);
        $this->webhook('pay-' . $order->id);
        Sanctum::actingAs($this->master);

        $this->postJson("/api/v1/orders/{$order->id}/cancel", ['refund_prepayment' => false])
            ->assertOk()
            ->assertJsonPath('prepayment.refunded', 0)
            ->assertJsonPath('prepayment.kept', 600);

        $this->assertSame([], FakeYooKassa::$refunds);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_late_cancellation_follows_the_masters_refund_rule(): void
    {
        $this->policy([
            'enabled' => true, 'default_mode' => 'percent', 'default_value' => 30,
            'refund' => ['full_before_hours' => 24, 'partial_percent' => 50],
        ]);
        $order = $this->heldBooking(); // the visit is two hours away
        FakeYooKassa::markPaid('pay-' . $order->id);
        $this->webhook('pay-' . $order->id);
        Sanctum::actingAs($this->master);

        $this->postJson("/api/v1/orders/{$order->id}/cancel", [])
            ->assertOk()
            ->assertJsonPath('prepayment.refunded', 300)
            ->assertJsonPath('prepayment.kept', 300);

        $this->assertSame('paid', $order->fresh()->payment_status); // partly kept
        $this->assertEquals(300, $order->fresh()->prepaid_amount);
    }

    public function test_cancelling_still_works_when_the_refund_is_refused(): void
    {
        $order = $this->heldBooking();
        FakeYooKassa::markPaid('pay-' . $order->id);
        $this->webhook('pay-' . $order->id);
        FakeYooKassa::$refundFails = true;
        Sanctum::actingAs($this->master);

        $this->postJson("/api/v1/orders/{$order->id}/cancel", [])
            ->assertOk()
            ->assertJsonPath('prepayment.refunded', 0)
            ->assertJsonStructure(['prepayment' => ['warning']]);

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_payment_for_a_booking_the_master_cancelled_is_refunded(): void
    {
        $order = $this->heldBooking();
        Sanctum::actingAs($this->master);
        $this->postJson("/api/v1/orders/{$order->id}/cancel", [])->assertOk();
        $this->assertSame('failed', $order->fresh()->payment_status);

        FakeYooKassa::markPaid('pay-' . $order->id);
        $this->webhook('pay-' . $order->id)->assertOk();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertCount(1, FakeYooKassa::$refunds);
    }

    public function test_cancelling_an_ordinary_booking_frees_its_time(): void
    {
        $this->policy(['enabled' => false]);
        $this->book()->assertCreated();
        $order = Order::firstOrFail();
        $this->assertNotContains('10:00', $this->freeSlots());

        Sanctum::actingAs($this->master);
        $this->postJson("/api/v1/orders/{$order->id}/cancel", [])->assertOk();

        $this->assertContains('10:00', $this->freeSlots());
    }
    private function paidBooking(): Order
    {
        $order = $this->heldBooking();
        FakeYooKassa::markPaid('pay-' . $order->id);
        $this->webhook('pay-' . $order->id);
        Sanctum::actingAs($this->master);

        return $order->fresh();
    }

    public function test_an_unpaid_hold_offers_nothing_but_cancel(): void
    {
        $order = $this->heldBooking();
        Sanctum::actingAs($this->master);

        $actions = $this->getJson("/api/v1/orders/{$order->id}")->json('data.actions');

        $this->assertTrue($actions['can_cancel']);
        foreach (['can_confirm', 'can_start', 'can_complete', 'can_remind', 'can_reschedule', 'can_mark_no_show'] as $ability) {
            $this->assertFalse($actions[$ability], $ability);
        }

        $this->postJson('/api/v1/orders/bulk', ['orders' => [$order->id], 'action' => 'confirm'])->assertStatus(422);
        $this->assertSame('new', $order->fresh()->status);
    }

    public function test_the_edit_form_cannot_confirm_an_unpaid_hold(): void
    {
        $order = $this->heldBooking();
        Sanctum::actingAs($this->master);

        $this->patchJson("/api/v1/orders/{$order->id}", [
            'client_id' => $order->client_id,
            'scheduled_at' => $order->scheduled_at->copy()->addHours(2)->toIso8601String(),
            'services' => [$this->service->id],
            'status' => 'confirmed',
        ])->assertOk()->assertJsonPath('data.status', 'new');
    }

    public function test_cancelling_through_the_edit_form_returns_the_prepayment_too(): void
    {
        $order = $this->paidBooking();

        $this->patchJson("/api/v1/orders/{$order->id}", [
            'client_id' => $order->client_id,
            'scheduled_at' => $order->scheduled_at->copy()->addHours(2)->toIso8601String(),
            'services' => [$this->service->id],
            'status' => 'cancelled',
        ])->assertOk();

        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertSame('cancelled', Appointment::firstOrFail()->status);
        $this->assertContains('10:00', $this->freeSlots());
    }

    public function test_bulk_cancelling_returns_prepayments_and_frees_the_time(): void
    {
        $order = $this->paidBooking();

        $this->postJson('/api/v1/orders/bulk', ['orders' => [$order->id], 'action' => 'cancel'])->assertOk();

        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertCount(1, FakeYooKassa::$refunds);
        $this->assertContains('10:00', $this->freeSlots());
    }

    public function test_an_order_with_a_prepayment_cannot_be_deleted_until_cancelled(): void
    {
        $order = $this->paidBooking();

        $this->deleteJson("/api/v1/orders/{$order->id}")->assertStatus(422)->assertJsonPath('error.code', 'prepayment_pending');
        $this->assertNotNull(Order::find($order->id));

        $this->postJson("/api/v1/orders/{$order->id}/cancel", [])->assertOk();
        $this->deleteJson("/api/v1/orders/{$order->id}")->assertOk();
    }
}
