<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\SupportTicket;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminBackofficeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_overview_api(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/overview');

        $response->assertOk()
            ->assertJsonStructure([
                'summary' => [
                    'total_users',
                    'new_users_7d',
                    'paid_users',
                    'suspended_users',
                    'open_tickets',
                    'revenue_30d',
                ],
            ]);
    }

    public function test_regular_user_cannot_access_admin_api(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/admin/overview')->assertForbidden();
    }

    public function test_admin_can_reply_to_support_ticket(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'admin_role' => User::ADMIN_ROLE_SUPPORT,
            'status' => User::STATUS_ACTIVE,
        ]);

        $user = User::factory()->create();
        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'subject' => 'Billing issue',
            'status' => SupportTicket::STATUS_WAITING,
            'priority' => 'normal',
            'source' => 'in_app',
            'last_message_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/admin/support/tickets/{$ticket->id}/reply", [
            'message' => 'We have checked the payment and will restore access today.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', SupportTicket::STATUS_RESPONDED)
            ->assertJsonPath('data.assignee.id', $admin->id);
    }

    public function test_admin_can_create_update_and_delete_user(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($admin);

        $createResponse = $this->postJson('/api/v1/admin/users', [
            'name' => 'Backoffice Created',
            'email' => 'created@example.test',
            'phone' => '+79990001122',
            'password' => 'password123',
            'status' => User::STATUS_ACTIVE,
            'is_admin' => true,
            'admin_role' => User::ADMIN_ROLE_SUPPORT,
            'admin_notes' => 'Created from test',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.email', 'created@example.test')
            ->assertJsonPath('data.admin_role', User::ADMIN_ROLE_SUPPORT);

        $userId = $createResponse->json('data.id');

        $updateResponse = $this->patchJson("/api/v1/admin/users/{$userId}", [
            'name' => 'Backoffice Updated',
            'email' => 'created@example.test',
            'phone' => '+79990001123',
            'password' => '',
            'status' => User::STATUS_SUSPENDED,
            'is_admin' => false,
            'admin_role' => null,
            'admin_notes' => 'Updated from test',
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.name', 'Backoffice Updated')
            ->assertJsonPath('data.status', User::STATUS_SUSPENDED)
            ->assertJsonPath('data.is_admin', false);

        $this->deleteJson("/api/v1/admin/users/{$userId}")
            ->assertOk();

        $this->assertDatabaseMissing('users', [
            'id' => $userId,
            'email' => 'created@example.test',
        ]);
    }

    public function test_admin_can_change_user_subscription_and_see_audit_log(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'elite', 'price' => 2990]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/admin/users/{$user->id}/subscription", [
            'plan_id' => $plan->id,
            'ends_at' => now()->addMonth()->toIso8601String(),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.current_plan.id', $plan->id);

        $this->assertDatabaseHas('subscription_transactions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'paid',
        ]);

        $auditResponse = $this->getJson('/api/v1/admin/audit');

        $auditResponse->assertOk()
            ->assertJsonFragment([
                'action' => 'admin.subscription_updated',
            ]);
    }
}
