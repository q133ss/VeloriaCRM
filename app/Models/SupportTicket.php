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
        'subject',
        'status',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
