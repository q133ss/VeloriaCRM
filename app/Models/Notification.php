<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    /**
     * Атрибуты, доступные для массового заполнения.
     */
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'is_read',
    ];

    /**
     * Приводим типы для удобной работы в сервисах и контроллерах.
     */
    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Модель не использует updated_at, поэтому отключаем его.
     */
    public const UPDATED_AT = null;

    /**
     * Связь уведомления с пользователем.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Скоуп для фильтрации непрочитанных уведомлений.
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }
}
