<?php

namespace App\Services\Landing;

use App\Models\Setting;
use App\Services\ScheduleService;

/**
 * The master's weekly working hours as short lines for a landing page
 * ("Пн–Пт: 10:00–19:00"), taken from the same schedule the booking uses.
 *
 * Only a weekly schedule can be summed up like this; a shift cycle or a list of
 * dates gives no lines and the page simply skips the block.
 */
class WorkingHours
{
    private const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    public function __construct(private readonly ScheduleService $schedule)
    {
    }

    /**
     * @return array<int, array{days: string, from: string, to: string}>
     */
    public function forUser(int $userId): array
    {
        $setting = Setting::query()->where('user_id', $userId)->first();
        $rules = $this->schedule->buildSettingsPayload($setting)['schedule_rules'];

        if (($rules['mode'] ?? 'weekly') !== 'weekly') {
            return [];
        }

        $perDay = [];

        foreach (self::DAYS as $day) {
            $slots = $rules['weekly'][$day]['slots'] ?? [];

            if (! empty($rules['weekly'][$day]['enabled']) && $slots !== []) {
                sort($slots);
                $perDay[$day] = [$slots[0], end($slots)];
            }
        }

        $lines = [];
        $labels = (array) __('landings.pretty.days');
        $run = [];

        $flush = function () use (&$run, &$lines, &$perDay, $labels) {
            if ($run === []) {
                return;
            }

            $first = $run[0];
            $last = $run[count($run) - 1];
            $name = fn (string $day) => $labels[$day] ?? $day;

            $lines[] = [
                'days' => count($run) === 1 ? $name($first) : $name($first) . '–' . $name($last),
                'from' => $perDay[$first][0],
                'to' => $perDay[$first][1],
            ];
            $run = [];
        };

        foreach (self::DAYS as $day) {
            if (! isset($perDay[$day])) {
                $flush();

                continue;
            }

            if ($run !== [] && $perDay[$run[0]] !== $perDay[$day]) {
                $flush();
            }

            $run[] = $day;
        }

        $flush();

        return $lines;
    }
}
