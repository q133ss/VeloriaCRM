<?php

namespace Tests\Feature;

use App\Models\Landing;
use App\Models\User;
use App\Services\Landing\LandingContent;
use App\Services\Landing\TemplateRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LandingEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Uploads go to public/uploads; tests must not leave files there.
        Storage::fake('landing_media');

        // The seasonal/consultation types are added to the landings CHECK constraint
        // only on Postgres (2026_09_08_030000); the in-memory SQLite keeps the old
        // three-value enum. Ignore it so every type can be rendered here.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA ignore_check_constraints = ON');
        }
    }

    private function landing(User $user, string $layout = 'salone', string $type = 'general', array $settings = []): Landing
    {
        return Landing::create([
            'user_id' => $user->id,
            'title' => 'Студия Анны',
            'type' => $type,
            'landing' => app(TemplateRegistry::class)->templateFor($layout, $type),
            'slug' => 'studio-' . $layout . '-' . $type . '-' . uniqid(),
            'settings' => array_merge([
                'primary_color' => 'indigo',
                'background_type' => 'preset',
                'phone' => '+7 900 123-45-67',
                'proof_items_text' => "Первый пункт\nВторой пункт\nТретий пункт",
                'faq_items_text' => "Вопрос? Ответ.",
                'headline' => 'Заголовок',
                'description' => 'Описание',
                'service_description' => 'Про услугу',
                'service_name' => 'Маникюр',
                'greeting' => 'Здравствуйте!',
                'benefit_items_text' => "Плюс раз\nПлюс два",
            ], $settings),
            'is_active' => true,
        ]);
    }

    /* ---------------------------------------------------------- registry */

    public function test_every_manifest_is_consistent_with_the_field_catalog_and_views(): void
    {
        $registry = app(TemplateRegistry::class);

        $this->assertNotEmpty($registry->layouts());
        $this->assertContains('landings.full.salone', $registry->templateIds());
        $this->assertContains('landings.full.pretty', $registry->templateIds());
        $this->assertNotContains('landings.templates.general', $registry->templateIds());

        foreach ($registry->layouts() as $layout) {
            foreach ($layout['fields'] as $key) {
                $this->assertArrayHasKey($key, LandingContent::FIELDS, "{$layout['slug']} lists unknown field {$key}");
            }

            foreach (TemplateRegistry::TYPES as $type) {
                $this->assertArrayHasKey($type, $layout['templates'], "{$layout['slug']} has no template for {$type}");
                $this->assertTrue(view()->exists($layout['templates'][$type]), "view {$layout['templates'][$type]} is missing");
            }

            foreach ($layout['images'] as $key => $asset) {
                $this->assertFileExists(public_path($asset), "{$layout['slug']} default photo {$key} is missing");
            }

            if ($layout['thumb']) {
                $this->assertFileExists(public_path($layout['thumb']));
            }
        }
    }

    public function test_landing_cannot_point_at_an_arbitrary_view(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $this->postJson('/api/v1/landings', [
            'title' => 'X', 'type' => 'general', 'landing' => 'admin.dashboard',
            'settings' => ['primary_color' => 'indigo', 'background_type' => 'preset', 'show_all_services' => true],
        ])->assertStatus(422);
    }

    /* ----------------------------------------------- every template, smoke */

    public function test_every_registered_template_renders_and_only_the_owner_gets_editor_markers(): void
    {
        $owner = User::factory()->create();
        $registry = app(TemplateRegistry::class);

        foreach ($registry->layouts() as $layout) {
            foreach (TemplateRegistry::TYPES as $type) {
                $landing = $this->landing($owner, $layout['slug'], $type);

                $this->get('/l/' . $landing->slug)
                    ->assertOk()
                    ->assertDontSee('data-lf-key', false)
                    ->assertDontSee('landing-editor/editor.js', false);

                // Someone else asking for edit mode gets the ordinary page.
                Sanctum::actingAs(User::factory()->create());
                $this->get('/l/' . $landing->slug . '?edit=1')
                    ->assertOk()
                    ->assertDontSee('data-lf-key', false);

                Sanctum::actingAs($owner);
                $this->get('/l/' . $landing->slug . '?edit=1')
                    ->assertOk()
                    ->assertSee('data-lf-key', false)
                    ->assertSee('landing-editor/editor.js', false);

            }
        }
    }

    public function test_a_page_on_the_retired_classic_layout_falls_back_to_the_default_template(): void
    {
        $owner = User::factory()->create();
        $landing = $this->landing($owner);
        $landing->update(['landing' => 'landings.templates.general']);

        $this->get('/l/' . $landing->slug)->assertOk()->assertSee('landing-templates/salone', false);
    }

    public function test_edit_mode_does_not_count_a_view(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());
        $landing = $this->landing($owner);

        $this->get('/l/' . $landing->slug . '?edit=1')->assertOk();

        $this->assertSame(0, (int) $landing->fresh()->views);
    }

    /* ------------------------------------------------------------ content */

    public function test_owner_edits_text_and_the_rest_of_settings_survives(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());
        $landing = $this->landing($owner);

        $this->patchJson("/api/v1/landings/{$landing->id}/content", [
            'changes' => [['key' => 'hero_title', 'value' => "  Новый   заголовок \n"]],
        ])->assertOk()->assertJsonPath('data.values.hero_title', 'Новый заголовок');

        $settings = $landing->fresh()->settings;
        $this->assertSame('Новый заголовок', $settings['headline']);
        $this->assertSame('+7 900 123-45-67', $settings['phone']);
        $this->assertContains('headline', $settings['_edited']);
    }

    public function test_title_and_list_items_are_saved_in_their_own_places(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());
        $landing = $this->landing($owner);

        $this->patchJson("/api/v1/landings/{$landing->id}/content", [
            'changes' => [
                ['key' => 'title', 'value' => 'Beauty Bar'],
                ['key' => 'proof_items_text', 'value' => "Один\n\n  Два  \nТри"],
            ],
        ])->assertOk();

        $fresh = $landing->fresh();
        $this->assertSame('Beauty Bar', $fresh->title);
        $this->assertSame("Один\nДва\nТри", $fresh->settings['proof_items_text']);
    }

    public function test_emptying_an_optional_field_removes_the_key_so_the_default_returns(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());
        $landing = $this->landing($owner, settings: ['cta_label' => 'Жми']);

        $this->patchJson("/api/v1/landings/{$landing->id}/content", [
            'changes' => [['key' => 'cta_label', 'value' => '   ']],
        ])->assertOk();

        $this->assertArrayNotHasKey('cta_label', $landing->fresh()->settings);
    }

    public function test_hero_text_writes_to_the_key_the_layout_reads(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());
        $salone = $this->landing($owner, 'salone', 'general');
        $pretty = $this->landing($owner, 'pretty', 'general');
        $service = $this->landing($owner, 'salone', 'service');

        foreach ([$salone, $pretty, $service] as $landing) {
            $this->patchJson("/api/v1/landings/{$landing->id}/content", [
                'changes' => [['key' => 'hero_text', 'value' => 'Новый текст']],
            ])->assertOk();
        }

        $this->assertSame('Новый текст', $salone->fresh()->settings['greeting']);
        $this->assertSame('Новый текст', $pretty->fresh()->settings['greeting']);
        $this->assertSame('Новый текст', $service->fresh()->settings['service_description']);
    }

    public function test_someone_elses_landing_is_not_found(): void
    {
        $landing = $this->landing(User::factory()->create());
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/v1/landings/{$landing->id}/content", [
            'changes' => [['key' => 'hero_title', 'value' => 'Взлом']],
        ])->assertNotFound();

        $this->assertSame('Заголовок', $landing->fresh()->settings['headline']);
    }

    public function test_guest_cannot_edit(): void
    {
        $landing = $this->landing(User::factory()->create());

        $this->patchJson("/api/v1/landings/{$landing->id}/content", [
            'changes' => [['key' => 'hero_title', 'value' => 'X']],
        ])->assertUnauthorized();
    }

    public function test_keys_outside_the_manifest_and_bad_values_are_rejected_without_partial_saves(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());
        $landing = $this->landing($owner);

        // promo_code is a real setting but not an editable field of any template.
        $this->patchJson("/api/v1/landings/{$landing->id}/content", [
            'changes' => [['key' => 'hero_title', 'value' => 'Ок'], ['key' => 'promo_code', 'value' => 'HACK']],
        ])->assertStatus(422);

        // Images go through their own endpoint, never as text.
        $this->patchJson("/api/v1/landings/{$landing->id}/content", [
            'changes' => [['key' => 'hero_image_1', 'value' => 'http://evil']],
        ])->assertStatus(422);

        $this->patchJson("/api/v1/landings/{$landing->id}/content", [
            'changes' => [['key' => 'hero_title', 'value' => str_repeat('я', 300)]],
        ])->assertStatus(422);

        $this->patchJson("/api/v1/landings/{$landing->id}/content", [
            'changes' => [['key' => 'title', 'value' => '   ']],
        ])->assertStatus(422);

        $this->patchJson("/api/v1/landings/{$landing->id}/content", [
            'changes' => [['key' => 'phone', 'value' => 'позвоните мне']],
        ])->assertStatus(422);

        $fresh = $landing->fresh();
        $this->assertSame('Заголовок', $fresh->settings['headline']);
        $this->assertSame('Студия Анны', $fresh->title);
        $this->assertArrayNotHasKey('promo_code', $fresh->settings);
    }

    public function test_markup_is_stored_as_text_and_escaped_on_the_page(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());
        $landing = $this->landing($owner);

        $this->patchJson("/api/v1/landings/{$landing->id}/content", [
            'changes' => [['key' => 'hero_title', 'value' => '<script>alert(1)</script>']],
        ])->assertOk();

        $this->get('/l/' . $landing->slug)
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_the_full_form_save_does_not_wipe_content_edited_by_click(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());
        $landing = $this->landing($owner, 'salone', 'promotion', [
            'promotion_id' => null,
        ]);

        $this->patchJson("/api/v1/landings/{$landing->id}/content", [
            'changes' => [['key' => 'booking_hint', 'value' => 'Отвечаю за час']],
        ])->assertOk();

        // The wizard/edit form patches only title here; settings must stay as they are.
        $this->patchJson("/api/v1/landings/{$landing->id}", ['title' => 'Другое имя'])->assertOk();

        $this->assertSame('Отвечаю за час', $landing->fresh()->settings['booking_hint']);
    }

    /* ------------------------------------------------------------- images */

    public function test_photo_upload_replaces_the_slot_and_removes_the_old_file(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());
        $landing = $this->landing($owner);

        $first = $this->postJson("/api/v1/landings/{$landing->id}/images", [
            'key' => 'hero_image_1',
            'file' => UploadedFile::fake()->image('a.jpg', 2400, 1600),
        ])->assertOk();

        $firstPath = $landing->fresh()->settings['images']['hero_image_1'];
        Storage::disk('landing_media')->assertExists($firstPath);
        $this->assertStringContainsString('landings/' . $landing->id . '/hero_image_1-', $first->json('data.url'));

        $this->postJson("/api/v1/landings/{$landing->id}/images", [
            'key' => 'hero_image_1',
            'file' => UploadedFile::fake()->image('b.png', 800, 600),
        ])->assertOk();

        Storage::disk('landing_media')->assertMissing($firstPath);
        $this->assertNotSame($firstPath, $landing->fresh()->settings['images']['hero_image_1']);

        // The page now serves the uploaded photo instead of the stock one.
        $this->get('/l/' . $landing->slug)->assertOk()->assertSee('landings/' . $landing->id . '/hero_image_1-', false);
    }

    public function test_uploaded_photo_is_scaled_down_and_reencoded_when_gd_is_available(): void
    {
        if (! function_exists('imagecreatefromstring')) {
            $this->markTestSkipped('GD is not installed');
        }

        Sanctum::actingAs($owner = User::factory()->create());
        $landing = $this->landing($owner);

        $this->postJson("/api/v1/landings/{$landing->id}/images", [
            'key' => 'about_image',
            'file' => UploadedFile::fake()->image('big.jpg', 4000, 3000),
        ])->assertOk();

        $path = $landing->fresh()->settings['images']['about_image'];
        [$width, $height] = getimagesizefromstring(Storage::disk('landing_media')->get($path));

        $this->assertLessThanOrEqual(1600, max($width, $height));
    }

    public function test_photo_upload_rejects_non_images_unknown_slots_and_other_users(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());
        $landing = $this->landing($owner);

        $this->postJson("/api/v1/landings/{$landing->id}/images", [
            'key' => 'hero_image_1',
            'file' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php'),
        ])->assertStatus(422);

        $this->postJson("/api/v1/landings/{$landing->id}/images", [
            'key' => 'hero_image_1',
            'file' => UploadedFile::fake()->create('big.jpg', 9000, 'image/jpeg'),
        ])->assertStatus(422);

        $this->postJson("/api/v1/landings/{$landing->id}/images", [
            'key' => 'not_a_slot',
            'file' => UploadedFile::fake()->image('a.jpg'),
        ])->assertStatus(422);

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/v1/landings/{$landing->id}/images", [
            'key' => 'hero_image_1',
            'file' => UploadedFile::fake()->image('a.jpg'),
        ])->assertNotFound();

        $this->assertArrayNotHasKey('images', $landing->fresh()->settings);
    }

    public function test_photo_reset_restores_the_stock_photo_and_deletes_the_upload(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());
        $landing = $this->landing($owner);

        $this->postJson("/api/v1/landings/{$landing->id}/images", [
            'key' => 'hero_image_2',
            'file' => UploadedFile::fake()->image('a.jpg'),
        ])->assertOk();
        $path = $landing->fresh()->settings['images']['hero_image_2'];

        $this->deleteJson("/api/v1/landings/{$landing->id}/images/hero_image_2")
            ->assertOk()
            ->assertJsonPath('data.url', asset('landing-templates/salone/img/hero-slider-2.jpg'));

        Storage::disk('landing_media')->assertMissing($path);
        $this->assertArrayNotHasKey('images', $landing->fresh()->settings);
    }

    public function test_deleting_a_landing_removes_its_photos(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());
        $landing = $this->landing($owner);

        $this->postJson("/api/v1/landings/{$landing->id}/images", [
            'key' => 'hero_image_1',
            'file' => UploadedFile::fake()->image('a.jpg'),
        ])->assertOk();
        $path = $landing->fresh()->settings['images']['hero_image_1'];

        $this->deleteJson("/api/v1/landings/{$landing->id}")->assertOk();

        Storage::disk('landing_media')->assertMissing($path);
    }
}
