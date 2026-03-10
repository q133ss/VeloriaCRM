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
}
