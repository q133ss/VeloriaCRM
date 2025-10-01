<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningTemplate extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;

    public const TYPE_TEXT = 'text';
    public const TYPE_VOICE = 'voice';
    public const TYPE_STORY = 'story';
    public const TYPE_CHECKLIST = 'checklist';

    protected $fillable = [
        'slug',
        'type',
        'title',
        'description',
        'content',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'content' => 'array',
        ];
    }

    public function getLocalizedContent(string $locale, ?string $fallback = null): mixed
    {
        $content = $this->content;

        if (!is_array($content)) {
            return $content;
        }

        if (array_key_exists($locale, $content)) {
            return $content[$locale];
        }

        if ($fallback && array_key_exists($fallback, $content)) {
            return $content[$fallback];
        }

        if (!empty($content)) {
            return reset($content);
        }

        return null;
    }
}
