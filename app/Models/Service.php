<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'base_price',
        'cost',
        'duration_min',
        'upsell_suggestions',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'float',
            'cost' => 'float',
            'duration_min' => 'integer',
            'upsell_suggestions' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where('name', 'like', "%{$search}%");
        }

        if (array_key_exists('category_id', $filters) && $filters['category_id'] !== null && $filters['category_id'] !== '') {
            $query->where('category_id', $filters['category_id']);
        }

        if (array_key_exists('price_min', $filters) && $filters['price_min'] !== null && $filters['price_min'] !== '') {
            $query->where('base_price', '>=', $filters['price_min']);
        }

        if (array_key_exists('price_max', $filters) && $filters['price_max'] !== null && $filters['price_max'] !== '') {
            $query->where('base_price', '<=', $filters['price_max']);
        }

        if (array_key_exists('duration_min', $filters) && $filters['duration_min'] !== null && $filters['duration_min'] !== '') {
            $query->where('duration_min', '>=', $filters['duration_min']);
        }

        if (array_key_exists('duration_max', $filters) && $filters['duration_max'] !== null && $filters['duration_max'] !== '') {
            $query->where('duration_min', '<=', $filters['duration_max']);
        }

        return $query;
    }

    public function getMarginAttribute(): ?float
    {
        if ($this->base_price === null || $this->cost === null) {
            return null;
        }

        return (float) $this->base_price - (float) $this->cost;
    }
}
