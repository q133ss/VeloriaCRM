<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoiceNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'recorder_id',
        'audio_path',
        'transcript',
        'notes',
        'status',
        'language',
    ];

    protected function casts(): array
    {
        return [
            'notes' => 'array',
        ];
    }
}
