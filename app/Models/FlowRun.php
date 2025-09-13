<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'flow_id',
        'scheduled_at',
        'status',
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
