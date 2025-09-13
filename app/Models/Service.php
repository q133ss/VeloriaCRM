<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'base_price',
        'cost',
        'duration_min',
        'upsell_suggestions',
    ];

    protected function casts(): array
    {
        return [
            'upsell_suggestions' => 'array',
        ];
    }
}
