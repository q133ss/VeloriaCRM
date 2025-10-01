<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class LearningTask extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'due_on',
        'progress_current',
        'progress_target',
        'progress_unit',
        'completed_at',
        'position',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'progress_unit' => 'array',
            'meta' => 'array',
            'due_on' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markCompleted(?Carbon $completedAt = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => $completedAt ?? now(),
            'progress_current' => $this->progress_target,
        ])->save();
    }

    public function reopen(?int $progressCurrent = null): void
    {
        if ($progressCurrent !== null) {
            $this->progress_current = max(0, $progressCurrent);
        }

        $this->forceFill([
            'status' => $this->progress_current > 0 ? self::STATUS_IN_PROGRESS : self::STATUS_PENDING,
            'completed_at' => null,
            'progress_current' => $this->progress_current,
        ])->save();
    }

    public function updateProgress(int $current): void
    {
        $this->progress_current = max(0, $current);

        if ($this->progress_target > 0 && $this->progress_current >= $this->progress_target) {
            $this->markCompleted();

            return;
        }

        $this->status = $this->progress_current > 0 ? self::STATUS_IN_PROGRESS : self::STATUS_PENDING;
        $this->completed_at = null;
        $this->save();
    }
}
