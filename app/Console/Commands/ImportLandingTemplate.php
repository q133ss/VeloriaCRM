<?php

namespace App\Console\Commands;

use App\Services\Landing\TemplateRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Turns a downloaded HTML template into a draft landing template: copies only the
 * files the page really uses, rewrites the paths, drops the scripts that must not
 * ship, wires in the booking widget and the click editor, writes a manifest and a
 * license file, and prints what still needs a human (fake content blocks, fonts
 * without Cyrillic, forms, weight). See docs/landing-templates.md.
 */
class ImportLandingTemplate extends Command
{
    protected $signature = 'landing:import-template
        {source : Folder with the downloaded template}
        {slug : Latin lowercase name, e.g. spa-soft}
        {--entry=index.html : The page to import}
        {--name= : Title shown in the wizard}
        {--description= : One line shown in the wizard}
        {--source-url= : Where the template comes from}
        {--license= : One of CC-BY-3.0, CC-BY-4.0, free-commercial}
        {--attribution= : Credit link the license makes us keep (URL)}
        {--categories= : Comma list: nails,brows,hair,barber,spa,cosmetology,makeup}
        {--purposes=booking : Comma list: booking,promo,ads,portfolio}
        {--max-mb=2 : Warn when the copied assets weigh more}
        {--force : Overwrite existing files}';

    protected $description = 'Import a downloaded HTML template as a draft landing template';

    /** Fonts known to cover Cyrillic; anything else in a Google Fonts link is flagged. */
    private const CYRILLIC_FONTS = [
        'montserrat', 'roboto', 'open sans', 'prata', 'playfair display', 'manrope', 'pt sans', 'pt serif', 'lora', 'raleway',
        'nunito', 'inter', 'ubuntu', 'cormorant', 'cormorant garamond', 'comfortaa', 'jost', 'marck script', 'caveat',
        'oswald', 'rubik', 'merriweather', 'fira sans', 'noto sans', 'noto serif', 'source sans pro', 'exo 2', 'philosopher',
        'bad script', 'pacifico', 'amatic sc', 'lobster', 'yeseva one', 'tenor sans', 'russo one', 'arsenal', 'golos text',
    ];

    /** Section keywords and what to do with them (the replacement dictionary). */
    private const BLOCKS = [
        'team|staff|expert|specialist' => 'people: replace with one master card (master_photo / master_role / master_bio)',
        'testimonial|review' => 'reviews: no data source, remove',
        'blog|news|article' => 'blog: replace with the master\'s questions and answers (faqItems)',
        'partner|brand|client-logo' => 'partner logos: remove',
        'pricing|price' => 'pricing: build from the services catalog ($priced)',
        'counter|fact|stat' => 'counters: use real figures ($stats) or remove',
        'newsletter|subscribe' => 'newsletter: remove',
        'map' => 'map: drop scripts with keys, show the address instead',
        'gallery|portfolio|work' => 'gallery: photo slots (work_image_N), stock photos are not her work',
        'faq' => 'faq: faqItems',
        'appointment|booking|contact-form' => 'form: replace with the shared booking widget',
    ];

    private array $report = [];

    public function handle(): int
    {
        $slug = Str::slug((string) $this->argument('slug'));
        $sourceDir = rtrim(str_replace('\\', '/', (string) $this->argument('source')), '/');
        $entry = $sourceDir . '/' . $this->option('entry');

        if ($slug === '' || $slug !== $this->argument('slug')) {
            return $this->stop('Slug must be lowercase latin letters, digits and dashes.');
        }
        if (! is_file($entry)) {
            return $this->stop("Entry page not found: {$entry}");
        }

        $license = (string) $this->option('license');
        if (! in_array($license, TemplateRegistry::LICENSES, true)) {
            return $this->stop('Pass --license=' . implode('|', TemplateRegistry::LICENSES));
        }
        if (str_starts_with($license, 'CC-BY') && ! $this->option('attribution')) {
            return $this->stop('A CC BY license needs --attribution=<credit link URL>.');
        }
        if (! $this->option('source-url')) {
            return $this->stop('Pass --source-url=<where the template comes from>.');
        }

        $targets = [
            'manifest' => resource_path("landing-templates/{$slug}.php"),
            'view' => resource_path("views/landings/full/{$slug}.blade.php"),
            'assets' => public_path("landing-templates/{$slug}"),
        ];
        foreach ($targets as $path) {
            if (file_exists($path) && ! $this->option('force')) {
                return $this->stop("Already exists: {$path} (use --force)");
            }
        }

        $html = (string) file_get_contents($entry);
        $baseDir = dirname($entry);
        $rootDir = $sourceDir;

        // 1. assets the page really uses
        $used = [];
        $external = [];
        $blade = $this->rewrite($html, $baseDir, $rootDir, $used, $external);
        $this->followStylesheets($used, $rootDir, $baseDir);

        // 2. copy them (images are scaled down)
        $copied = $this->copyAssets($used, $rootDir, $targets['assets']);

        // 3. page
        $blade = $this->toBlade($blade, $slug, (string) $this->option('name') ?: Str::headline($slug));

        File::ensureDirectoryExists(dirname($targets['view']));
        File::ensureDirectoryExists(dirname($targets['manifest']));
        file_put_contents($targets['view'], $blade);
        file_put_contents($targets['manifest'], $this->manifest($slug));
        file_put_contents($targets['assets'] . '/LICENSE.txt', $this->licenseText($slug, $sourceDir));

        // 4. report
        $this->analyse($html, $slug, $copied, $external, (float) $this->option('max-mb'));

        return self::SUCCESS;
    }

