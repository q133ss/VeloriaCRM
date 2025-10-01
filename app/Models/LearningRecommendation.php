<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningRecommendation extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'impact_text',
        'action',
        'confidence',
        'priority',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'impact_text' => 'array',
            'action' => 'array',
            'meta' => 'array',
            'confidence' => 'float',
        ];
    }

    public function scopeForUser(Builder $query, ?User $user): Builder
    {
        if (!$user) {
            return $query->whereNull('user_id');
        }

        return $query->where(function (Builder $subQuery) use ($user) {
            $subQuery->whereNull('user_id')->orWhere('user_id', $user->id);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
