<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Landing extends Model
{
    use HasFactory;

    public static function isFullPageTemplate(?string $template): bool
    {
        return app(\App\Services\Landing\TemplateRegistry::class)->isFullPage($template);
    }

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'landing',
        'slug',
        'settings',
        'is_active',
        'views',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'views' => 0,
    ];

    /**
     * Whether visitors may pick this service on the page: any of the master's
     * services when the page shows them all (or lists none), otherwise only the listed ones.
     */
    public function offersService(int $serviceId): bool
    {
        $ownService = Service::query()->where('user_id', $this->user_id)->whereKey($serviceId)->exists();

        if (! $ownService) {
            return false;
        }

        $settings = $this->settings ?? [];
        $listed = collect($settings['service_ids'] ?? [])
            ->merge(array_filter([$settings['service_id'] ?? null]))
            ->map(fn ($id) => (int) $id)
            ->filter();

        if ($this->type === 'general' && ! empty($settings['show_all_services'])) {
            return true;
        }

        return $listed->isEmpty() || $listed->contains($serviceId);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LandingRequest::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
