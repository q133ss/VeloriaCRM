<?php

namespace App\Console\Commands;

use App\Services\Landing\TemplateRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Scaffolds a full-page landing template that is already wired to the click
 * editor, the booking form and the manifest registry. Only the markup taken
 * from the source template is left to do. See docs/landing-templates.md.
 */
class MakeLandingTemplate extends Command
{
    protected $signature = 'landing:make-template
        {slug : Latin lowercase name, e.g. spa-soft}
        {--name= : Title shown in the wizard}
        {--description= : One line shown under the title}
        {--source= : Where the template comes from (URL), goes into the license header}
        {--license=CC BY 4.0 : License of the source template}
        {--force : Overwrite existing files}';

    protected $description = 'Create the manifest, Blade view and asset folder for a new full-page landing template';

    public function handle(TemplateRegistry $registry): int
    {
        $slug = Str::slug((string) $this->argument('slug'));

        if ($slug === '' || $slug !== $this->argument('slug')) {
            $this->error('Slug must be lowercase latin letters, digits and dashes.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: Str::headline($slug);
        $description = $this->option('description') ?: 'Full-page layout with photos, services and a booking form.';

        $files = [
            resource_path("landing-templates/{$slug}.php") => $this->manifest($slug, $name, $description),
            resource_path("views/landings/full/{$slug}.blade.php") => $this->view($slug, $name),
            public_path("landing-templates/{$slug}/README.md") => $this->readme($slug, $name),
        ];

        foreach ($files as $path => $contents) {
            if (file_exists($path) && ! $this->option('force')) {
                $this->error("Already exists: {$path} (use --force to overwrite)");

                return self::FAILURE;
            }
        }

        foreach ($files as $path => $contents) {
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0775, true);
            }

            file_put_contents($path, $contents);
            $this->line('  created ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path));
        }

        foreach (['css', 'js', 'img'] as $dir) {
            @mkdir(public_path("landing-templates/{$slug}/{$dir}"), 0775, true);
        }

        $this->newLine();
        $this->info("Template '{$slug}' created. Next:");
        $this->line("  1. Put css/js/img into public/landing-templates/{$slug}/ and keep the original LICENSE file there.");
        $this->line("  2. Move the sections into resources/views/landings/full/{$slug}.blade.php, replacing texts with <x-landing.text>, photos with <x-landing.image>.");
        $this->line("  3. List every key and photo you used in resources/landing-templates/{$slug}.php (fields, images).");
        $this->line('  4. Add a preview.jpg (480x316) and set "thumb" in the manifest.');
        $this->line('  5. php artisan test --filter=LandingEditorTest  (the smoke test picks the new template up by itself)');

        $registry->layouts();

        return self::SUCCESS;
    }

    private function manifest(string $slug, string $name, string $description): string
    {
        $name = var_export($name, true);
        $description = var_export($description, true);

        return <<<PHP
<?php

/**
 * See resources/landing-templates/salone.php for the manifest contract.
 * Source: {$this->option('source')} ({$this->option('license')})
 */
\$view = 'landings.full.{$slug}';

return [
    'slug' => '{$slug}',
    'name' => {$name},
    'description' => {$description},
    'full_page' => true,
    'thumb' => null, // 'landing-templates/{$slug}/preview.jpg'
    'templates' => [
        'general' => \$view,
        'promotion' => \$view,
        'service' => \$view,
        'seasonal' => \$view,
        'consultation' => \$view,
    ],
    // Stock photo per slot; the owner can replace each one in the click editor.
    'images' => [
        // 'hero_image_1' => 'landing-templates/{$slug}/img/hero-1.jpg',
    ],
    // Keys the click editor may change on this template (kinds and limits are in
    // App\\Services\\Landing\\LandingContent::FIELDS).
    'fields' => [
        'title', 'hero_title', 'hero_text', 'cta_label', 'booking_hint',
        'phone', 'address', 'proof_items_text',
    ],
];

PHP;
    }

    private function view(string $slug, string $name): string
    {
        $source = $this->option('source') ?: 'unknown';
        $license = $this->option('license');

        $template = <<<'BLADE'
{{--
    __NAME__: source __SOURCE__, license __LICENSE__.
    Keep the author's credit link if the license asks for it (CC BY does).

    Changes from the source: data instead of placeholder text, the booking form,
    no invented people or figures. Editable spots use <x-landing.text> and
    <x-landing.image>; they render plain markup for visitors.
--}}
@php
    $settings = $landing->settings ?? [];
    $content = app(\App\Services\Landing\LandingContent::class);
    $editing = $content->editing();
    $phone = trim((string) ($settings['phone'] ?? ''));
    $phoneHref = $phone !== '' ? 'tel:' . preg_replace('/[^0-9+]+/', '', $phone) : null;
    $services = $featuredServices->values();
    $proofItems = collect($content->items('proof_items_text'));
    $asset = fn (string $path) => asset('landing-templates/__SLUG__/' . $path);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $landing->title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Fonts must cover Cyrillic. Add the template's own CSS here: <link href="{{ $asset('css/style.css') }}" rel="stylesheet"> --}}
</head>
<body>
    @if($isPreview && empty($isEdit))
        <div>{{ __('landings.public.preview_badge') }}</div>
    @endif

    <header>
        <x-landing.text key="title" tag="strong" />
    </header>

    <section id="top">
        <x-landing.text key="hero_title" tag="h1" :default="$landing->title" />
        @if($editing || filled($content->text('hero_text')))
            <x-landing.text key="hero_text" tag="p" />
        @endif
        <a href="#booking"><x-landing.text key="cta_label" :default="__('landings.salone.book')" /></a>
    </section>

    @if($proofItems->isNotEmpty())
        <section id="about">
            @foreach($proofItems->take(3) as $item)
                <x-landing.text key="proof_items_text" :index="$loop->index" tag="p" />
            @endforeach
        </section>
    @endif

    <section id="services">
        @foreach($services as $service)
            <article>
                <h3>{{ $service->name }}</h3>
                @if($service->base_price)<span>{{ __('landings.salone.from_price', ['price' => number_format((float) $service->base_price, 0, ',', ' ')]) }}</span>@endif
                @if($service->duration_min)<span>{{ __('landings.salone.minutes', ['n' => (int) $service->duration_min]) }}</span>@endif
                <a href="#booking" data-pick-service="{{ $service->id }}">{{ __('landings.salone.book') }}</a>
            </article>
        @endforeach
    </section>

    <section id="booking">
        <form id="request-form" novalidate>
            <input type="text" name="client_name" placeholder="{{ __('landings.salone.field_name') }}" required maxlength="120">
            <input type="tel" name="client_phone" placeholder="{{ __('landings.salone.field_phone') }}" required maxlength="32" autocomplete="tel" data-phone-mask>
            <select name="service_id" id="request-service">
                <option value="">{{ __('landings.salone.field_service_any') }}</option>
                @foreach($services as $service)
                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                @endforeach
            </select>
            <div id="request-picker"></div>
            <textarea name="message" rows="3" placeholder="{{ __('landings.salone.field_message') }}" maxlength="1000"></textarea>
            <button type="submit" id="request-submit">{{ __('landings.salone.submit') }}</button>
            <div id="request-message" role="status" aria-live="polite"></div>
        </form>
    </section>

    <footer>
        @if($phoneHref || $editing)
            <a href="{{ $phoneHref ?: '#' }}"><x-landing.text key="phone" /></a>
        @endif
        @if(filled($settings['address'] ?? null) || $editing)
            <x-landing.text key="address" tag="p" />
        @endif
        {{-- Credit link required by the source license goes here. --}}
    </footer>

    @include('landings.partials.request-script', ['ids' => ['form' => 'request-form', 'button' => 'request-submit', 'message' => 'request-message', 'service' => 'request-service', 'picker' => 'request-picker']])
    @include('landings.partials.editor-assets')
</body>
</html>
BLADE;

        return strtr($template, [
            '__NAME__' => $name,
            '__SLUG__' => $slug,
            '__SOURCE__' => $source,
            '__LICENSE__' => $license,
        ]);
    }

    private function readme(string $slug, string $name): string
    {
        return "# {$name}\n\nAssets of the `{$slug}` landing template.\n\nSource: " . ($this->option('source') ?: 'unknown')
            . "\nLicense: " . $this->option('license')
            . "\n\nKeep the original LICENSE file next to the assets.\n";
    }
}
