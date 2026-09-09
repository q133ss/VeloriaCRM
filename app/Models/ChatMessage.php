<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_thread_id',
        'sender_type',
        'user_id',
        'body',
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

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class, 'chat_thread_id');
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
