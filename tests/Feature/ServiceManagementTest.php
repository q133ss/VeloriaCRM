<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_can_list_services_grouped_by_category(): void
    {
        $user = $this->authenticate();

        $category = ServiceCategory::create([
            'user_id' => $user->id,
            'name' => 'Hair',
        ]);

        $service = Service::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Haircut',
            'base_price' => 1500,
            'cost' => 500,
            'duration_min' => 60,
        ]);

        $response = $this->getJson('/api/v1/services');

        $response->assertOk()
            ->assertJsonPath('data.groups.0.name', $category->name)
            ->assertJsonPath('data.groups.0.services.0.name', $service->name);
    }

    public function test_can_create_update_and_delete_service(): void
    {
        $user = $this->authenticate();

        $category = ServiceCategory::create([
            'user_id' => $user->id,
            'name' => 'Brows',
        ]);

        $createPayload = [
            'name' => 'Brow shaping',
            'category_id' => $category->id,
            'base_price' => 1200,
            'cost' => 300,
            'duration_min' => 45,
            'upsell_suggestions' => ['Tint'],
        ];

        $createResponse = $this->postJson('/api/v1/services', $createPayload);

        $createResponse->assertCreated()
            ->assertJsonPath('data.name', 'Brow shaping');

        $serviceId = $createResponse->json('data.id');

        $this->assertDatabaseHas('services', [
            'id' => $serviceId,
            'user_id' => $user->id,
            'name' => 'Brow shaping',
        ]);

        $updatePayload = [
            'name' => 'Brow styling',
            'category_id' => null,
            'base_price' => 1400,
            'cost' => 400,
            'duration_min' => 50,
            'upsell_suggestions' => ['Tint', 'Lamination'],
        ];

        $updateResponse = $this->patchJson('/api/v1/services/' . $serviceId, $updatePayload);

        $updateResponse->assertOk()
            ->assertJsonPath('data.name', 'Brow styling')
            ->assertJsonPath('data.category_id', null);

        $this->assertDatabaseHas('services', [
            'id' => $serviceId,
            'name' => 'Brow styling',
            'category_id' => null,
        ]);

        $deleteResponse = $this->deleteJson('/api/v1/services/' . $serviceId);

        $deleteResponse->assertOk()
            ->assertJsonPath('message', __('services.messages.deleted'));

        $this->assertDatabaseMissing('services', ['id' => $serviceId]);
    }

    public function test_can_manage_categories(): void
    {
        $this->authenticate();

        $createResponse = $this->postJson('/api/v1/service-categories', ['name' => 'Hair']);

        $createResponse->assertCreated()
            ->assertJsonPath('data.name', 'Hair');

        $categoryId = $createResponse->json('data.id');

        $updateResponse = $this->patchJson('/api/v1/service-categories/' . $categoryId, ['name' => 'Hair care']);

        $updateResponse->assertOk()
            ->assertJsonPath('data.name', 'Hair care');

        $indexResponse = $this->getJson('/api/v1/service-categories');

        $indexResponse->assertOk()
            ->assertJsonPath('data.categories.0.name', 'Hair care');

        $deleteResponse = $this->deleteJson('/api/v1/service-categories/' . $categoryId);

        $deleteResponse->assertOk()
            ->assertJsonPath('message', __('services.messages.category_deleted'));

        $this->assertDatabaseMissing('service_categories', ['id' => $categoryId]);
    }

    public function test_cannot_manage_services_of_other_user(): void
    {
        $user = $this->authenticate();
        $otherUser = User::factory()->create();

        $service = Service::create([
            'user_id' => $otherUser->id,
            'name' => 'Massage',
            'base_price' => 2000,
            'cost' => 800,
            'duration_min' => 90,
        ]);

        $this->getJson('/api/v1/services/' . $service->id)->assertStatus(403);

        $this->patchJson('/api/v1/services/' . $service->id, [
            'name' => 'Relax massage',
            'base_price' => 2100,
            'duration_min' => 90,
        ])->assertStatus(403);

        $this->deleteJson('/api/v1/services/' . $service->id)->assertStatus(403);
    }
}
