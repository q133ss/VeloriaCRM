<?php

namespace App\Services\Landing;

use App\Models\Landing;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * The single place that knows what on a landing can be edited, where each value
 * lives and how it is validated. Templates read through it (Blade components
 * under components/landing), the click editor writes through it, so a new
 * template only lists keys in its manifest.
 *
 * Kinds: text (one line), textarea, phone, list (one item per line), image.
 * "virtual" keys resolve to a different settings key by landing type, because
 * the same hero heading is `headline` for a promotion and the salon name for a
 * general page.
 */
class LandingContent
{
    public const FIELDS = [
        'title' => ['kind' => 'text', 'max' => 255, 'required' => true],
        'hero_title' => ['kind' => 'text', 'max' => 255, 'required' => true],
        'hero_text' => ['kind' => 'textarea', 'max' => 1000],
        'subtitle' => ['kind' => 'textarea', 'max' => 500],
        'cta_label' => ['kind' => 'text', 'max' => 120],
        'secondary_cta_label' => ['kind' => 'text', 'max' => 120],
        'booking_hint' => ['kind' => 'text', 'max' => 255],
        'bonus_text' => ['kind' => 'text', 'max' => 255],
        'season_label' => ['kind' => 'text', 'max' => 120],
        'lead_magnet' => ['kind' => 'text', 'max' => 255],
        'phone' => ['kind' => 'phone', 'max' => 32],
        'address' => ['kind' => 'text', 'max' => 255],
        'proof_items_text' => ['kind' => 'list', 'max' => 200, 'items' => 20],
        'faq_items_text' => ['kind' => 'list', 'max' => 400, 'items' => 20],
        'benefit_items_text' => ['kind' => 'list', 'max' => 200, 'items' => 20],
        'hero_image_1' => ['kind' => 'image'],
        'hero_image_2' => ['kind' => 'image'],
        'hero_image_3' => ['kind' => 'image'],
        'about_image' => ['kind' => 'image'],
        'master_role' => ['kind' => 'text', 'max' => 120],
        'master_bio' => ['kind' => 'textarea', 'max' => 500],
        'master_photo' => ['kind' => 'image'],
        'work_image_1' => ['kind' => 'image'],
        'work_image_2' => ['kind' => 'image'],
        'work_image_3' => ['kind' => 'image'],
    ];

    private ?Landing $landing = null;

    private bool $editing = false;

    public function __construct(private readonly TemplateRegistry $registry)
    {
    }

    public function bind(Landing $landing, bool $editing = false): static
    {
        $this->landing = $landing;
        $this->editing = $editing;

        return $this;
    }

    public function editing(): bool
    {
        return $this->editing;
    }

    public function landing(): ?Landing
    {
        return $this->landing;
    }

    /* ------------------------------------------------------------ reading */

    public function text(string $key, ?string $default = null, bool $titleFallback = true): string
    {
        $value = $this->raw($key, $titleFallback);

        return filled($value) ? (string) $value : (string) $default;
    }

    /** @return string[] */
    public function items(string $key, array $default = []): array
    {
        $items = $this->lines($this->raw($key));

        return $items ?: array_values($default);
    }

    public function imageUrl(string $key, string $defaultAsset): string
    {
        $path = data_get($this->landing?->settings, 'images.' . $key);

        if (is_string($path) && $path !== '' && Storage::disk('landing_media')->exists($path)) {
            return Storage::disk('landing_media')->url($path);
        }

        return asset($defaultAsset);
    }

    /** data-* attributes for an editable element, empty outside edit mode. */
    public function attrs(string $key, ?int $index = null): array
    {
        if (! $this->editing || ! isset(self::FIELDS[$key])) {
            return [];
        }

        $kind = self::FIELDS[$key]['kind'];

        return array_filter([
            'data-lf-key' => $key,
            'data-lf-kind' => $kind,
            'data-lf-max' => self::FIELDS[$key]['max'] ?? null,
            'data-lf-index' => $index,
            'data-lf-placeholder' => $kind === 'image' ? null : __('landings.editor.ui.placeholder'),
            'data-lf-custom' => $kind === 'image' && filled(data_get($this->landing?->settings, 'images.' . $key)) ? '1' : null,
        ], fn ($v) => $v !== null);
    }

    /** Image keys of the landing's template with the stock photo each one falls back to. */
    public function imageDefaults(Landing $landing): array
    {
        $layout = $this->registry->layoutForTemplate($landing->landing);
        $keys = array_filter($layout['fields'] ?? [], fn ($k) => (self::FIELDS[$k]['kind'] ?? null) === 'image');

        return array_intersect_key($layout['images'] ?? [], array_flip($keys));
    }

    /** Keys the landing's current template lets the editor change. */
    public function allowedKeys(Landing $landing): array
    {
        $layout = $this->registry->layoutForTemplate($landing->landing);
        $keys = $layout['fields'] ?? [];

        return array_values(array_filter($keys, fn ($k) => isset(self::FIELDS[$k])));
    }

    /* ------------------------------------------------------------ writing */

