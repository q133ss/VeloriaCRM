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

/**
 * A template used to expose a handful of fields while its headings, captions
 * and big photos were fixed in the Blade file — and some fields it did print
 * were missing from the manifest, so the editor refused them.
 */
class LandingEditableCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('landing_media');

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA ignore_check_constraints = ON');
        }
    }

    private function landing(User $user, string $layout): Landing
    {
        return Landing::create([
            'user_id' => $user->id,
            'title' => 'Студия Анны',
            'type' => 'general',
            'landing' => app(TemplateRegistry::class)->templateFor($layout, 'general'),
            'slug' => 'cov-' . $layout . '-' . uniqid(),
            'settings' => [
                'primary_color' => 'indigo',
                'background_type' => 'preset',
                'phone' => '+7 900 123-45-67',
                'address' => 'Москва, Тверская 1',
                'proof_items_text' => "Первый пункт
Второй пункт
Третий пункт",
                'benefit_items_text' => "Плюс раз
Плюс два",
                'faq_items_text' => 'Вопрос? Ответ.',
            ],
            'is_active' => true,
        ]);
    }

    public function test_every_editable_element_of_every_template_is_declared_in_its_manifest(): void
    {
        foreach (app(TemplateRegistry::class)->layouts() as $layout) {
            $source = file_get_contents(resource_path('views/' . str_replace('.', '/', $layout['templates']['general']) . '.blade.php'));

            preg_match_all('/<x-landing\.(?:text|image|bg)\b[^>]*?(?<![:\w])key="([a-z_0-9]+)"/s', $source, $static);
            $keys = array_unique($static[1]);

            // Keys built in a loop: `:key="'work_image_' . $i"` over `[1, 2, 3]`.
            preg_match_all('/@foreach\(\[([\d, ]+)\] as \$(\w+)\)(.*?)@endforeach/s', $source, $loops, PREG_SET_ORDER);
            foreach ($loops as [, $numbers, $var, $body]) {
                if (preg_match_all('/:key="\'([a-z_]+)\' \. \$' . $var . '"/', $body, $prefixes)) {
                    foreach ($prefixes[1] as $prefix) {
                        foreach (array_map('trim', explode(',', $numbers)) as $n) {
                            $keys[] = $prefix . $n;
                        }
                    }
                }
            }

            foreach (array_unique($keys) as $key) {
                if (LandingContent::isLabel($key)) {
                    continue;
                }

                $this->assertContains($key, $layout['fields'], "{$layout['slug']} prints {$key} but its manifest does not list it, so the editor refuses it");

                if ((LandingContent::FIELDS[$key]['kind'] ?? null) === 'image') {
                    $this->assertArrayHasKey($key, $layout['images'], "{$layout['slug']} has no stock photo for {$key}");
                }
            }
        }
    }

    public function test_no_template_shows_a_caption_that_the_owner_cannot_click(): void
    {
        $captions = [];

        foreach (app(TemplateRegistry::class)->layouts() as $layout) {
            $source = file_get_contents(resource_path('views/' . str_replace('.', '/', $layout['templates']['general']) . '.blade.php'));
            $editable = preg_match_all('/<x-landing\.text key="lbl_/', $source);

            // Even the one-screen promo layouts keep their headings editable.
            $this->assertGreaterThanOrEqual(3, $editable, "{$layout['slug']} keeps its headings fixed");

            // A heading or a kicker must never be a bare lang string.
            $this->assertDoesNotMatchRegularExpression(
                '/<(h[1-6]|span class="subheading")[^>]*>\s*\{\{\s*__\(\'landings\.common\.(services|booking|about|master|prices|contacts)_(title|kicker)\'\)/',
                $source,
                "{$layout['slug']} has a fixed section heading",
            );
        }
    }

    public function test_a_caption_can_be_edited_on_every_template_without_a_manifest_entry(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);

        foreach (app(TemplateRegistry::class)->layouts() as $layout) {
            $landing = $this->landing($owner, $layout['slug']);

            // The page prints each caption in an editable element when the owner is editing.
            $edit = $this->get('/l/' . $landing->slug . '?edit=1')->assertOk()->getContent();
            $this->assertSame(1, preg_match('/data-lf-key="(lbl_[a-z_0-9]+)"/', $edit, $found), "{$layout['slug']} has no editable caption");

            $this->patchJson("/api/v1/landings/{$landing->id}/content", [
                'changes' => [['key' => $found[1], 'value' => 'Выберите удобное время']],
            ])->assertOk();

            $page = $this->get('/l/' . $landing->slug)->assertOk()->assertDontSee('data-lf-key', false);
            $this->assertStringContainsString('Выберите удобное время', $page->getContent(), "{$layout['slug']}: {$found[1]} is not printed");
        }
    }

    public function test_an_emptied_caption_returns_to_the_default_text(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());
        $landing = $this->landing($owner, 'haircare');

        $this->patchJson("/api/v1/landings/{$landing->id}/content", [
            'changes' => [['key' => 'lbl_haircare_welcome', 'value' => 'Рада вас видеть']],
        ])->assertOk();
        $this->get('/l/' . $landing->slug)->assertSee('Рада вас видеть');

        $this->patchJson("/api/v1/landings/{$landing->id}/content", [
            'changes' => [['key' => 'lbl_haircare_welcome', 'value' => '']],
        ])->assertOk();

        $this->assertArrayNotHasKey('lbl_haircare_welcome', $landing->fresh()->settings);
        $this->get('/l/' . $landing->slug)
            ->assertDontSee('Рада вас видеть')
            ->assertSee(__('landings.haircare.welcome'));
    }

    public function test_caption_keys_are_validated_and_capped(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());
        $landing = $this->landing($owner, 'haircare');
        $url = "/api/v1/landings/{$landing->id}/content";

        // Only the lbl_ namespace is free-form; anything else still has to be in the manifest.
        $this->patchJson($url, ['changes' => [['key' => 'lbl_', 'value' => 'x']]])->assertStatus(422);
        $this->patchJson($url, ['changes' => [['key' => 'lbl_Bad-Key', 'value' => 'x']]])->assertStatus(422);
        $this->patchJson($url, ['changes' => [['key' => 'promo_code', 'value' => 'x']]])->assertStatus(422);
        $this->patchJson($url, ['changes' => [['key' => 'lbl_common_book', 'value' => str_repeat('я', 501)]]])->assertStatus(422);

        // Markup is stored as text and escaped.
        $this->patchJson($url, ['changes' => [['key' => 'lbl_common_book', 'value' => '<script>alert(1)</script>']]])->assertOk();
        $this->get('/l/' . $landing->slug)->assertDontSee('<script>alert(1)</script>', false);

        // The number of stored captions is bounded.
        $changes = [];
        for ($i = 0; $i <= LandingContent::MAX_LABELS_PER_LANDING; $i++) {
            $changes[] = ['key' => 'lbl_flood_' . $i, 'value' => 'x'];
        }
        $this->patchJson($url, ['changes' => $changes])->assertStatus(422);
    }

    public function test_the_extra_photos_can_be_uploaded_where_the_template_uses_them(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());
        $landing = $this->landing($owner, 'haircare');

        $this->get('/l/' . $landing->slug . '?edit=1')
            ->assertOk()
            ->assertSee('data-lf-key="extra_image_1"', false)
            ->assertSee('data-lf-key="work_image_4"', false);

        foreach (['extra_image_1', 'extra_image_2', 'work_image_4'] as $key) {
            $this->postJson("/api/v1/landings/{$landing->id}/images", [
                'key' => $key,
                'file' => UploadedFile::fake()->image('photo.jpg', 800, 600),
            ])->assertOk();
        }

        // A template that does not use a slot still refuses it.
        $this->postJson("/api/v1/landings/{$landing->id}/images", [
            'key' => 'extra_image_3',
            'file' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ])->assertStatus(422);
    }
}
