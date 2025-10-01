<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'payment_id',
        'amount',
        'currency',
        'status',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'metadata' => 'array',
            'amount' => 'decimal:2',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
