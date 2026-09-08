<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use App\Models\Plan;
use App\Models\SubscriptionTransaction;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    public const ADMIN_ROLE_SUPER_ADMIN = 'super_admin';
    public const ADMIN_ROLE_SUPPORT = 'support';
    public const ADMIN_ROLE_FINANCE = 'finance';
    public const ADMIN_ROLE_ANALYST = 'analyst';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'timezone',
        'time_format',
        'avatar_path',
        'password',
        'is_admin',
        'admin_role',
        'status',
        'suspended_at',
        'admin_notes',
    ];

    protected $appends = [
        'avatar_url',
        'initials',
        'status_label',
        'admin_role_label',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'avatar_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'suspended_at' => 'datetime',
        ];
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class)->withPivot('ends_at')->withTimestamps();
    }

    public function setting()
    {
        return $this->hasOne(Setting::class);
    }

    public function holidays()
    {
        return $this->hasMany(Holiday::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function subscriptionTransactions(): HasMany
    {
        return $this->hasMany(SubscriptionTransaction::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $path = $this->avatar_path;
        if (!is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function getInitialsAttribute(): string
    {
        $name = is_string($this->name) ? trim($this->name) : '';
        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? $parts[count($parts) - 1] : '';

        $firstChar = $first !== '' ? mb_strtoupper(mb_substr($first, 0, 1)) : '';
        $lastChar = $last !== '' ? mb_strtoupper(mb_substr($last, 0, 1)) : '';

        $initials = $firstChar . $lastChar;
        return $initials !== '' ? $initials : '?';
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function canAccessAdmin(): bool
    {
        return (bool) $this->is_admin && in_array($this->admin_role, self::adminRoles(), true);
    }

    public static function adminRoles(): array
    {
        return [
            self::ADMIN_ROLE_SUPER_ADMIN,
            self::ADMIN_ROLE_SUPPORT,
            self::ADMIN_ROLE_FINANCE,
            self::ADMIN_ROLE_ANALYST,
        ];
    }

    /**
     * Which paid tier the master is on right now, as a lowercase slug.
     *
     * This question used to be answered independently in five controllers —
     * Settings, Analytics, Landings, Marketing and Clients — each with its own
     * copy of the same `whereIn('name', ['pro','Pro','PRO', ...])` and its own
     * idea of what an expired `ends_at` means. Whoever forgot to call their copy
     * shipped an open door: campaigns and promotions had no tier check at all.
     */
    public function activePlanSlug(): string
    {
        $plan = $this->plans()
            ->where(function ($query) {
                $query
                    ->whereNull('plan_user.ends_at')
                    ->orWhere('plan_user.ends_at', '>', Carbon::now());
            })
            ->orderByDesc('plans.price')
            ->first();

        return strtolower((string) ($plan?->name ?: 'lite'));
    }

    /** Pro and Elite both count: Elite is Pro plus more. */
    public function hasProAccess(): bool
    {
        return in_array($this->activePlanSlug(), ['pro', 'elite'], true);
    }

    public function hasEliteAccess(): bool
    {
        return $this->activePlanSlug() === 'elite';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SUSPENDED => 'Suspended',
            default => 'Active',
        };
    }

    public function getAdminRoleLabelAttribute(): ?string
    {
        return match ($this->admin_role) {
            self::ADMIN_ROLE_SUPER_ADMIN => 'Super Admin',
            self::ADMIN_ROLE_SUPPORT => 'Support',
            self::ADMIN_ROLE_FINANCE => 'Finance',
            self::ADMIN_ROLE_ANALYST => 'Analyst',
            default => null,
        };
    }
}
