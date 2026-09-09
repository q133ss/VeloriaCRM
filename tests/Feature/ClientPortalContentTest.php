<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\MasterPost;
use App\Models\Plan;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientPortalContentTest extends TestCase
{
    use RefreshDatabase;

    private function makeProMaster(array $attributes = []): User
    {
        $master = User::factory()->create($attributes);
        $plan = Plan::firstOrCreate(['name' => 'pro'], ['price' => 999]);
        $master->plans()->attach($plan->id, ['ends_at' => Carbon::now()->addMonth()]);

        return $master;
    }

    public function test_pro_master_can_create_and_manage_posts(): void
    {
        $master = $this->makeProMaster();
        Sanctum::actingAs($master);

        $create = $this->postJson('/api/v1/master-posts', [
            'title' => 'Открылись новые окна',
            'body' => 'Записывайтесь на пятницу.',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.title', 'Открылись новые окна')
            ->assertJsonPath('data.is_published', true);

        $postId = $create->json('data.id');

        $this->assertDatabaseHas('master_posts', [
            'id' => $postId,
            'user_id' => $master->id,
        ]);

        $update = $this->patchJson('/api/v1/master-posts/' . $postId, [
            'title' => 'Обновлённый заголовок',
            'body' => 'Записывайтесь на пятницу.',
            'is_published' => false,
        ]);

        $update->assertOk()->assertJsonPath('data.is_published', false);

        $index = $this->getJson('/api/v1/master-posts');
        $index->assertOk()->assertJsonCount(1, 'data');

        $this->deleteJson('/api/v1/master-posts/' . $postId)->assertOk();
        $this->assertDatabaseMissing('master_posts', ['id' => $postId]);
    }

    public function test_lite_master_cannot_create_posts(): void
    {
        $master = User::factory()->create();
        Sanctum::actingAs($master);

        $this->postJson('/api/v1/master-posts', [
            'title' => 'Новость',
            'body' => 'Текст',
        ])->assertForbidden();
    }

    public function test_master_cannot_manage_another_masters_post(): void
    {
        $master = $this->makeProMaster();
        $otherMaster = $this->makeProMaster();

        $post = MasterPost::create([
            'user_id' => $otherMaster->id,
            'title' => 'Чужая новость',
            'body' => 'Текст',
            'is_published' => true,
            'published_at' => Carbon::now(),
        ]);

        Sanctum::actingAs($master);

        $this->patchJson('/api/v1/master-posts/' . $post->id, [
            'title' => 'Взлом',
            'body' => 'Текст',
        ])->assertNotFound();

        $this->deleteJson('/api/v1/master-posts/' . $post->id)->assertNotFound();
    }

    public function test_client_sees_only_published_posts_of_own_master(): void
    {
        $master = $this->makeProMaster();
        $otherMaster = User::factory()->create();

        MasterPost::create([
            'user_id' => $master->id,
            'title' => 'Опубликовано',
            'body' => 'Видно клиенту',
            'is_published' => true,
            'published_at' => Carbon::now()->subDay(),
        ]);

        MasterPost::create([
            'user_id' => $master->id,
            'title' => 'Черновик',
            'body' => 'Не видно клиенту',
            'is_published' => false,
        ]);

        MasterPost::create([
            'user_id' => $master->id,
            'title' => 'Запланировано на будущее',
            'body' => 'Пока не видно',
            'is_published' => true,
            'published_at' => Carbon::now()->addDay(),
        ]);

        MasterPost::create([
            'user_id' => $otherMaster->id,
            'title' => 'Пост другого мастера',
            'body' => 'Не видно',
            'is_published' => true,
            'published_at' => Carbon::now()->subDay(),
        ]);

        $client = Client::create([
            'user_id' => $master->id,
            'name' => 'Client',
            'email' => 'client@example.com',
            'phone' => '79518677099',
        ]);

        Sanctum::actingAs($client);

        $response = $this->getJson('/api/v1/client/posts');

        $response->assertOk()
            ->assertJsonCount(1, 'data.posts')
            ->assertJsonPath('data.posts.0.title', 'Опубликовано');
    }

    public function test_client_can_read_active_promotions_of_own_master(): void
    {
        $master = User::factory()->create();

        Promotion::create([
            'user_id' => $master->id,
            'name' => 'Скидка на маникюр',
            'type' => 'order_percent',
            'percent' => 10,
            'promo_code' => 'FRIEND10',
        ]);

        Promotion::create([
            'user_id' => $master->id,
            'name' => 'Архивная акция',
            'type' => 'order_percent',
            'percent' => 15,
            'promo_code' => 'OLD15',
            'archived_at' => Carbon::now(),
        ]);

        $client = Client::create([
            'user_id' => $master->id,
            'name' => 'Client',
            'email' => 'promo-client@example.com',
            'phone' => '79518677098',
        ]);

        Sanctum::actingAs($client);

        $response = $this->getJson('/api/v1/client/promotions');

        $response->assertOk()
            ->assertJsonCount(1, 'data.promotions')
            ->assertJsonPath('data.promotions.0.promo_code', 'FRIEND10');
    }
}
