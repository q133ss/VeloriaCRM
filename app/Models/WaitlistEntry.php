<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'client_user_id',
        'service_id',
        'preferred_slots',
        'preferred_dates',
        'preferred_time_windows',
        'flexibility_days',
        'priority',
        'priority_manual',
        'status',
        'source',
        'notes',
        'last_offered_at',
        'expires_at',
        'matched_slot',
        'match_score',
        'match_reasons',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'preferred_slots' => 'array',
            'preferred_dates' => 'array',
            'preferred_time_windows' => 'array',
            'match_reasons' => 'array',
            'meta' => 'array',
            'last_offered_at' => 'datetime',
            'expires_at' => 'datetime',
            'matched_slot' => 'datetime',
        ];
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
