<?php

namespace Tests\Feature;

use App\Support\VideoEmbed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_leads_with_self_booking_and_has_no_soon_badges(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(__('landing.hero.title'))
            ->assertDontSee('Скоро')
            ->assertDontSee('class="soon"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('og:image', false);
    }

    public function test_home_links_featured_templates_to_live_demos(): void
    {
        config(['landing_home.featured_templates' => ['pretty', 'missing-template', 'salone']]);

        $response = $this->get('/')->assertOk();

        $response->assertSee(route('landings.demo', 'pretty'), false)
            ->assertSee(route('landings.demo', 'salone'), false)
            ->assertDontSee(route('landings.demo', 'missing-template'), false);

        $this->get(route('landings.demo', 'pretty'))->assertOk();
    }

    public function test_reviews_section_is_hidden_until_reviews_are_configured(): void
    {
        config(['landing_home.video_reviews' => []]);
        $this->get('/')->assertOk()->assertDontSee('id="reviews"', false);

        config(['landing_home.video_reviews' => [
            ['url' => 'https://www.youtube.com/shorts/aqz-KE-bpKQ', 'name' => 'Анна', 'role' => 'Мастер маникюра', 'city' => 'Казань'],
            ['url' => 'https://example.com/not-a-video', 'name' => 'Нет видео'],
        ]]);

        $this->get('/')
            ->assertOk()
            ->assertSee('id="reviews"', false)
            ->assertSee('https://www.youtube.com/embed/aqz-KE-bpKQ', false)
            ->assertSee('Анна')
            ->assertDontSee('Нет видео');
    }

    public function test_android_button_appears_only_with_a_configured_link(): void
    {
        config(['landing_home.android_app_url' => null]);
        $this->get('/')->assertOk()->assertDontSee('data-android-link', false);

        config(['landing_home.android_app_url' => 'https://play.google.com/store/apps/details?id=ru.veloria.client']);
        $this->get('/')
            ->assertOk()
            ->assertSee('data-android-link', false)
            ->assertSee('play.google.com/store/apps/details?id=ru.veloria.client', false);
    }

    public function test_video_embed_understands_the_links_people_actually_paste(): void
    {
        $this->assertSame('https://www.youtube.com/embed/abcDEF12345?autoplay=1&rel=0', VideoEmbed::url('https://www.youtube.com/watch?v=abcDEF12345'));
        $this->assertSame('https://www.youtube.com/embed/abcDEF12345?autoplay=1&rel=0', VideoEmbed::url('https://youtu.be/abcDEF12345'));
        $this->assertSame('https://www.youtube.com/embed/abcDEF12345?autoplay=1&rel=0', VideoEmbed::url('https://youtube.com/shorts/abcDEF12345?si=x'));
        $this->assertSame(
            'https://rutube.ru/play/embed/c6cc4d620b1d4338901770a44b3e82f4?autoplay=1',
            VideoEmbed::url('https://rutube.ru/video/c6cc4d620b1d4338901770a44b3e82f4/')
        );
        $this->assertSame(
            'https://vk.com/video_ext.php?oid=-22822305&id=456242110&autoplay=1',
            VideoEmbed::url('https://vkvideo.ru/video-22822305_456242110')
        );
        $this->assertSame(
            'https://vk.com/video_ext.php?oid=-1&id=2&autoplay=1',
            VideoEmbed::url('https://vk.com/clip-1_2')
        );
        $this->assertNull(VideoEmbed::url('https://example.com/video'));
        $this->assertNull(VideoEmbed::url(''));
    }
}