    /**
     * Validates every change first and saves once, so a half-applied edit never
     * lands. Settings are merged, never replaced.
     *
     * @param  array<int, array{key: string, value: mixed}>  $changes
     * @return array<string, string> normalized values by key
     */
    public function apply(Landing $landing, array $changes): array
    {
        $allowed = $this->allowedKeys($landing);
        $this->landing = $landing;

        $errors = [];
        $clean = [];

        foreach ($changes as $i => $change) {
            $key = (string) ($change['key'] ?? '');
            $field = self::FIELDS[$key] ?? null;

            if (! $field || ! in_array($key, $allowed, true) || $field['kind'] === 'image') {
                $errors["changes.$i.key"] = __('landings.editor.errors.unknown_key');

                continue;
            }

            $result = $this->normalize($field, $change['value'] ?? '');

            if ($result['error']) {
                $errors["changes.$i.value"] = $result['error'];

                continue;
            }

            $clean[$key] = $result['value'];
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $settings = $landing->settings ?? [];
        $edited = $settings['_edited'] ?? [];

        foreach ($clean as $key => $value) {
            if ($key === 'title') {
                $landing->title = $value;

                continue;
            }

            $target = $this->writeKey($key, $landing);

            if ($value === '') {
                unset($settings[$target]);
            } else {
                $settings[$target] = $value;
            }

            $edited[] = $target;
        }

        $settings['_edited'] = array_values(array_unique($edited));
        $landing->settings = $settings;
        $landing->save();

        return $clean;
    }

    public function setImage(Landing $landing, string $key, ?string $path): void
    {
        $settings = $landing->settings ?? [];

        if ($path === null) {
            unset($settings['images'][$key]);
            if (empty($settings['images'])) {
                unset($settings['images']);
            }
        } else {
            $settings['images'][$key] = $path;
        }

        $landing->settings = $settings;
        $landing->save();
    }

    /**
     * @param  array{kind: string, max?: int, items?: int, required?: bool}  $field
     * @return array{value: string, error: ?string}
     */
    public function normalize(array $field, mixed $value): array
    {
        if (! is_scalar($value) && $value !== null) {
            return ['value' => '', 'error' => __('landings.editor.errors.invalid')];
        }

        $value = preg_replace('/[^\P{Cc}\n\t]/u', '', str_replace(["\r\n", "\r"], "\n", (string) $value)) ?? '';
        $kind = $field['kind'];
        $max = $field['max'] ?? 255;

        if ($kind === 'text' || $kind === 'phone') {
            $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        } elseif ($kind === 'textarea') {
            $value = trim(preg_replace("/\n{3,}/", "\n\n", $value) ?? '');
        } elseif ($kind === 'list') {
            $lines = array_map(fn ($l) => trim(preg_replace('/\s+/u', ' ', $l) ?? ''), explode("\n", $value));
            $lines = array_values(array_filter($lines, fn ($l) => $l !== ''));

            if (count($lines) > ($field['items'] ?? 20)) {
                return ['value' => '', 'error' => __('landings.editor.errors.too_many')];
            }

            foreach ($lines as $line) {
                if (mb_strlen($line) > $max) {
                    return ['value' => '', 'error' => __('landings.editor.errors.too_long', ['max' => $max])];
                }
            }

            $value = implode("\n", $lines);
        }

        if (($field['required'] ?? false) && $value === '') {
            return ['value' => '', 'error' => __('landings.editor.errors.required')];
        }

        if ($kind !== 'list' && mb_strlen($value) > $max) {
            return ['value' => '', 'error' => __('landings.editor.errors.too_long', ['max' => $max])];
        }

        if ($kind === 'phone' && $value !== '') {
            $digits = preg_replace('/\D+/', '', $value) ?? '';

            if (! preg_match('/^[0-9+()\-\s.]+$/', $value) || strlen($digits) < 7) {
                return ['value' => '', 'error' => __('landings.editor.errors.phone')];
            }
        }

        return ['value' => $value, 'error' => null];
    }

    /* ----------------------------------------------------------- internals */

    private function raw(string $key, bool $titleFallback = true): ?string
    {
        $landing = $this->landing;

        if (! $landing) {
            return null;
        }

        $settings = $landing->settings ?? [];

        return match ($key) {
            'title' => $landing->title,
            'hero_title' => $this->firstFilled([
                $settings['headline'] ?? null,
                $landing->type === 'service' ? ($settings['service_name'] ?? null) : null,
                $titleFallback ? $landing->title : null,
            ]),
            'hero_text' => $this->firstFilled([
                $settings[$this->writeKey('hero_text', $landing)] ?? null,
                $settings['subtitle'] ?? null,
            ]),
            default => isset($settings[$key]) && is_scalar($settings[$key]) ? (string) $settings[$key] : null,
        };
    }

    /**
     * Settings key a virtual key writes to. A layout can remap it in its manifest
     * ('keys' => ['hero_text' => ['general' => 'subtitle']]) when it shows the
     * value somewhere other than the default place.
     */
    private function writeKey(string $key, Landing $landing): string
    {
        $layout = $this->registry->layoutForTemplate($landing->landing);
        $mapped = $layout['keys'][$key][$landing->type] ?? $layout['keys'][$key]['*'] ?? null;

        if ($mapped) {
            return $mapped;
        }

        return match ($key) {
            'hero_title' => 'headline',
            'hero_text' => match ($landing->type) {
                'service' => 'service_description',
                'general' => 'greeting',
                default => 'description',
            },
            default => $key,
        };
    }

    private function firstFilled(array $values): ?string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    /** @return string[] */
    private function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text) ?: []), fn ($l) => $l !== ''));
    }
}
