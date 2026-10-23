<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One open of a prospect's claim link or booking page. */
class ProspectVisit extends Model
{
    public const KIND_CLAIM = 'claim';
    public const KIND_LANDING = 'landing';

    public const UPDATED_AT = null;

    protected $fillable = ['prospect_id', 'kind', 'visitor', 'is_bot', 'referer', 'user_agent'];

    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }
}
