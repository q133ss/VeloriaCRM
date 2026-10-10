<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Payment;
use App\Models\PrepaymentRule;

/**
 * What the public booking page learns about a prepayment before and after the booking.
 */
class PrepaymentLandingTest extends PrepaymentTestCase
{
    private function quote(array $query = [])
    {
        return $this->getJson('/l/studio/prepayment?' . http_build_query(array_merge([
            'service_id' => $this->service->id,
            'date' => '2026-10-05',
            'time' => '10:00',
        ], $query)));
    }

    public function test_the_quote_says_what_a_booking_at_that_time_would_ask(): void
    {
        $this->quote()->assertOk()
            ->assertJsonPath('data.required', true)
            ->assertJsonPath('data.amount', 600)
            ->assertJsonPath('data.hold_minutes', 15);
    }

    public function test_the_quote_follows_the_date_rules(): void
    {
        $this->policy(['enabled' => true]);
        PrepaymentRule::create([
            'user_id' => $this->master->id, 'name' => 'Праздники', 'type' => 'period',
            'starts_on' => '2026-10-06', 'ends_on' => '2026-10-08', 'mode' => 'fixed', 'value' => 900,
        ]);

        $this->quote(['date' => '2026-10-05'])->assertJsonPath('data.required', false);
        $this->quote(['date' => '2026-10-06'])->assertJsonPath('data.amount', 900);
    }

    public function test_the_quote_is_empty_when_nothing_is_asked(): void
    {
        $this->policy(['enabled' => false]);
        $this->quote()->assertOk()->assertExactJson(['data' => ['required' => false]]);

        $this->policy(['enabled' => true, 'default_mode' => 'percent', 'default_value' => 30]);
        $this->settings->forceFill(['integration_checks' => null])->save();
        $this->quote()->assertJsonPath('data.required', false);
    }

    public function test_the_quote_gives_nothing_away_about_a_particular_client(): void
    {
        Client::create(['user_id' => $this->master->id, 'name' => 'Мария', 'phone' => '+7(911)555-66-77', 'prepay_override' => 'never']);

        // The quote cannot know who is asking, so a "never" card changes nothing here;
        // the booking itself answers for the real person.
        $this->quote()->assertJsonPath('data.required', true);
        $this->book()->assertCreated()->assertJsonPath('data.kind', 'booked');
    }

    public function test_the_quote_is_validated(): void
    {
        $this->quote(['date' => 'tomorrow'])->assertStatus(422);
        $this->quote(['time' => '25:00'])->assertStatus(422);
        $this->quote(['service_id' => 999999])->assertStatus(422);
        $this->getJson('/l/nonexistent/prepayment?date=2026-10-05&time=10:00')->assertNotFound();
    }

    public function test_the_booking_answers_with_everything_the_payment_step_needs(): void
    {
        $response = $this->book()->assertCreated()
            ->assertJsonPath('data.kind', 'payment_required')
            ->assertJsonPath('data.payment.amount', 600)
            ->assertJsonStructure(['message', 'data' => ['payment' => ['amount', 'confirmation_url', 'expires_at']]]);

        // The return address sent to ЮKassa leads back to this landing and carries the payment's token.
        $payment = Payment::firstOrFail();
        $this->assertStringContainsString(urlencode('/l/studio/paid/' . $payment->return_token), $response->json('data.payment.confirmation_url'));
    }

    public function test_the_return_page_leads_back_to_the_masters_page(): void
    {
        $this->book()->assertCreated();
        $token = Payment::firstOrFail()->return_token;

        $this->get('/l/studio/paid/' . $token)->assertOk()->assertSee('href="' . url('/l/studio') . '"', false);
        $this->get('/pay/return/' . $token)->assertOk()->assertDontSee('/l/studio');
    }

    public function test_the_booking_script_knows_where_to_ask(): void
    {
        $this->get('/l/studio')->assertOk()->assertSee(addcslashes(url('/l/studio/prepayment'), '/'), false); // as it sits in the page's JSON
    }
}
