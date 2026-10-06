<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\ScheduleService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class ScheduleServiceTest extends TestCase
{
    public function test_it_resolves_weekly_slots_with_quarter_hours(): void
    {
        $service = new ScheduleService();
        $setting = new Setting([
            'schedule_rules' => [
                'mode' => 'weekly',
                'weekly' => [
                    'mon' => ['enabled' => true, 'slots' => ['15:30', '09:00', '10:00']],
                ],
            ],
        ]);

        $slots = $service->resolveSlotsForDate($setting, Carbon::parse('2026-03-16'), 'Europe/Moscow');

        $this->assertSame(['09:00', '10:00', '15:30'], $slots);
    }

    public function test_it_resolves_shift_cycle_days(): void
    {
        $service = new ScheduleService();
        $setting = new Setting([
            'schedule_rules' => [
                'mode' => 'cycle',
                'cycle' => [
                    'anchor_date' => '2026-03-10',
                    'work_days' => 2,
                    'rest_days' => 2,
                    'slots' => ['09:00', '19:00'],
                ],
            ],
        ]);

        $this->assertSame(['09:00', '19:00'], $service->resolveSlotsForDate($setting, '2026-03-10', 'Europe/Moscow'));
        $this->assertSame(['09:00', '19:00'], $service->resolveSlotsForDate($setting, '2026-03-11', 'Europe/Moscow'));
        $this->assertSame([], $service->resolveSlotsForDate($setting, '2026-03-12', 'Europe/Moscow'));
        $this->assertSame([], $service->resolveSlotsForDate($setting, '2026-03-13', 'Europe/Moscow'));
    }

    public function test_legacy_monthly_mode_still_resolves_only_its_listed_dates(): void
    {
        // 'monthly' used to be an exclusive mode saved by old clients: every date not
        // in the list was a day off and `weekly` was ignored. Loading that record
        // today must still behave exactly the same way.
        $service = new ScheduleService();
        $setting = new Setting([
            'schedule_rules' => [
                'mode' => 'monthly',
                'weekly' => [
                    'mon' => ['enabled' => true, 'slots' => ['09:00']],
                ],
                'monthly' => [
                    'dates' => [
                        '2026-03-20' => ['11:00', '15:30'],
                    ],
                ],
            ],
        ]);

        $this->assertSame(['11:00', '15:30'], $service->resolveSlotsForDate($setting, '2026-03-20', 'Europe/Moscow'));
        $this->assertSame([], $service->resolveSlotsForDate($setting, '2026-03-21', 'Europe/Moscow'));
        // 2026-03-16 is a Monday: the leftover `weekly` data must stay dead, not
        // resurface as the new fallback for unlisted dates.
        $this->assertSame([], $service->resolveSlotsForDate($setting, '2026-03-16', 'Europe/Moscow'));
    }

    public function test_a_picked_date_overrides_the_weekly_rule_in_both_directions(): void
    {
        $service = new ScheduleService();
        $setting = new Setting([
            'schedule_rules' => [
                'mode' => 'weekly',
                'weekly' => [
                    'mon' => ['enabled' => true, 'slots' => ['09:00', '10:00']],
                ],
                'monthly' => [
                    'dates' => [
                        // Monday 2026-03-16, picked by hand as a day off.
                        '2026-03-16' => [],
                        // Tuesday 2026-03-17, normally off, picked as a work day.
                        '2026-03-17' => ['12:00', '13:00'],
                    ],
                ],
            ],
        ]);

        $this->assertSame([], $service->resolveSlotsForDate($setting, '2026-03-16', 'Europe/Moscow'));
        $this->assertSame(['12:00', '13:00'], $service->resolveSlotsForDate($setting, '2026-03-17', 'Europe/Moscow'));
        // The following Monday has no override and keeps following the rule.
        $this->assertSame(['09:00', '10:00'], $service->resolveSlotsForDate($setting, '2026-03-23', 'Europe/Moscow'));
    }

    public function test_a_picked_date_overrides_the_shift_cycle_too(): void
    {
        $service = new ScheduleService();
        $setting = new Setting([
            'schedule_rules' => [
                'mode' => 'cycle',
                'cycle' => [
                    'anchor_date' => '2026-03-10',
                    'work_days' => 2,
                    'rest_days' => 2,
                    'slots' => ['09:00', '19:00'],
                ],
                'monthly' => [
                    // 2026-03-10 is a work day in the cycle, picked by hand as a day off.
                    'dates' => ['2026-03-10' => []],
                ],
            ],
        ]);

        $this->assertSame([], $service->resolveSlotsForDate($setting, '2026-03-10', 'Europe/Moscow'));
        // The rest of the cycle is unaffected.
        $this->assertSame(['09:00', '19:00'], $service->resolveSlotsForDate($setting, '2026-03-11', 'Europe/Moscow'));
    }
}
