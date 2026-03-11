<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'landing_id',
        'user_id',
        'client_id',
        'service_id',
        'client_name',
        'client_phone',
        'client_email',
        'preferred_date',
        'message',
        'status',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'meta' => 'array',
        ];
    }

    public function landing(): BelongsTo
    {
        return $this->belongsTo(Landing::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
