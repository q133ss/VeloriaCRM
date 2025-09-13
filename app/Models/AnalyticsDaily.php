<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsDaily extends Model
{
    use HasFactory;

    protected $table = 'analytics_daily';

    protected $fillable = [
        'user_id',
        'date',
        'revenue',
        'hours_booked',
        'repeat_rate',
        'margin_per_hour',
        'no_show_count',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
