<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class SupportTicket extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_RESPONDED = 'responded';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'assigned_to',
        'subject',
        'status',
        'priority',
        'category',
        'source',
        'last_message_at',
        'first_responded_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'first_responded_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('created_at');
    }

    public function touchLastMessageAt(?Carbon $date = null): void
    {
        $this->forceFill([
            'last_message_at' => $date ?? now(),
        ])->save();
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN,
            self::STATUS_WAITING,
            self::STATUS_RESPONDED,
            self::STATUS_CLOSED,
        ];
    }

    public static function priorities(): array
    {
        return ['low', 'normal', 'high', 'urgent'];
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    public function getPriorityLabelAttribute(): string
    {
        return ucfirst($this->priority ?: 'normal');
    }
}
