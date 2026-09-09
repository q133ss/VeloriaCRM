<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The client-app mirror of `Notification` (master-only) — same shape, kept as
 * its own model/table because the two actors (`Client`/`User`) authenticate
 * and broadcast on separate channels, and a client must never be able to read
 * or mark-read a master's own notification row.
 */
class ClientNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'title',
        'message',
        'action_url',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];

    public const UPDATED_AT = null;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }
}
