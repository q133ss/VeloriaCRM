<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PrepaymentRule;
use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeYooKassa;

/**
 * The master's side: the general rule, date rules, per-service and per-client
 * settings, and what a booking says about its prepayment.
 */
class PrepaymentSettingsTest extends PrepaymentTestCase
{
    private array $policy = [
        'enabled' => true,
        'default_mode' => 'percent',
        'default_value' => 40,
        'new_clients_only' => true,
        'hold_minutes' => 20,
        'no_show_threshold' => 2,
        'refund' => ['full_before_hours' => 24, 'partial_percent' => 50],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs($this->master);
    }

    public function test_it_reports_the_shop_state_and_defaults(): void
    {
        $this->settings->forceFill(['deposit_policy' => null])->save();

        $this->getJson('/api/v1/settings/prepayment')
            ->assertOk()
            ->assertJsonPath('data.shop', 'verified')
            ->assertJsonPath('data.policy.enabled', false)
            ->assertJsonPath('data.policy.hold_minutes', 15)
            ->assertJsonPath('data.webhook_url', url('/api/v1/payments/yookassa/webhook'))
            ->assertJsonPath('data.services.0.name', 'Маникюр');

        $this->settings->forceFill(['integration_checks' => null])->save();
        $this->getJson('/api/v1/settings/prepayment')->assertJsonPath('data.shop', 'unchecked');

        $this->settings->forceFill(['yookassa_shop_id' => null, 'yookassa_secret_key' => null])->save();
        $this->getJson('/api/v1/settings/prepayment')->assertJsonPath('data.shop', 'missing');
    }

    public function test_the_general_rule_is_saved_and_then_drives_bookings(): void
    {
        $this->putJson('/api/v1/settings/prepayment', $this->policy)->assertOk();

        $this->assertEquals(40, $this->settings->fresh()->deposit_policy['default_value']);
        $this->assertSame(50, $this->settings->fresh()->deposit_policy['refund']['partial_percent']);

        $this->book()->assertCreated()->assertJsonPath('data.payment.amount', 800);
        $this->assertSame(20, (int) now()->diffInMinutes(Order::firstOrFail()->prepay_expires_at, false));
    }

    public function test_it_cannot_be_switched_on_before_the_shop_is_verified(): void
    {
        $this->settings->forceFill(['integration_checks' => null])->save();

        $this->putJson('/api/v1/settings/prepayment', $this->policy)
            ->assertStatus(422)->assertJsonValidationErrors('enabled');

        $this->putJson('/api/v1/settings/prepayment', ['enabled' => false] + $this->policy)->assertOk();
    }

    public function test_the_general_rule_is_validated(): void
    {
        $this->putJson('/api/v1/settings/prepayment', ['default_value' => 150] + $this->policy)
            ->assertStatus(422)->assertJsonValidationErrors('default_value');
        $this->putJson('/api/v1/settings/prepayment', ['default_mode' => 'fixed', 'default_value' => 150] + $this->policy)->assertOk();
        $this->putJson('/api/v1/settings/prepayment', ['hold_minutes' => 1] + $this->policy)
            ->assertStatus(422)->assertJsonValidationErrors('hold_minutes');
        $this->putJson('/api/v1/settings/prepayment', ['refund' => ['partial_percent' => 101]] + $this->policy)
            ->assertStatus(422)->assertJsonValidationErrors('refund.partial_percent');
    }

    public function test_date_rules_can_be_created_changed_and_removed(): void
    {
        $id = $this->postJson('/api/v1/prepayment-rules', [
            'name' => 'Новый год', 'type' => 'period', 'starts_on' => '2026-12-20', 'ends_on' => '2027-01-10',
            'mode' => 'percent', 'value' => 50, 'service_ids' => [$this->service->id],
        ])->assertCreated()->assertJsonPath('data.service_ids.0', $this->service->id)->json('data.id');

        $this->postJson('/api/v1/prepayment-rules', [
            'name' => 'Выходные', 'type' => 'weekday', 'weekdays' => [6, 0, 6], 'mode' => 'fixed', 'value' => 500,
        ])->assertCreated()->assertJsonPath('data.weekdays', [6, 0])->assertJsonPath('data.service_ids', []);

        $this->putJson("/api/v1/prepayment-rules/{$id}", [
            'name' => 'Новый год', 'type' => 'period', 'starts_on' => '2026-12-20', 'ends_on' => '2027-01-12',
            'mode' => 'percent', 'value' => 60, 'is_active' => false,
        ])->assertOk()->assertJsonPath('data.is_active', false)->assertJsonPath('data.service_ids', []);

        $this->getJson('/api/v1/settings/prepayment')->assertJsonCount(2, 'data.rules');

        $this->deleteJson("/api/v1/prepayment-rules/{$id}")->assertOk();
        $this->assertSame(1, PrepaymentRule::count());
    }

