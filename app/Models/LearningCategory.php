<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningCategory extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;

    protected $fillable = [
        'slug',
        'title',
        'description',
        'icon',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
        ];
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(LearningLesson::class)->orderBy('position');
    }
}
