<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * landing:import-template on a tiny fake template: what it copies, rewrites, removes and refuses.
 */
class ImportLandingTemplateTest extends TestCase
{
    private const SLUG = 'zz-import-fixture';

    private string $fixture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = storage_path('framework/testing/import-fixture');
        $this->cleanup();

        File::ensureDirectoryExists($this->fixture . '/css');
        File::ensureDirectoryExists($this->fixture . '/fonts');
        File::ensureDirectoryExists($this->fixture . '/images');
        File::ensureDirectoryExists($this->fixture . '/js');

        File::put($this->fixture . '/index.html', <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Fixture Salon</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:400" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <section class="hero" style="background-image: url('images/hero.jpg');">
        <img src="images/logo.png" alt="">
        <a href="about.html">About</a>
    </section>
    <section class="team"><h2>Our team</h2></section>
    <section class="pricing-table"><h2>Pricing</h2></section>
    <p>Lorem ipsum dolor sit amet</p>
    <form action="send.php"><input name="email"></form>
    <script src="https://maps.googleapis.com/maps/api/js?key=SECRET"></script>
    <script src="js/app.js"></script>
</body>
</html>
HTML);
        File::put($this->fixture . '/css/style.css', "@font-face { font-family: X; src: url('../fonts/x.eot'); src: url('../fonts/x.woff') format('woff'), url('../fonts/x.svg#x') format('svg'); }\n.b { background: url(../images/pattern.png); }");
        File::put($this->fixture . '/fonts/x.eot', 'eot');
        File::put($this->fixture . '/fonts/x.woff', 'woff');
        File::put($this->fixture . '/fonts/x.svg', '<svg/>');
        File::put($this->fixture . '/images/hero.jpg', 'jpg');
        File::put($this->fixture . '/images/logo.png', 'png');
        File::put($this->fixture . '/images/pattern.png', 'png');
        File::put($this->fixture . '/images/unused.jpg', 'jpg');
        File::put($this->fixture . '/js/app.js', "\$('#date').datetimepicker({});
console.log(1);");
        File::put($this->fixture . '/LICENSE.txt', 'Original license text');
    }

    protected function tearDown(): void
    {
        $this->cleanup();

        parent::tearDown();
    }

    private function cleanup(): void
    {
        File::deleteDirectory(storage_path('framework/testing/import-fixture'));
        File::deleteDirectory(public_path('landing-templates/' . self::SLUG));
        File::delete(resource_path('landing-templates/' . self::SLUG . '.php'));
        File::delete(resource_path('views/landings/full/' . self::SLUG . '.blade.php'));
    }

    private function run_import(array $options = []): int
    {
        return Artisan::call('landing:import-template', array_merge([
            'source' => $this->fixture,
            'slug' => self::SLUG,
            '--source-url' => 'https://example.com/fixture',
            '--license' => 'CC-BY-4.0',
            '--attribution' => 'https://example.com',
            '--categories' => 'nails,not-a-category',
            '--purposes' => 'booking,ads',
            '--name' => 'Fixture',
        ], $options));
    }

    public function test_it_copies_only_used_files_and_rewrites_the_page(): void
    {
        $this->assertSame(0, $this->run_import(), Artisan::output());

        $assets = public_path('landing-templates/' . self::SLUG);

        // used by the page or by its stylesheet
        foreach (['css/style.css', 'images/hero.jpg', 'images/logo.png', 'images/pattern.png', 'js/app.js', 'fonts/x.woff'] as $file) {
            $this->assertFileExists("{$assets}/{$file}", $file);
        }
        // not referenced, and the Internet Explorer font fallbacks next to a woff
        foreach (['images/unused.jpg', 'fonts/x.eot', 'fonts/x.svg'] as $file) {
            $this->assertFileDoesNotExist("{$assets}/{$file}", $file);
        }

        $view = File::get(resource_path('views/landings/full/' . self::SLUG . '.blade.php'));
        $this->assertStringContainsString("{{ \$asset('css/style.css') }}", $view);
        $this->assertStringContainsString("url('{{ \$asset('images/hero.jpg') }}')", $view);
        $this->assertStringContainsString('<title>{{ $landing->title }}</title>', $view);
        $this->assertStringContainsString('name="csrf-token"', $view);
        $this->assertStringContainsString("landings.partials.request-script", $view);
        $this->assertStringContainsString("landings.partials.editor-assets", $view);
        $this->assertStringContainsString('PageData', $view);
        // links to other pages of the template lead nowhere on a single landing
        $this->assertStringNotContainsString('about.html', $view);
        // a map with a key must not ship
        $this->assertStringNotContainsString('maps.googleapis', $view);
        $this->assertStringNotContainsString('SECRET', $view);

        $this->assertStringContainsString('Original license text', File::get("{$assets}/LICENSE.txt"));
    }

    public function test_it_writes_a_manifest_the_registry_accepts(): void
    {
        $this->assertSame(0, $this->run_import());

        $manifest = require resource_path('landing-templates/' . self::SLUG . '.php');

        $this->assertSame(self::SLUG, $manifest['slug']);
        $this->assertSame('CC-BY-4.0', $manifest['license']);
        $this->assertSame('https://example.com', $manifest['attribution']);
        $this->assertSame(['nails'], $manifest['categories'], 'unknown categories are dropped');
        $this->assertSame(['booking', 'ads'], $manifest['purposes']);
        $this->assertSame('landings.full.' . self::SLUG, $manifest['templates']['general']);
    }

    public function test_the_report_flags_what_needs_a_human(): void
    {
        $this->run_import();
        $output = Artisan::output();

        $this->assertStringContainsString("Font 'poppins'", $output, 'a font without Cyrillic');
        $this->assertStringContainsString('people:', $output);
        $this->assertStringContainsString('pricing:', $output);
        $this->assertStringContainsString('<form>', $output);
        $this->assertStringContainsString('Placeholder text', $output);
        $this->assertStringContainsString('maps.googleapis', $output, 'the removed script is reported');
        $this->assertStringContainsString('js/app.js calls datetimepicker', $output, 'a call to a plugin that is not loaded');
    }

    public function test_it_refuses_without_a_license_or_a_credit_link_and_never_overwrites(): void
    {
        $this->assertSame(1, $this->run_import(['--license' => 'MIT']));
        $this->assertSame(1, $this->run_import(['--attribution' => '']));
        $this->assertSame(1, $this->run_import(['--source-url' => '']));
        $this->assertFileDoesNotExist(resource_path('landing-templates/' . self::SLUG . '.php'));

        $this->assertSame(0, $this->run_import());
        $this->assertSame(1, $this->run_import(), 'a second import needs --force');
        $this->assertSame(0, $this->run_import(['--force' => true]));
    }
}