    public function test_date_rules_are_validated(): void
    {
        $base = ['name' => 'X', 'type' => 'period', 'starts_on' => '2026-12-20', 'ends_on' => '2027-01-10', 'mode' => 'percent', 'value' => 50];

        $this->postJson('/api/v1/prepayment-rules', ['ends_on' => '2026-12-01'] + $base)->assertStatus(422)->assertJsonValidationErrors('ends_on');
        $this->postJson('/api/v1/prepayment-rules', ['value' => 120] + $base)->assertStatus(422)->assertJsonValidationErrors('value');
        $this->postJson('/api/v1/prepayment-rules', ['type' => 'weekday', 'weekdays' => null] + $base)->assertStatus(422)->assertJsonValidationErrors('weekdays');
        $this->postJson('/api/v1/prepayment-rules', ['type' => 'weekday', 'weekdays' => [7]] + $base)->assertStatus(422);
        $this->postJson('/api/v1/prepayment-rules', ['name' => ''] + $base)->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_a_master_cannot_touch_anothers_rules_or_services(): void
    {
        $other = User::factory()->create();
        $theirs = PrepaymentRule::create([
            'user_id' => $other->id, 'name' => 'Чужое', 'type' => 'weekday', 'weekdays' => [1], 'mode' => 'fixed', 'value' => 100,
        ]);
        $theirService = Service::create(['user_id' => $other->id, 'name' => 'Чужая', 'base_price' => 100, 'duration_min' => 30]);

        $this->putJson("/api/v1/prepayment-rules/{$theirs->id}", ['name' => 'X', 'type' => 'weekday', 'weekdays' => [1], 'mode' => 'fixed', 'value' => 1])->assertNotFound();
        $this->deleteJson("/api/v1/prepayment-rules/{$theirs->id}")->assertNotFound();
        $this->assertNotContains('Чужое', array_column($this->getJson('/api/v1/settings/prepayment')->json('data.rules'), 'name'));

        $this->postJson('/api/v1/prepayment-rules', [
            'name' => 'X', 'type' => 'weekday', 'weekdays' => [1], 'mode' => 'fixed', 'value' => 100, 'service_ids' => [$theirService->id],
        ])->assertStatus(422)->assertJsonValidationErrors('service_ids.0');
    }

    public function test_a_service_carries_its_own_prepayment(): void
    {
        $body = ['name' => 'Педикюр', 'base_price' => 3000, 'duration_min' => 60];

        $id = $this->postJson('/api/v1/services', $body + ['prepay_mode' => 'fixed', 'prepay_value' => 700])
            ->assertCreated()->assertJsonPath('data.prepay_mode', 'fixed')->assertJsonPath('data.prepay_value', 700)->json('data.id');

        // A form that does not know about prepayments must not wipe them.
        $this->patchJson("/api/v1/services/{$id}", $body + ['base_price' => 3200])
            ->assertOk()->assertJsonPath('data.prepay_mode', 'fixed')->assertJsonPath('data.prepay_value', 700);

        // "Do not ask" has no amount.
        $this->patchJson("/api/v1/services/{$id}", $body + ['prepay_mode' => 'none', 'prepay_value' => 700])
            ->assertOk()->assertJsonPath('data.prepay_mode', 'none')->assertJsonPath('data.prepay_value', null);

        $this->patchJson("/api/v1/services/{$id}", $body + ['prepay_mode' => null, 'prepay_value' => null])
            ->assertOk()->assertJsonPath('data.prepay_mode', null);
    }

    public function test_a_service_prepayment_is_validated(): void
    {
        $body = ['name' => 'Педикюр', 'base_price' => 3000, 'duration_min' => 60];

        $this->postJson('/api/v1/services', $body + ['prepay_mode' => 'percent', 'prepay_value' => 150])
            ->assertStatus(422);
        $this->postJson('/api/v1/services', $body + ['prepay_mode' => 'fixed'])
            ->assertStatus(422);
        $this->postJson('/api/v1/services', $body + ['prepay_mode' => 'sometimes'])
            ->assertStatus(422);
    }

    public function test_a_client_can_be_set_to_always_or_never_prepay(): void
    {
        $client = Client::create(['user_id' => $this->master->id, 'name' => 'Мария', 'phone' => '+79115556677']);

        $this->getJson("/api/v1/clients/{$client->id}")->assertJsonPath('data.prepay_override', 'inherit');

        $this->patchJson("/api/v1/clients/{$client->id}", ['name' => 'Мария', 'phone' => '+79115556677', 'prepay_override' => 'always'])
            ->assertOk()->assertJsonPath('data.prepay_override', 'always');

        // Saving the card from a form that leaves it out keeps it.
        $this->patchJson("/api/v1/clients/{$client->id}", ['name' => 'Мария К.', 'phone' => '+79115556677'])
            ->assertOk()->assertJsonPath('data.prepay_override', 'always');

        $this->patchJson("/api/v1/clients/{$client->id}", ['name' => 'Мария', 'phone' => '+79115556677', 'prepay_override' => 'sometimes'])
            ->assertStatus(422);

        $this->patchJson("/api/v1/clients/{$client->id}", ['name' => 'Мария', 'phone' => '+79115556677', 'prepay_override' => null])
            ->assertOk()->assertJsonPath('data.prepay_override', 'inherit');
    }

    public function test_orders_say_what_state_their_prepayment_is_in(): void
    {
        $this->book()->assertCreated();
        $order = Order::firstOrFail();

        $this->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.prepayment.state', 'awaiting')
            ->assertJsonPath('data.prepayment_refund', null);

        FakeYooKassa::markPaid('pay-' . $order->id);
        $this->postJson('/api/v1/payments/yookassa/webhook', ['event' => 'payment.succeeded', 'object' => ['id' => 'pay-' . $order->id]])->assertOk();

        $this->getJson("/api/v1/orders/{$order->id}")
            ->assertJsonPath('data.prepayment.state', 'paid')
            ->assertJsonPath('data.prepayment.amount', 600)
            ->assertJsonPath('data.prepayment_refund.refundable', 600)
            ->assertJsonPath('data.prepayment_refund.suggested', 600);

        $this->assertSame(__('prepayment.badge.paid', ['amount' => '600']), Order::firstOrFail()->prepayment['label']);
        $this->assertSame(1, Payment::count());
    }

    public function test_an_ordinary_booking_has_no_prepayment_badge(): void
    {
        $this->policy(['enabled' => false]);
        $this->book()->assertCreated();

        $this->getJson('/api/v1/orders/' . Order::firstOrFail()->id)->assertJsonPath('data.prepayment', null);
    }
}
