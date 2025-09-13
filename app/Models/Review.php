<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'source',
        'rating',
        'content',
        'status',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'meta' => 'array',
        ];
    }
}
