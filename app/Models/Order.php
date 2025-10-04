<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Order extends Model
{
    use HasFactory;

    public const STATUS_LABELS = [
        'new' => 'Новая',
        'confirmed' => 'Подтверждена',
        'in_progress' => 'В работе',
        'completed' => 'Завершена',
        'cancelled' => 'Отменена',
        'no_show' => 'Не пришёл',
    ];

    public const PERIOD_OPTIONS = [
        'today' => 'Сегодня',
        'tomorrow' => 'Завтра',
        'this_week' => 'Текущая неделя',
        'next_week' => 'Следующая неделя',
        'this_month' => 'Текущий месяц',
        'next_month' => 'Следующий месяц',
        'all' => 'За всё время',
    ];

    protected $fillable = [
        'master_id',
        'client_id',
        'services',
        'scheduled_at',
        'actual_started_at',
        'note',
        'duration',
        'duration_forecast',
        'actual_finished_at',
        'total_price',
        'status',
        'rescheduled_from',
        'reschedule_count',
        'cancellation_reason',
        'client_lateness',
        'confirmed_at',
        'cancelled_at',
        'reminded_at',
        'start_confirmation_notified_at',
        'payment_method',
        'payment_status',
        'duration_optimistic',
        'duration_pessimistic',
        'confidence_level',
        'source',
        'prepaid_amount',
        'is_reminder_sent',
        'complexity_level',
        'recommended_services',
    ];

    protected function casts(): array
    {
        return [
            'services' => 'array',
            'scheduled_at' => 'datetime',
            'actual_started_at' => 'datetime',
            'duration' => 'integer',
            'duration_forecast' => 'integer',
            'actual_finished_at' => 'datetime',
            'total_price' => 'decimal:2',
            'rescheduled_from' => 'datetime',
            'reschedule_count' => 'integer',
            'client_lateness' => 'integer',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reminded_at' => 'datetime',
            'start_confirmation_notified_at' => 'datetime',
            'payment_method' => 'string',
            'payment_status' => 'string',
            'duration_optimistic' => 'integer',
            'duration_pessimistic' => 'integer',
            'confidence_level' => 'decimal:2',
            'source' => 'string',
            'prepaid_amount' => 'decimal:2',
            'is_reminder_sent' => 'boolean',
            'complexity_level' => 'integer',
            'recommended_services' => 'array',
        ];
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'master_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function scopeWithFilter(Builder $query, array $filters): Builder
    {
        if (($filters['period'] ?? null) && ($filters['period'] ?? null) !== 'all') {
            $query->where(function (Builder $builder) use ($filters) {
                $period = $filters['period'];
                $now = Carbon::now();

                $ranges = match ($period) {
                    'today' => [
                        $now->copy()->startOfDay(),
                        $now->copy()->endOfDay(),
                    ],
                    'tomorrow' => [
                        $now->copy()->addDay()->startOfDay(),
                        $now->copy()->addDay()->endOfDay(),
                    ],
                    'this_week' => [
                        $now->copy()->startOfWeek(),
                        $now->copy()->endOfWeek(),
                    ],
                    'next_week' => [
                        $now->copy()->addWeek()->startOfWeek(),
                        $now->copy()->addWeek()->endOfWeek(),
                    ],
                    'this_month' => [
                        $now->copy()->startOfMonth(),
                        $now->copy()->endOfMonth(),
                    ],
                    'next_month' => [
                        $now->copy()->addMonth()->startOfMonth(),
                        $now->copy()->addMonth()->endOfMonth(),
                    ],
                    default => null,
                };

                if ($ranges) {
                    $builder->whereBetween('scheduled_at', $ranges);
                }
            });
        }

        if (($filters['status'] ?? null) && ($filters['status'] ?? null) !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($filters['search'] ?? null) {
            $search = trim((string) $filters['search']);
            $digits = preg_replace('/[^0-9]+/', '', $search);

            $query->where(function (Builder $builder) use ($search, $digits) {
                $builder->whereHas('client', function (Builder $clientQuery) use ($search, $digits) {
                    $clientQuery->where(function (Builder $nested) use ($search, $digits) {
                        $nested->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");

                        if ($digits) {
                            $normalized = '+' . ltrim($digits, '+');
                            $nested->orWhere('phone', 'like', "%{$normalized}%");
                        }
                    });
                });
            });
        }

        return $query;
    }

    public static function statusLabels(): array
    {
        return self::STATUS_LABELS;
    }

    public static function periodOptions(): array
    {
        return self::PERIOD_OPTIONS;
    }

    public static function statusBadgeClasses(): array
    {
        return [
            'new' => 'bg-label-primary',
            'confirmed' => 'bg-label-info',
            'in_progress' => 'bg-label-warning',
            'completed' => 'bg-label-success',
            'cancelled' => 'bg-label-secondary',
            'no_show' => 'bg-label-danger',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusClassAttribute(): string
    {
        return self::statusBadgeClasses()[$this->status] ?? 'bg-label-secondary';
    }
}
