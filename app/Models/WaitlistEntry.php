<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaitlistEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'service_id',
        'preferred_slots',
        'priority',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'preferred_slots' => 'array',
        ];
    }
}
