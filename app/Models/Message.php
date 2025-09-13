<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'channel',
        'direction',
        'content',
        'template_id',
        'status',
        'cost',
        'scheduled_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
