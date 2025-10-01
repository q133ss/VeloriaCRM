<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\SubscriptionTransaction;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
    ];

    protected $appends = ['slug'];

    public function getSlugAttribute(): string
    {
        return $this->name;
    }

    public function subscriptionTransactions(): HasMany
    {
        return $this->hasMany(SubscriptionTransaction::class);
    }
}
