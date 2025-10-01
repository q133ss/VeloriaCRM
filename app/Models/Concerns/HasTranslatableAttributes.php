<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasTranslatableAttributes
{
    public function getTranslation(string $attribute, string $locale, ?string $fallback = null): mixed
    {
        $value = $this->getAttribute($attribute);

        if (!is_array($value)) {
            return $value;
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            if ($item === null || $item === '') {
                continue;
            }
            $normalized[Str::lower((string) $key)] = $item;
        }

        $locale = Str::lower($locale);
        $fallback = $fallback ? Str::lower($fallback) : null;

        if (array_key_exists($locale, $normalized)) {
            return $normalized[$locale];
        }

        if ($fallback && array_key_exists($fallback, $normalized)) {
            return $normalized[$fallback];
        }

        if (!empty($normalized)) {
            return reset($normalized);
        }

        return null;
    }

    public function getTranslationAsString(string $attribute, string $locale, ?string $fallback = null): string
    {
        $value = $this->getTranslation($attribute, $locale, $fallback);

        return is_string($value) ? $value : '';
    }
}
