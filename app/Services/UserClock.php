<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Renders a moment the way this particular master reads a clock.
 *
 * Settings offer a timezone and a 24h/12h switch. The timezone reached the
 * dashboard, the analytics and the schedule but not the two screens where time
 * actually matters — the bookings list and the calendar formatted
 * `scheduled_at` straight, so a master who moved to another city kept seeing
 * Moscow hours. The 12h switch reached nothing at all: it was stored, returned
 * by the API, and read by no formatter anywhere in the product.
 *
 * One place to ask, so the next screen cannot forget either half.
 */
class UserClock
{
    private ?User $user = null;

    private bool $resolved = false;

    /** Pins the clock to a given master; without it the signed-in one is used. */
    public function for(?User $user): self
    {
        $clone = new self();
        $clone->user = $user;
        $clone->resolved = true;

        return $clone;
    }

    public function timezone(): string
    {
        return $this->user()?->timezone ?: config('app.timezone');
    }

    /** `24h` or `12h`, as chosen in Settings. */
    public function timeFormat(): string
    {
        return $this->user()?->time_format === '12h' ? '12h' : '24h';
    }

    /** «14:00» or «2:00 PM». */
    public function time(?Carbon $moment): ?string
    {
        return $this->format($moment, $this->timeFormat() === '12h' ? 'g:i A' : 'H:i');
    }

    /** «08.09.2026 14:00» or «08.09.2026 2:00 PM». */
    public function dateTime(?Carbon $moment): ?string
    {
        return $this->format($moment, $this->timeFormat() === '12h' ? 'd.m.Y g:i A' : 'd.m.Y H:i');
    }

    public function date(?Carbon $moment): ?string
    {
        return $this->format($moment, 'd.m.Y');
    }

    /** The same moment, moved into the master's zone. */
    public function inZone(?Carbon $moment): ?Carbon
    {
        return $moment?->copy()->setTimezone($this->timezone());
    }

    private function format(?Carbon $moment, string $pattern): ?string
    {
        return $this->inZone($moment)?->format($pattern);
    }

    private function user(): ?User
    {
        if (! $this->resolved) {
            $this->user = Auth::guard('sanctum')->user();
            $this->resolved = true;
        }

        return $this->user;
    }
}
