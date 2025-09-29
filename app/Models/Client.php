<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    public const LOYALTY_LEVELS = [
        'new' => 'Новый клиент',
        'bronze' => 'Бронза',
        'silver' => 'Серебро',
        'gold' => 'Золото',
        'platinum' => 'Платина',
        'vip' => 'VIP',
        'ambassador' => 'Ambassador',
    ];

    use HasFactory;

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
