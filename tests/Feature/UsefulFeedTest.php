<?php

namespace Tests\Feature;

use App\Models\LearningArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * «Что читать, сколько это займёт и читала ли я это» — вопросы к «Полезному».
 */
class UsefulFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'ru');
    }

    public function test_the_reading_time_is_measured_from_the_text_not_typed_beside_it(): void
    {
        $this->master();

        // Three bullet points were labelled «7 мин чтения».
        $this->article('Юридические нюансы', [
            'ru' => ['sections' => [
                'Регистрация и налоги.',
                'Договор-оферта и политика конфиденциальности.',
                'Онлайн-касса и безопасные платежи.',
            ]],
        ], ['reading_time_minutes' => 7]);

        $this->assertSame(1, $this->feed()['featured_post']['reading_minutes']);
    }

    public function test_a_section_heading_is_a_heading_and_not_a_json_key(): void
    {
        $this->master();
        $this->article('Первый пост', ['ru' => ['structure' => ['Заголовок.', 'История.']]]);

        $blocks = $this->feed()['featured_post']['content_blocks'];

        // The page used to print the key itself: «SECTIONS», in English caps.
        $this->assertSame('Из чего собрать', $blocks[0]['title']);
        $this->assertSame(['Заголовок.', 'История.'], $blocks[0]['items']);
    }

    public function test_an_unknown_key_never_reaches_the_reader(): void
    {
        $this->master();
        $this->article('Странный ключ', ['ru' => ['whatever_key' => ['Пункт.']]]);

        $this->assertSame('Ещё', $this->feed()['featured_post']['content_blocks'][0]['title']);
    }

    public function test_a_post_can_be_marked_read_and_then_hidden(): void
    {
        $this->master();
        $one = $this->article('Первый', ['ru' => ['ideas' => ['Раз.']]]);
        $this->article('Второй', ['ru' => ['ideas' => ['Два.']]]);

        $this->assertSame(2, $this->feed()['counts']['unread']);

        $this->postJson('/api/v1/useful/posts/' . $one->id . '/read', ['read' => true])
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $feed = $this->feed();
        $this->assertSame(1, $feed['counts']['unread']);

        $unreadOnly = $this->feed(['unread' => 1]);
        $titles = collect([$unreadOnly['featured_post']])
            ->filter()
            ->concat($unreadOnly['posts'])
            ->pluck('title')
            ->all();

        $this->assertSame(['Второй'], $titles);

        $this->postJson('/api/v1/useful/posts/' . $one->id . '/read', ['read' => false])->assertOk();
        $this->assertSame(2, $this->feed()['counts']['unread']);
    }

    public function test_search_looks_at_the_title_and_the_summary(): void
    {
        $this->master();
        $this->article('Инструкция: сарафанное радио', ['ru' => ['steps' => ['Раз.']]]);
        $this->article('Юридические нюансы', ['ru' => ['sections' => ['Два.']]], ['summary' => ['ru' => 'Про налоги и кассу.']]);

        $found = collect($this->feed(['search' => 'налог'])['posts'])->pluck('title')->all();
        $this->assertSame(['Юридические нюансы'], $found);

        // «Статья недели» is a recommendation, so it steps out of a search.
        $this->assertNull($this->feed(['search' => 'налог'])['featured_post']);
    }

    private function feed(array $query = []): array
    {
        return $this->getJson('/api/v1/useful/overview?' . http_build_query($query))->assertOk()->json();
    }

    private function master(): User
    {
        $master = User::factory()->create(['timezone' => 'Europe/Moscow']);
        Sanctum::actingAs($master);

        return $master;
    }

    private function article(string $title, array $content, array $overrides = []): LearningArticle
    {
        return LearningArticle::create(array_merge([
            'slug' => \Illuminate\Support\Str::slug($title) . '-' . uniqid(),
            'title' => ['ru' => $title, 'en' => $title],
            'summary' => ['ru' => 'Короткое описание.', 'en' => 'Short summary.'],
            'content' => $content,
            'reading_time_minutes' => 5,
            'is_published' => true,
            'sort_order' => 0,
        ], $overrides));
    }
}