    private function stop(string $message): int
    {
        $this->error($message);

        return self::FAILURE;
    }

    /* ------------------------------------------------------------------ paths */

    private function isLocal(string $ref): bool
    {
        $ref = trim($ref);

        return $ref !== ''
            && ! preg_match('~^(?:[a-z][a-z0-9+.-]*:|//|#|\{\{|<\?)~i', $ref);
    }

    /** Path of a local reference relative to the template root, or null when it is not a file. */
    private function resolve(string $ref, string $fromDir, string $rootDir): ?string
    {
        $clean = preg_replace('~[?#].*$~', '', $ref);
        $full = $this->normalize($fromDir . '/' . $clean);

        if (! is_file($full) || ! str_starts_with($full, $this->normalize($rootDir) . '/')) {
            return null;
        }

        return substr($full, strlen($this->normalize($rootDir)) + 1);
    }

    private function normalize(string $path): string
    {
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($parts);

                continue;
            }
            $parts[] = $segment;
        }

        $prefix = preg_match('~^[A-Za-z]:~', $path) ? '' : (str_starts_with($path, '/') ? '/' : '');

        return $prefix . implode('/', $parts);
    }

    /* ---------------------------------------------------------------- rewrite */

    /**
     * Rewrites local references to `{{ $asset('…') }}`, collecting what is used.
     *
     * @param  array<string, true>  $used
     * @param  array<int, string>  $external
     */
    private function rewrite(string $html, string $baseDir, string $rootDir, array &$used, array &$external): string
    {
        // Scripts that must not ship: maps with keys, analytics, inline document.write.
        $html = preg_replace_callback('~<script\b[^>]*>.*?</script>\s*~is', function (array $m) use (&$external) {
            $tag = $m[0];
            if (preg_match('~maps\.googleapis|google-map|googletagmanager|google-analytics|gtag\(|fbq\(|yandex\.ru/metrika~i', $tag)) {
                $this->report[] = ['removed', 'script: ' . Str::limit(trim(strip_tags($tag)) ?: $tag, 70)];

                return '';
            }

            return $tag;
        }, $html) ?? $html;

        // <a href="other-page.html"> leads nowhere on a single landing page.
        $html = preg_replace_callback('~(<a\b[^>]*?\bhref=)(["\'])([^"\']+\.html?)(?:#[^"\']*)?\2~i', function (array $m) {
            return $this->isLocal($m[3]) ? $m[1] . $m[2] . '#' . $m[2] : $m[0];
        }, $html) ?? $html;

        $html = preg_replace_callback('~\b(href|src|data-src|data-bg|poster)=(["\'])([^"\']*)\2~i', function (array $m) use ($baseDir, $rootDir, &$used, &$external) {
            [$all, $attr, $q, $ref] = $m;

            if (! $this->isLocal($ref)) {
                if (preg_match('~^(?:https?:)?//~i', $ref) && ! str_contains($ref, 'fonts.googleapis.com')) {
                    $external[] = $ref;
                }

                return $all;
            }

            $rel = $this->resolve($ref, $baseDir, $rootDir);
            if ($rel === null) {
                return $all;
            }

            $used[$rel] = true;

            return $attr . '=' . $q . "{{ \$asset('" . $rel . "') }}" . $q;
        }, $html) ?? $html;

        // background-image: url(...) in inline styles and <style> blocks
        $html = preg_replace_callback('~url\((["\']?)([^)"\']+)\1\)~i', function (array $m) use ($baseDir, $rootDir, &$used) {
            if (! $this->isLocal($m[2])) {
                return $m[0];
            }
            $rel = $this->resolve($m[2], $baseDir, $rootDir);
            if ($rel === null) {
                return $m[0];
            }
            $used[$rel] = true;

            return 'url(' . $m[1] . "{{ \$asset('" . $rel . "') }}" . $m[1] . ')';
        }, $html) ?? $html;

        return $html;
    }

    /** Stylesheets pull in fonts and images through url(); copy those too. */
    private function followStylesheets(array &$used, string $rootDir, string $baseDir): void
    {
        $queue = array_keys(array_filter($used, fn ($v, $k) => str_ends_with(strtolower($k), '.css'), ARRAY_FILTER_USE_BOTH));
        $seen = [];

        while ($queue) {
            $css = array_shift($queue);
            if (isset($seen[$css])) {
                continue;
            }
            $seen[$css] = true;
            $text = (string) @file_get_contents($rootDir . '/' . $css);
            $dir = dirname($rootDir . '/' . $css);

            preg_match_all('~url\((["\']?)([^)"\']+)\1\)|@import\s+(["\'])([^"\']+)\3~i', $text, $found, PREG_SET_ORDER);
            foreach ($found as $hit) {
                $ref = ($hit[2] ?? '') !== '' ? $hit[2] : ($hit[4] ?? '');
                if (! $this->isLocal($ref)) {
                    continue;
                }
                $rel = $this->resolve($ref, $dir, $rootDir);
                if ($rel === null) {
                    continue;
                }
                if ($this->isLegacyFont($rel, $rootDir)) {
                    continue;
                }
                $used[$rel] = true;
                if (str_ends_with(strtolower($rel), '.css')) {
                    $queue[] = $rel;
                }
            }
        }
    }

    /** An .eot/.svg font file next to a woff/woff2/ttf of the same name: an Internet Explorer fallback. */
    private function isLegacyFont(string $rel, string $rootDir): bool
    {
        $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
        if (! in_array($ext, ['eot', 'svg'], true) || ! preg_match('~font|icon~i', $rel)) {
            return false;
        }

        $base = $rootDir . '/' . preg_replace('~\.[^.]+$~', '', $rel);
        foreach (['woff2', 'woff', 'ttf', 'otf'] as $modern) {
            if (is_file($base . '.' . $modern) || is_file($base . '.' . ucfirst($modern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, true>  $used
     * @return array<string, int> copied relative path => bytes
     */
    private function copyAssets(array $used, string $rootDir, string $targetDir): array
    {
        File::ensureDirectoryExists($targetDir);
        $copied = [];

        foreach (array_keys($used) as $rel) {
            $from = $rootDir . '/' . $rel;
            $to = $targetDir . '/' . $rel;
            File::ensureDirectoryExists(dirname($to));

            $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && function_exists('imagecreatefromstring') && $this->scaleImage($from, $to, $ext)) {
                $copied[$rel] = (int) filesize($to);

                continue;
            }

            copy($from, $to);
            $copied[$rel] = (int) filesize($to);
        }

        return $copied;
    }

    /** Photos are scaled to 1600px on the long side; smaller ones are copied untouched. */
    private function scaleImage(string $from, string $to, string $ext): bool
    {
        $info = @getimagesize($from);
        if (! $info || max($info[0], $info[1]) <= 1600 || filesize($from) < 200 * 1024) {
            return false;
        }

        $image = @imagecreatefromstring((string) file_get_contents($from));
        if (! $image) {
            return false;
        }

        $scaled = imagescale($image, (int) round($info[0] * 1600 / max($info[0], $info[1])), -1, IMG_BICUBIC);
        if (! $scaled) {
            return false;
        }

        match ($ext) {
            'png' => imagepng($scaled, $to, 7),
            'webp' => imagewebp($scaled, $to, 82),
            default => imagejpeg($scaled, $to, 82),
        };

        return true;
    }

    /* ---------------------------------------------------------------- blade */

    private function toBlade(string $html, string $slug, string $name): string
    {
        $source = (string) $this->option('source-url');
        $license = (string) $this->option('license');

        // <title>, csrf token, language, viewport
        $html = preg_replace('~<title>.*?</title>~is', '<title>{{ $landing->title }}</title>', $html, 1, $titleCount) ?? $html;
        $head = "    <meta name=\"csrf-token\" content=\"{{ csrf_token() }}\">\n";
        if (! preg_match('~name=["\']viewport["\']~i', $html)) {
            $head .= "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
        }
        $html = preg_replace('~</head>~i', $head . '</head>', $html, 1) ?? $html;
        if (! $titleCount) {
            $html = preg_replace('~<head([^>]*)>~i', "<head$1>\n    <title>{{ \$landing->title }}</title>", $html, 1) ?? $html;
        }
        $html = preg_replace('~<html\b[^>]*>~i', '<html lang="{{ str_replace(\'_\', \'-\', app()->getLocale()) }}">', $html, 1) ?? $html;

        // Blade would treat these as its own syntax inside the copied page.
        $html = str_replace(['@php', '@endphp', '@include'], ['@@php', '@@endphp', '@@include'], $html);

        $includes = "\n    @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker']])\n    @include('landings.partials.editor-assets')\n";
        $html = preg_replace('~</body>~i', $includes . '</body>', $html, 1, $bodyCount) ?? $html;
        if (! $bodyCount) {
            $html .= $includes;
        }

        $header = <<<BLADE
{{--
    {$name}: source {$source}, license {$license}.
    Imported by landing:import-template; still to do by hand (see the import report):
    map sections to data (\$cards, \$priced, \$hours, \$faqItems …), replace texts with
    <x-landing.text>, big photos with <x-landing.image>/<x-landing.bg>, the form with the
    booking widget markup (ids: request-form, request-submit, request-message, request-service,
    request-picker; phone input gets data-phone-mask), keep the author's credit link.
--}}
@php
    extract(app(\\App\\Services\\Landing\\PageData::class)->for(\$landing, \$featuredServices));

    \$asset = fn (string \$path) => asset('landing-templates/{$slug}/' . \$path);
@endphp

BLADE;

        return $header . ltrim($html);
    }

    private function manifest(string $slug): string
    {
        $name = var_export((string) $this->option('name') ?: Str::headline($slug), true);
        $description = var_export((string) $this->option('description') ?: 'Full-page template with photos, services and a booking form.', true);
        $source = var_export((string) $this->option('source-url'), true);
        $license = var_export((string) $this->option('license'), true);
        $attribution = var_export((string) $this->option('attribution') ?: null, true);
        $categories = $this->list((string) $this->option('categories'), TemplateRegistry::CATEGORIES);
        $purposes = $this->list((string) $this->option('purposes'), TemplateRegistry::PURPOSES) ?: "'booking'";

        return <<<PHP
<?php

/**
 * Imported by landing:import-template. See resources/landing-templates/salone.php for the manifest contract.
 */
\$view = 'landings.full.{$slug}';

return [
    'slug' => '{$slug}',
    'name' => {$name},
    'description' => {$description},
    'full_page' => true,
    'thumb' => 'landing-templates/{$slug}/preview.jpg', // scripts/landing-thumbs.mjs makes it
    'purposes' => [{$purposes}],
    'source' => {$source},
    'license' => {$license},
    'attribution' => {$attribution},
    'categories' => [{$categories}],
    'templates' => [
        'general' => \$view,
        'promotion' => \$view,
        'service' => \$view,
        'seasonal' => \$view,
        'consultation' => \$view,
    ],
    'images' => [
        // 'hero_image_1' => 'landing-templates/{$slug}/images/hero.jpg',
    ],
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text',
    ],
];

PHP;
    }

    /** @param  string[]  $allowed */
    private function list(string $csv, array $allowed): string
    {
        $items = array_values(array_intersect(array_map('trim', explode(',', $csv)), $allowed));

        return implode(', ', array_map(fn ($i) => "'{$i}'", $items));
    }

    private function licenseText(string $slug, string $sourceDir): string
    {
        $text = 'Template: ' . ((string) $this->option('name') ?: Str::headline($slug)) . "\n"
            . 'Source: ' . $this->option('source-url') . "\n"
            . 'License: ' . $this->option('license') . "\n";

        if ($this->option('attribution')) {
            $text .= 'Credit link that must stay in the page: ' . $this->option('attribution') . "\n";
        }

        foreach (['LICENSE', 'LICENSE.txt', 'license.txt', 'READ-ME.txt', 'README.txt'] as $file) {
            if (is_file($sourceDir . '/' . $file)) {
                $text .= "\n--- original {$file} ---\n" . file_get_contents($sourceDir . '/' . $file);
            }
        }

        return $text . "\nChanges for Veloria CRM: rebuilt as a Blade view showing the master's own data; unused files and sections removed.\n";
    }

    /* --------------------------------------------------------------- report */

    private function analyse(string $html, string $slug, array $copied, array $external, float $maxMb): void
    {
        $totalMb = array_sum($copied) / 1048576;
        $this->info("Imported '{$slug}': " . count($copied) . ' asset files, ' . number_format($totalMb, 2) . ' MB.');
        if ($totalMb > $maxMb) {
            $this->warn("  Assets weigh {$totalMb} MB (limit {$maxMb}); drop unused images and font formats.");
        }

        foreach ($this->report as [$kind, $what]) {
            $this->line("  removed {$what}");
        }

        // fonts without Cyrillic
        if (preg_match_all('~fonts\.googleapis\.com/css2?\?[^"\']*family=([^"\'&]+)~i', $html, $fonts)) {
            foreach ($fonts[1] as $family) {
                $name = strtolower(trim(urldecode(explode(':', str_replace('+', ' ', $family))[0])));
                if (! in_array($name, self::CYRILLIC_FONTS, true)) {
                    $this->warn("  Font '{$name}' may have no Cyrillic: check it and swap for Montserrat/Manrope/Playfair.");
                }
            }
        }

        // blocks that need a decision
        // The first place each kind of block shows up, with what to do with it.
        $reported = [];
        foreach (preg_split('~\R~', $html) ?: [] as $i => $line) {
            if (! preg_match('~<(?:section|div|footer|article|aside)\b[^>]*\b(?:id|class)=["\']([^"\']+)["\']~i', $line, $m)) {
                continue;
            }
            foreach (self::BLOCKS as $pattern => $advice) {
                if (! isset($reported[$pattern]) && preg_match('~(?:^|[\s_-])(?:' . $pattern . ')~i', $m[1])) {
                    $reported[$pattern] = true;
                    $this->line('  line ' . ($i + 1) . " [{$m[1]}] -> {$advice}");
                    break;
                }
            }
        }

        arsort($copied);
        $heaviest = array_slice($copied, 0, 4, true);
        $this->line('  Heaviest: ' . implode(', ', array_map(fn ($k, $v) => $k . ' (' . number_format($v / 1024) . ' KB)', array_keys($heaviest), $heaviest)));

        if (preg_match_all('~<form\b~i', $html, $forms)) {
            $this->warn('  ' . count($forms[0]) . ' <form> found: keep one, name the fields client_name/client_phone/service_id/message, use the request-* ids.');
        }
        // A plugin the page calls in its own script but that we do not load (or must not) stops that
        // script with a TypeError, and everything after the call (carousels, menus) silently dies.
        foreach (array_keys($copied) as $rel) {
            if (! str_ends_with($rel, '.js') || preg_match('~\.min\.js$|/lib/|jquery|bootstrap~i', $rel)) {
                continue;
            }
            $code = (string) @file_get_contents(public_path("landing-templates/{$slug}/{$rel}"));
            if (preg_match_all('~\.(datetimepicker|datepicker|timepicker|counterUp|animateNumber|magnificPopup|stellar|isotope|lightbox|validate|jqBootstrapValidation)\s*\(~', $code, $calls)) {
                $this->warn("  {$rel} calls " . implode(', ', array_unique($calls[1])) . ': load the plugin or delete the call, otherwise the rest of the script stops.');
            }
        }

        if (preg_match('~lorem ipsum|far far away|clita erat|dolor sit amet~i', $html)) {
            $this->warn('  Placeholder text found: replace it with data or lang strings.');
        }
        if ($external) {
            $hosts = collect($external)->map(fn ($u) => parse_url(str_starts_with($u, '//') ? 'https:' . $u : $u, PHP_URL_HOST))->filter()->unique()->values()->all();
            $this->line('  External hosts still used (CDN is fine, keys are not): ' . implode(', ', $hosts));
        }

        $this->newLine();
        $this->line('Next: finish resources/views/landings/full/' . $slug . '.blade.php, then');
        $this->line("  node scripts/landing-thumbs.mjs {$slug}   and   php artisan test --filter=LandingEditorTest");
    }
}
