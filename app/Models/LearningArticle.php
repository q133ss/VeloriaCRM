<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningArticle extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;

    protected $fillable = [
        'slug',
        'title',
        'summary',
        'reading_time_minutes',
        'topic',
        'content',
        'action',
        'is_published',
        'is_featured',
        'sort_order',
        'source_url',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'summary' => 'array',
            'content' => 'array',
            'action' => 'array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }
}
