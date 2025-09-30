<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingCampaignVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'label',
        'subject',
        'content',
        'sample_size',
        'delivered_count',
        'read_count',
        'click_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sample_size' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'campaign_id');
    }
}
