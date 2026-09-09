<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * The 1:1 conversation between a master and one of her own clients. Unlike
 * `SupportTicket` (many tickets per user, triaged by status) there is exactly
 * one thread per (user_id, client_id) pair — it is found-or-created lazily on
 * whoever sends the first message.
 */
class ChatThread extends Model
{
    use HasFactory;

    public const SENDER_MASTER = 'master';

    public const SENDER_CLIENT = 'client';

    protected $fillable = [
        'user_id',
        'client_id',
        'last_message_at',
        'last_message_sender_type',
        'master_read_at',
        'client_read_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'master_read_at' => 'datetime',
            'client_read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    public function scopeForMaster($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function recordMessage(string $senderType, ?Carbon $date = null): void
    {
        $date ??= now();

        $this->forceFill([
            'last_message_at' => $date,
            'last_message_sender_type' => $senderType,
            ...($senderType === self::SENDER_MASTER ? ['master_read_at' => $date] : []),
            ...($senderType === self::SENDER_CLIENT ? ['client_read_at' => $date] : []),
        ])->save();
    }

    public function markReadByMaster(): void
    {
        $this->forceFill(['master_read_at' => now()])->save();
    }

    public function markReadByClient(): void
    {
        $this->forceFill(['client_read_at' => now()])->save();
    }

    public function isUnreadForMaster(): bool
    {
        return $this->last_message_sender_type === self::SENDER_CLIENT
            && $this->last_message_at !== null
            && ($this->master_read_at === null || $this->last_message_at->gt($this->master_read_at));
    }

    public function isUnreadForClient(): bool
    {
        return $this->last_message_sender_type === self::SENDER_MASTER
            && $this->last_message_at !== null
            && ($this->client_read_at === null || $this->last_message_at->gt($this->client_read_at));
    }
}
