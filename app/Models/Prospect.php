<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An account built in advance for a master from cold outreach: services,
 * schedule and a booking page assembled from her public group, waiting for
 * her to take it over through the claim link.
 */
class Prospect extends Model
{
    public const STATUS_BUILT = 'built';
    public const STATUS_SENT = 'sent';
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_EXPIRED = 'expired';

    /** Technical mailboxes of unclaimed accounts; nothing is ever delivered there. */
    public const EMAIL_DOMAIN = 'prospect.veloriapro.ru';

    protected $fillable = [
        'user_id',
        'landing_id',
        'created_by',
        'channel',
        'external_id',
        'url',
        'title',
        'booking_service',
        'status',
        'services_source',
        'password',
        'claim_token',
        'source',
        'sent_at',
        'claimed_at',
    ];

    protected $hidden = [
        'password',
        'claim_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'source' => 'array',
            'sent_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function landing(): BelongsTo
    {
        return $this->belongsTo(Landing::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(ProspectVisit::class);
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_BUILT, self::STATUS_SENT], true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_BUILT, self::STATUS_SENT]);
    }

    /** Whether this user is a prospect account nobody has taken over yet. */
    public static function isPendingUser(?int $userId): bool
    {
        return $userId !== null && static::query()->pending()->where('user_id', $userId)->exists();
    }
}
