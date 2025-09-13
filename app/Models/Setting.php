<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_days',
        'work_hours',
        'cancel_policy',
        'deposit_policy',
        'notification_prefs',
        'branding',
        'address',
        'map_point',
    ];

    protected function casts(): array
    {
        return [
            'work_days' => 'array',
            'work_hours' => 'array',
            'cancel_policy' => 'array',
            'deposit_policy' => 'array',
            'notification_prefs' => 'array',
            'branding' => 'array',
            'map_point' => 'array',
        ];
    }
}
