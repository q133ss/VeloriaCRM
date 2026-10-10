<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrepaymentRule extends Model
{
    public const TYPE_PERIOD = 'period';
    public const TYPE_WEEKDAY = 'weekday';

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'starts_on',
        'ends_on',
        'weekdays',
        'mode',
        'value',
        'service_ids',
        'is_active',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'weekdays' => 'array',
            'value' => 'float',
            'service_ids' => 'array',
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
