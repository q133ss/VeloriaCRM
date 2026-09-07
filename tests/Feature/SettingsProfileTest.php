<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * What the settings form does to the account it saves.
 */
class SettingsProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'ru');
    }

    /**
     * The phone used to be stored exactly as the input mask drew it, and the
     * SMS gateway was then handed «7(999)000-55-44».
     */
    public function test_the_phone_is_stored_without_the_mask_it_was_typed_in(): void
    {
        $user = $this->master();

        $this->patchJson('/api/v1/settings', $this->payload(['phone' => '+7(999)000-55-44']))
            ->assertOk();

        $this->assertSame('+79990005544', $user->fresh()->phone);
    }

    public function test_a_local_phone_is_brought_to_one_shape(): void
    {
        $user = $this->master();

        $this->patchJson('/api/v1/settings', $this->payload(['phone' => '8 999 000 55 44']))
            ->assertOk();

        $this->assertSame('+79990005544', $user->fresh()->phone);
    }

    /**
     * Empty coordinates used to be saved as ['lat' => null, 'lng' => null],
     * which reads as «a point is set» to everything downstream.
     */
    public function test_empty_coordinates_are_no_point_at_all(): void
    {
        $user = $this->master();

        $this->patchJson('/api/v1/settings', $this->payload([
            'map_point' => ['lat' => '', 'lng' => ''],
        ]))->assertOk();

        $this->assertNull($user->fresh()->setting->map_point);
    }

    public function test_filled_coordinates_are_kept_as_numbers(): void
    {
        $user = $this->master();

        $this->patchJson('/api/v1/settings', $this->payload([
            'map_point' => ['lat' => '55.7558', 'lng' => '37.6173'],
        ]))->assertOk();

        $this->assertSame(
            ['lat' => 55.7558, 'lng' => 37.6173],
            $user->fresh()->setting->map_point
        );
    }

    /**
     * ru/validation.php held six rules out of ninety, so the master was told
     * «The map point.lat field must be a number.»
     */
    public function test_a_mistake_is_explained_in_the_language_of_the_page(): void
    {
        $this->master();

        $response = $this->patchJson('/api/v1/settings', $this->payload([
            'map_point' => ['lat' => 'широта', 'lng' => '37.6'],
        ]))->assertStatus(422);

        $message = $response->json('error.fields.map_point\.lat.0')
            ?? $response->json('error.fields')['map_point.lat'][0];

        $this->assertSame('Поле Широта должно быть числом.', $message);
    }

    public function test_the_current_password_is_still_required_to_set_a_new_one(): void
    {
        $this->master();

        $response = $this->patchJson('/api/v1/settings', $this->payload([
            'current_password' => 'not-the-password',
            'new_password' => 'brand-new-password',
            'new_password_confirmation' => 'brand-new-password',
        ]))->assertStatus(422);

        $this->assertSame(
            'Текущий пароль указан неверно.',
            $response->json('error.fields')['current_password'][0]
        );
    }

    private function master(): User
    {
        $user = User::factory()->create([
            'timezone' => 'Europe/Moscow',
            'time_format' => '24h',
            'password' => 'password',
        ]);

        Setting::create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Мария Соколова',
            'email' => 'maria@example.com',
            'timezone' => 'Europe/Moscow',
            'time_format' => '24h',
            'notifications' => ['email' => true, 'telegram' => false, 'sms' => false],
            'holidays' => [],
        ], $overrides);
    }
}
