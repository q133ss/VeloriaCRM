<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterMood extends Model
{
    use HasFactory;

    /**
     * Справочник опций настроения, используемый в уведомлении и на фронтенде.
     * Ключ — машинное значение, значение — отображаемая подпись.
     */
    public const MOOD_OPTIONS = [
        'tired' => 'Устал 😓',
        'ok' => 'Все хорошо 🙂',
        'great' => 'Отлично 😄',
    ];

    /**
     * Параметры, доступные для массового заполнения.
     */
    protected $fillable = [
        'user_id',
        'mood_date',
        'mood',
        'mood_label',
    ];

    /**
     * Приведение типов для удобной работы в аналитике.
     */
    protected $casts = [
        'mood_date' => 'date',
    ];

    /**
     * Связь настроения с мастером (пользователем).
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Возвращает подпись настроения по его ключу.
     */
    public static function labelFor(string $mood): string
    {
        return self::MOOD_OPTIONS[$mood] ?? $mood;
    }
}
