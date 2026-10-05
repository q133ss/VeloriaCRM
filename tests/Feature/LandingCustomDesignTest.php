<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LandingCustomDesignTest extends TestCase
{
    use RefreshDatabase;

    private function master(?string $plan = null): User
    {
        $user = User::factory()->create();

        if ($plan) {
            $model = Plan::create(['name' => $plan, 'price' => 1000, 'duration_days' => 30]);
            $user->plans()->attach($model->id, ['ends_at' => Carbon::now()->addMonth()]);
        }

        return $user;
    }

    private function payload(): array
    {
        return ['about' => 'Маникюр и педикюр', 'style' => 'Нежно и светло', 'links' => 'instagram.com/master', 'contact' => '+7 900 123-45-67'];
    }

    public function test_request_reaches_the_admin_as_a_support_ticket(): void
    {
        $user = $this->master('pro');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/landings/custom-design', $this->payload())->assertCreated();

        $ticket = SupportTicket::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('custom_design', $ticket->category);
        $this->assertSame('landing_custom_design', $ticket->source);
        $this->assertSame(SupportTicket::STATUS_WAITING, $ticket->status);

        $text = $ticket->messages()->first()->message;
        $this->assertStringContainsString('Маникюр и педикюр', $text);
        $this->assertStringContainsString('+7 900 123-45-67', $text);
        $this->assertStringContainsString('PRO', $text);
        $this->assertStringContainsString('Цена договорная', $text);
    }

    public function test_elite_gets_the_first_template_free_and_only_the_first(): void
    {
        $user = $this->master('elite');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/landings/options')->assertJsonPath('data.custom_design.free_available', true);

        $this->postJson('/api/v1/landings/custom-design', $this->payload())->assertCreated();
        $this->assertStringContainsString('бесплатно', SupportTicket::first()->messages()->first()->message);

        $this->getJson('/api/v1/landings/options')->assertJsonPath('data.custom_design.free_available', false);

        $this->postJson('/api/v1/landings/custom-design', $this->payload())->assertCreated();
        $this->assertStringContainsString('Цена договорная', SupportTicket::latest('id')->first()->messages()->first()->message);
    }

    public function test_free_and_pro_never_get_it_for_free(): void
    {
        foreach ([null, 'pro'] as $plan) {
            Sanctum::actingAs($this->master($plan));
            $this->getJson('/api/v1/landings/options')->assertJsonPath('data.custom_design.free_available', false);
        }
    }

    public function test_it_needs_a_login_and_the_two_required_fields(): void
    {
        $this->postJson('/api/v1/landings/custom-design', $this->payload())->assertUnauthorized();

        Sanctum::actingAs($this->master());
        $this->postJson('/api/v1/landings/custom-design', ['about' => '', 'contact' => ''])->assertStatus(422);
        $this->assertSame(0, SupportTicket::count());
    }
}
