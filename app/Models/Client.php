<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'birthday',
        'tags',
        'allergies',
        'preferences',
        'notes',
        'last_visit_at',
        'loyalty_level',
    ];

    protected $casts = [
        'tags' => 'array',
        'allergies' => 'array',
        'preferences' => 'array',
        'last_visit_at' => 'datetime',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }
}
