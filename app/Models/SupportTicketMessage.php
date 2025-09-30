<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SupportTicketMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'sender_type',
        'message',
        'attachment_path',
        'attachment_name',
    ];

    protected function appends(): array
    {
        return ['attachment_url'];
    }

    protected $hidden = [
        'attachment_path',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->attachment_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->attachment_path);
    }
}
