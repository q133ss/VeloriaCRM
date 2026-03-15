<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UsefulCategory extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'sort_order',
        'is_active',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(LearningArticle::class, 'useful_category_id');
    }
}
