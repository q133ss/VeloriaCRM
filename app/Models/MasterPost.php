<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A master's own editorial post ("news/promotions feed" in the client app) —
 * deliberately not `Landing` (a public web page) or `Promotion` (a discount
 * code). This is just short-form news a master's clients read in the app.
 */
class MasterPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'image_url',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Visible in the client app: published, and not scheduled for the future.
     */
    public function scopePublished($query)
    {
        $now = Carbon::now();

        return $query
            ->where('is_published', true)
            ->where(function ($builder) use ($now) {
                $builder
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', $now);
            });
    }
}
