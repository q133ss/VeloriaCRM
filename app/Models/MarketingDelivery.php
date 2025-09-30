<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'variant_id',
        'client_id',
        'channel',
        'status',
        'sent_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'campaign_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaignVariant::class, 'variant_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
