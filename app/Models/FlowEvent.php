<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'flow_run_id',
        'client_id',
        'event_type',
        'channel',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
