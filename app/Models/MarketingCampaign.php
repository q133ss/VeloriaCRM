<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'template_id',
        'name',
        'channel',
        'segment',
        'segment_filters',
        'is_ab_test',
        'status',
        'scheduled_at',
        'subject',
        'content',
        'test_group_size',
        'winning_variant_id',
        'delivered_count',
        'read_count',
        'click_count',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'segment_filters' => 'array',
            'is_ab_test' => 'bool',
            'scheduled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MarketingCampaignVariant::class, 'campaign_id');
    }

    public function winningVariant(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaignVariant::class, 'winning_variant_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(MarketingDelivery::class, 'campaign_id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
