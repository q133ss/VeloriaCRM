<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningLesson extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;

    protected $fillable = [
        'learning_category_id',
        'slug',
        'title',
        'summary',
        'duration_minutes',
        'format',
        'content',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'summary' => 'array',
            'content' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class, 'learning_category_id');
    }
}
