<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ScheduleService
{
    private const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    public function normalizeRules(
        ?array $rules,
        array $legacyWorkDays = [],
        array $legacyWorkHours = [],
    ): array {
        $rules = is_array($rules) ? $rules : [];
        $legacy = $this->rulesFromLegacy($legacyWorkDays, $legacyWorkHours);
        $mode = $rules['mode'] ?? $legacy['mode'] ?? 'weekly';

        $weeklyRules = $this->normalizeWeeklyRules($rules['weekly'] ?? $legacy['weekly'] ?? []);
        $cycleRules = $this->normalizeCycleRules($rules['cycle'] ?? []);
        $monthlyRules = $this->normalizeMonthlyRules($rules['monthly'] ?? []);

        if (! in_array($mode, ['weekly', 'cycle', 'monthly'], true)) {
            $mode = 'weekly';
        }

        if ($mode === 'cycle' && $cycleRules['slots'] === []) {
            $mode = 'weekly';
        }

        if ($mode === 'monthly' && $monthlyRules['dates'] === []) {
            $mode = 'weekly';
        }

        return [
            'mode' => $mode,
            'weekly' => $weeklyRules,
            'cycle' => $cycleRules,
            'monthly' => $monthlyRules,
        ];
    }

    public function rulesFromLegacy(array $workDays = [], array $workHours = []): array
    {
        $weekly = [];

        foreach (self::DAYS as $day) {
            $enabled = in_array($day, $workDays, true);
            $slots = $this->normalizeSlots($workHours[$day] ?? []);

            if ($slots !== []) {
                $enabled = true;
            }

            $weekly[$day] = [
                'enabled' => $enabled,
                'slots' => $enabled ? $slots : [],
            ];
        }

        return [
            'mode' => 'weekly',
            'weekly' => $weekly,
            'cycle' => [
                'anchor_date' => now()->toDateString(),
                'work_days' => 2,
                'rest_days' => 2,
                'slots' => [],
            ],
            'monthly' => [
                'dates' => [],
            ],
        ];
    }

    public function deriveLegacyFields(array $rules): array
    {
        $normalized = $this->normalizeRules($rules);
        $workDays = [];
        $workHours = [];

        foreach (self::DAYS as $day) {
            $dayRules = $normalized['weekly'][$day];

            if (! empty($dayRules['enabled']) && ! empty($dayRules['slots'])) {
                $workDays[] = $day;
                $workHours[$day] = $dayRules['slots'];
            }
        }

        return [
            'work_days' => $workDays,
            'work_hours' => $workHours,
        ];
    }

    public function resolveSlotsForDate(?Setting $setting, CarbonInterface|string $date, ?string $timezone = null): array
    {
        $timezone = $timezone ?: config('app.timezone');
        $day = is_string($date)
            ? Carbon::parse($date, $timezone)->startOfDay()
            : Carbon::instance($date)->copy()->timezone($timezone)->startOfDay();

        $rules = $this->normalizeRules(
            $setting?->schedule_rules,
            is_array($setting?->work_days) ? $setting->work_days : [],
            is_array($setting?->work_hours) ? $setting->work_hours : [],
        );

        return match ($rules['mode']) {
            'cycle' => $this->resolveCycleSlots($rules['cycle'], $day),
            'monthly' => $this->resolveMonthlySlots($rules['monthly'], $day),
            default => $this->resolveWeeklySlots($rules['weekly'], $day),
        };
    }

    public function hasWorkingSlots(?Setting $setting, CarbonInterface|string $date, ?string $timezone = null): bool
    {
        return $this->resolveSlotsForDate($setting, $date, $timezone) !== [];
    }

    public function buildSettingsPayload(?Setting $setting): array
    {
        $legacyWorkDays = is_array($setting?->work_days) ? $setting->work_days : [];
        $legacyWorkHours = is_array($setting?->work_hours) ? $setting->work_hours : [];

        return [
            'work_days' => $legacyWorkDays,
            'work_hours' => $legacyWorkHours,
            'schedule_rules' => $this->normalizeRules($setting?->schedule_rules, $legacyWorkDays, $legacyWorkHours),
        ];
    }

    private function normalizeWeeklyRules(array $weeklyRules): array
    {
        $normalized = [];

        foreach (self::DAYS as $day) {
            $dayRules = is_array($weeklyRules[$day] ?? null) ? $weeklyRules[$day] : [];
            $slots = $this->normalizeSlots($dayRules['slots'] ?? []);
            $enabled = filter_var($dayRules['enabled'] ?? ($slots !== []), FILTER_VALIDATE_BOOLEAN);

            $normalized[$day] = [
                'enabled' => $enabled,
                'slots' => $enabled ? $slots : [],
            ];
        }

        return $normalized;
    }

    private function normalizeCycleRules(array $cycleRules): array
    {
        $anchorDate = $cycleRules['anchor_date'] ?? now()->toDateString();

        try {
            $anchorDate = Carbon::parse((string) $anchorDate)->toDateString();
        } catch (\Throwable) {
            $anchorDate = now()->toDateString();
        }

        $workDays = max(1, min((int) ($cycleRules['work_days'] ?? 2), 31));
        $restDays = max(1, min((int) ($cycleRules['rest_days'] ?? 2), 31));

        return [
            'anchor_date' => $anchorDate,
            'work_days' => $workDays,
            'rest_days' => $restDays,
            'slots' => $this->normalizeSlots($cycleRules['slots'] ?? []),
        ];
    }

    private function normalizeMonthlyRules(array $monthlyRules): array
    {
        $dates = is_array($monthlyRules['dates'] ?? null) ? $monthlyRules['dates'] : [];
        $normalizedDates = [];

        foreach ($dates as $date => $slots) {
            try {
                $dateKey = Carbon::parse((string) $date)->toDateString();
            } catch (\Throwable) {
                continue;
            }

            $normalizedSlots = $this->normalizeSlots($slots);
            if ($normalizedSlots === []) {
                continue;
            }

            $normalizedDates[$dateKey] = $normalizedSlots;
        }

        ksort($normalizedDates);

        return [
            'dates' => $normalizedDates,
        ];
    }

    private function resolveWeeklySlots(array $weeklyRules, CarbonInterface $day): array
    {
        $dayKey = strtolower($day->format('D'));
        $dayRules = $weeklyRules[$dayKey] ?? ['enabled' => false, 'slots' => []];

        if (empty($dayRules['enabled'])) {
            return [];
        }

        return $dayRules['slots'] ?? [];
    }

    private function resolveCycleSlots(array $cycleRules, CarbonInterface $day): array
    {
        if (($cycleRules['slots'] ?? []) === []) {
            return [];
        }

        $anchor = Carbon::createFromFormat('Y-m-d', $cycleRules['anchor_date'], $day->getTimezone())->startOfDay();
        $diff = $anchor->diffInDays($day, false);
        $workDays = (int) $cycleRules['work_days'];
        $restDays = (int) $cycleRules['rest_days'];
        $cycleLength = $workDays + $restDays;

        if ($cycleLength <= 0) {
            return [];
        }

        $position = (($diff % $cycleLength) + $cycleLength) % $cycleLength;

        return $position < $workDays ? ($cycleRules['slots'] ?? []) : [];
    }

    private function resolveMonthlySlots(array $monthlyRules, CarbonInterface $day): array
    {
        return $monthlyRules['dates'][$day->toDateString()] ?? [];
    }

    /**
     * @param  array<int, mixed>|string|null  $rawSlots
     * @return array<int, string>
     */
    private function normalizeSlots(array|string|null $rawSlots): array
    {
        if (is_string($rawSlots)) {
            $rawSlots = preg_split('/[\s,;]+/', $rawSlots, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        if (! is_array($rawSlots)) {
            return [];
        }

        $normalized = [];

        foreach ($rawSlots as $slot) {
            if (! is_string($slot)) {
                continue;
            }

            $slot = trim($slot);
            if (! preg_match('/^\d{2}:\d{2}$/', $slot)) {
                continue;
            }

            [$hours, $minutes] = array_map('intval', explode(':', $slot));
            if ($hours < 0 || $hours > 23 || ! in_array($minutes, [0, 15, 30, 45], true)) {
                continue;
            }

            $normalized[] = sprintf('%02d:%02d', $hours, $minutes);
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }
}
