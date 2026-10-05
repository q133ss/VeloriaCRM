<?php

namespace App\Services\Landing;

use Illuminate\Support\Collection;

/**
 * Finds landing layouts by their manifests in resources/landing-templates.
 * Adding a layout is adding a manifest; nothing else lists templates.
 */
class TemplateRegistry
{
    public const TYPES = ['general', 'promotion', 'service', 'seasonal', 'consultation'];

    public const DEFAULT_LAYOUT = 'salone';

    /** What a template is good for; the wizard shows a chip for each one in use. */
    public const CATEGORIES = ['nails', 'brows', 'hair', 'barber', 'spa', 'cosmetology', 'makeup'];

    /** What the page is for: booking, an offer, an advert, a showcase, a master's personal card, courses and workshops, a single service. */
    public const PURPOSES = ['booking', 'promo', 'ads', 'portfolio', 'card', 'course', 'single'];

    /** Licenses we accept for third-party templates. */
    public const LICENSES = ['CC-BY-3.0', 'CC-BY-4.0', 'free-commercial'];

    /** @var Collection<string, array>|null */
    private ?Collection $layouts = null;

    /**
     * @return Collection<string, array> layouts keyed by slug
     */
    public function layouts(): Collection
    {
        return $this->layouts ??= collect(glob(resource_path('landing-templates/*.php')) ?: [])
            ->map(fn (string $file) => $this->normalize(require $file))
            ->keyBy('slug')
            ->sortBy(fn (array $layout) => $layout['slug'] === self::DEFAULT_LAYOUT ? '' : $layout['slug']);
    }

    public function layout(string $slug): ?array
    {
        return $this->layouts()->get($slug);
    }

    /** Every view a landing may point at. */
    public function templateIds(): array
    {
        return $this->layouts()
            ->flatMap(fn (array $layout) => array_values($layout['templates']))
            ->unique()
            ->values()
            ->all();
    }

    public function layoutForTemplate(?string $template): ?array
    {
        if (! $template) {
            return null;
        }

        return $this->layouts()->first(fn (array $layout) => in_array($template, $layout['templates'], true));
    }

    /** The view to render for a stored value: itself when it is a known template, otherwise the default. */
    public function resolve(?string $template, string $type): string
    {
        return $template && in_array($template, $this->templateIds(), true)
            ? $template
            : $this->defaultTemplate($type);
    }

    public function templateFor(string $layoutSlug, string $type): string
    {
        $layout = $this->layout($layoutSlug) ?? $this->layout(self::DEFAULT_LAYOUT) ?? $this->layouts()->first();

        return $layout['templates'][$type] ?? $layout['templates']['general'];
    }

    public function defaultTemplate(string $type): string
    {
        return $this->templateFor(self::DEFAULT_LAYOUT, $type);
    }

    /**
     * Categories that at least one template belongs to, in a stable order, with their labels.
     *
     * @return array<int, array{slug: string, label: string}>
     */
    public function categoryChips(): array
    {
        $used = $this->layouts()->flatMap(fn (array $layout) => $layout['categories'])->unique()->all();

        return collect(self::CATEGORIES)
            ->filter(fn (string $slug) => in_array($slug, $used, true))
            ->map(fn (string $slug) => ['slug' => $slug, 'label' => __('landings.wizard.categories.' . $slug)])
            ->values()
            ->all();
    }

    /**
     * Purposes that at least one template serves, in a stable order, with their labels.
     *
     * @return array<int, array{slug: string, label: string}>
     */
    public function purposeChips(): array
    {
        $used = $this->layouts()->flatMap(fn (array $layout) => $layout['purposes'])->unique()->all();

        return collect(self::PURPOSES)
            ->filter(fn (string $slug) => in_array($slug, $used, true))
            ->map(fn (string $slug) => ['slug' => $slug, 'label' => __('landings.wizard.purposes.' . $slug)])
            ->values()
            ->all();
    }

    /** What the wizard shows in "page layout". */
    public function forWizard(): array
    {
        return $this->layouts()->map(fn (array $layout) => [
            'slug' => $layout['slug'],
            'title' => __($layout['name']),
            'description' => __($layout['description']),
            // filemtime keeps browsers from showing an old preview after it is replaced
            'categories' => $layout['categories'],
            'purposes' => $layout['purposes'],
            'demo_url' => route('landings.demo', ['layout' => $layout['slug']]),
            'thumb' => $layout['thumb'] ? asset($layout['thumb']) . '?v=' . (@filemtime(public_path($layout['thumb'])) ?: 1) : null,
            'templates' => $layout['templates'],
        ])->values()->all();
    }

    private function normalize(array $manifest): array
    {
        foreach (['slug', 'name', 'description', 'templates'] as $required) {
            if (empty($manifest[$required])) {
                throw new \InvalidArgumentException("Landing manifest is missing '{$required}'");
            }
        }

        $manifest['full_page'] = (bool) ($manifest['full_page'] ?? false);
        $manifest['thumb'] = $manifest['thumb'] ?? null;
        $manifest['fields'] = array_values($manifest['fields'] ?? []);
        $manifest['images'] = $manifest['images'] ?? [];
        $manifest['keys'] = $manifest['keys'] ?? [];
        $manifest['categories'] = array_values(array_intersect((array) ($manifest['categories'] ?? []), self::CATEGORIES));
        $manifest['purposes'] = array_values(array_intersect((array) ($manifest['purposes'] ?? []), self::PURPOSES)) ?: ['booking'];
        // Where the template came from and under what terms; `attribution` is the credit link the
        // license makes us keep in the page (checked by tests).
        $manifest['source'] = $manifest['source'] ?? null;
        $manifest['license'] = $manifest['license'] ?? null;
        $manifest['attribution'] = $manifest['attribution'] ?? null;

        return $manifest;
    }
}
