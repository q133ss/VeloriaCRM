<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_id',
        'client_id',
        'services',
        'scheduled_at',
        'actual_started_at',
        'note',
        'duration',
        'duration_forecast',
        'actual_finished_at',
        'total_price',
        'status',
        'rescheduled_from',
        'reschedule_count',
        'cancellation_reason',
        'client_lateness',
        'confirmed_at',
        'cancelled_at',
        'reminded_at',
        'payment_method',
        'payment_status',
        'duration_optimistic',
        'duration_pessimistic',
        'confidence_level',
        'source',
        'prepaid_amount',
        'is_reminder_sent',
        'complexity_level',
        'recommended_services',
    ];

    protected function casts(): array
    {
        return [
            'services' => 'array',
            'scheduled_at' => 'datetime',
            'actual_started_at' => 'datetime',
            'duration' => 'integer',
            'duration_forecast' => 'integer',
            'actual_finished_at' => 'datetime',
            'total_price' => 'decimal:2',
            'rescheduled_from' => 'datetime',
            'reschedule_count' => 'integer',
            'client_lateness' => 'integer',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reminded_at' => 'datetime',
            'payment_method' => 'string',
            'payment_status' => 'string',
            'duration_optimistic' => 'integer',
            'duration_pessimistic' => 'integer',
            'confidence_level' => 'decimal:2',
            'source' => 'string',
            'prepaid_amount' => 'decimal:2',
            'is_reminder_sent' => 'boolean',
            'complexity_level' => 'integer',
            'recommended_services' => 'array',
        ];
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'master_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
