<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Client extends Authenticatable
{
    public const LOYALTY_LEVELS = [
        'new' => 'Новый клиент',
        'bronze' => 'Был один раз',
        'silver' => 'Возвращается',
        'gold' => 'Постоянный клиент',
        'platinum' => 'Очень постоянный',
        'vip' => 'Любимый клиент',
        'ambassador' => 'Рекомендует вас',
    ];

    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'user_id',
        'client_user_id',
        'name',
        'phone',
        'email',
        'birthday',
        'tags',
        'allergies',
        'preferences',
        'notes',
        'last_visit_at',
        'loyalty_level',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'tags' => 'array',
            'allergies' => 'array',
            'preferences' => 'array',
            'last_visit_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function loyaltyLevels(): array
    {
        return self::LOYALTY_LEVELS;
    }

    /**
     * The level the bookings already imply.
     *
     * `loyalty_level` is a field on the client form, so unless the master filled
     * it in by hand it says nothing — and nobody fills it in. Counting visits
     * gives the same answer without asking. A value the master did set stays
     * hers: `vip` and `ambassador` are judgements about a person, not a tally,
     * and no arithmetic should be allowed to take them away.
     */
    public static function loyaltyFromVisits(int $visits): string
    {
        return match (true) {
            $visits >= 10 => 'platinum',
            $visits >= 5 => 'gold',
            $visits >= 2 => 'silver',
            $visits === 1 => 'bronze',
            default => 'new',
        };
    }

    public static function loyaltyLabel(?string $level): ?string
    {
        if (! $level) {
            return null;
        }

        return self::LOYALTY_LEVELS[$level] ?? ucfirst($level);
    }
}
