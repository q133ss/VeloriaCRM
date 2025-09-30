<?php

namespace App\Models;

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

    public function getMarginAttribute(): ?float
    {
        if ($this->base_price === null || $this->cost === null) {
            return null;
        }

        return (float) $this->base_price - (float) $this->cost;
    }
}
