<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'number',
        'total',
        'discount',
        'payable',
        'status',
        'currency',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
