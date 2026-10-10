<?php

namespace Tests\Feature;

use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The cabinet pages are a shell filled from the API, so a guest who could open
 * them saw the whole interface. They get a 403; /help alone answers a guest,
 * with a contact form instead of the master's ticket centre.
 */
class GuestCabinetAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'ru');
    }

    public function test_guests_get_403_on_every_cabinet_page(): void
    {
        foreach ([
            '/dashboard', '/calendar', '/clients', '/clients/create', '/orders', '/orders/create',
            '/services', '/analytics', '/useful', '/settings', '/profile', '/integrations',
            '/messages', '/notifications', '/landings', '/marketing', '/subscription', '/admin/overview',
        ] as $path) {
            $this->get($path)->assertForbidden();
        }
    }

    public function test_a_master_still_opens_the_cabinet(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/calendar')->assertOk();
        $this->actingAs($user)->get('/help')->assertOk()->assertSee('/api/v1/help/tickets', false);
    }

    public function test_help_shows_a_guest_the_contact_form_not_the_ticket_centre(): void
    {
        $this->get('/help')
            ->assertOk()
            ->assertSee('/api/v1/support-requests', false)
            ->assertDontSee('/api/v1/help/tickets', false);
    }

    public function test_a_guest_can_leave_a_contact(): void
    {
        $this->postJson('/api/v1/support-requests', [
            'name' => 'Анна',
            'contact_type' => 'telegram',
            'contact' => 'anna_master',
            'message' => 'Хочу попробовать',
        ])->assertCreated();

        $this->assertDatabaseHas('support_requests', [
            'contact_type' => 'telegram',
            'contact' => '@anna_master',
        ]);
    }

    public function test_a_bad_contact_is_refused(): void
    {
        $this->postJson('/api/v1/support-requests', ['contact_type' => 'email', 'contact' => 'nope'])
            ->assertStatus(422);
        $this->postJson('/api/v1/support-requests', ['contact_type' => 'phone', 'contact' => '123'])
            ->assertStatus(422);
        $this->postJson('/api/v1/support-requests', ['contact_type' => 'phone', 'contact' => '+7 900 000-00-00', 'website' => 'spam'])
            ->assertStatus(422);

        $this->assertSame(0, SupportRequest::count());
    }
}
