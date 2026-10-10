<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeYooKassa;

/**
 * What the client app learns about a prepayment: the amount before she books,
 * the payment step after, and the state of her bookings in the list.
 */
class PrepaymentClientAppTest extends PrepaymentTestCase
{
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Client::create([
            'user_id' => $this->master->id, 'name' => 'Мария', 'phone' => '79115556677', 'email' => 'm@example.com',
        ]);
        Sanctum::actingAs($this->client);
    }

    private function quote(array $query = [])
    {
        return $this->getJson('/api/v1/client/prepayment-quote?' . http_build_query(array_merge([
            'service_id' => $this->service->id, 'date' => '2026-10-05', 'time' => '10:00',
        ], $query)));
    }

    private function bookFromApp()
    {
        return $this->postJson('/api/v1/client/appointments', ['service_id' => $this->service->id, 'date' => '2026-10-05', 'time' => '10:00']);
    }

    private function appointments(): array
    {
        return $this->getJson('/api/v1/client/appointments')->assertOk()->json('data.appointments');
    }

    public function test_the_app_is_told_the_amount_before_she_books(): void
    {
        $this->quote()->assertOk()
            ->assertJsonPath('data.required', true)
            ->assertJsonPath('data.amount', 600)
            ->assertJsonPath('data.hold_minutes', 15);
    }

    public function test_the_quote_is_for_this_very_client(): void
    {
        $this->client->update(['prepay_override' => 'never']);
        $this->quote()->assertJsonPath('data.required', false);

        $this->client->update(['prepay_override' => 'always']);
        $this->policy(['enabled' => true]);
        $this->quote()->assertJsonPath('data.amount', 600); // the 30 % fallback

        $this->quote(['service_id' => null])->assertJsonPath('data.required', false); // no price, nothing to prepay
    }

    public function test_the_quote_is_validated_and_scoped_to_her_master(): void
    {
        $this->quote(['date' => 'soon'])->assertStatus(422);
        $this->quote(['service_id' => 999999])->assertNotFound();
    }

    public function test_an_unpaid_booking_shows_its_payment_in_the_list(): void
    {
        $this->bookFromApp()->assertCreated()->assertJsonPath('data.payment.amount', 600);

        $item = $this->appointments()[0];

        $this->assertTrue($item['is_upcoming']);
        $this->assertSame('awaiting', $item['payment']['state']);
        $this->assertEquals(600, $item['payment']['amount']);
        $this->assertStringStartsWith('https://yookassa.test/pay/', $item['payment']['confirmation_url']);
        $this->assertNotNull($item['payment']['expires_at']);
    }

    public function test_a_paid_booking_shows_it_was_paid(): void
    {
        $this->bookFromApp()->assertCreated();
        $order = Order::firstOrFail();
        FakeYooKassa::markPaid('pay-' . $order->id);
        $this->artisan('prepayment:sync')->assertSuccessful();

        $item = $this->appointments()[0];

        $this->assertSame('confirmed', $item['status']);
        $this->assertSame('paid', $item['payment']['state']);
        $this->assertEquals(600, $item['payment']['amount']);
        $this->assertNull($item['payment']['confirmation_url']);
    }

    public function test_a_booking_whose_time_ran_out_is_no_longer_upcoming_and_has_no_link(): void
    {
        $this->bookFromApp()->assertCreated();

        Carbon::setTestNow(now()->addMinutes(16));
        $this->artisan('prepayment:sync')->assertSuccessful();

        $item = $this->appointments()[0];

        $this->assertSame('cancelled', $item['status']);
        $this->assertFalse($item['is_upcoming']);
        $this->assertNull($item['payment']);
    }

    public function test_a_link_is_not_offered_once_the_hold_is_over(): void
    {
        $this->bookFromApp()->assertCreated();

        // The minute sync has not run yet, but paying now would only be refunded.
        Carbon::setTestNow(now()->addMinutes(16));

        $item = $this->appointments()[0];

        $this->assertSame('awaiting', $item['payment']['state']);
        $this->assertNull($item['payment']['confirmation_url']);
    }

    public function test_an_ordinary_booking_has_no_payment_in_the_list(): void
    {
        $this->policy(['enabled' => false]);
        $this->bookFromApp()->assertCreated()->assertJsonPath('data.payment', null);

        $this->assertNull($this->appointments()[0]['payment']);
        $this->assertSame(0, Payment::count());
    }

    public function test_only_the_address_opened_from_the_app_leads_back_into_it(): void
    {
        $this->bookFromApp()->assertCreated();
        $token = Payment::firstOrFail()->return_token;

        $this->get('/pay/return/' . $token)->assertOk()->assertSee('veloriaclient://payment-return?token=' . $token, false);
        $this->get('/l/studio/paid/' . $token)->assertOk()->assertDontSee('veloriaclient://', false);
    }
}
