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

    public function test_it_resolves_custom_month_dates_only(): void
    {
        $service = new ScheduleService();
        $setting = new Setting([
            'schedule_rules' => [
                'mode' => 'monthly',
                'monthly' => [
                    'dates' => [
                        '2026-03-20' => ['11:00', '15:30'],
                    ],
                ],
            ],
        ]);

        $this->assertSame(['11:00', '15:30'], $service->resolveSlotsForDate($setting, '2026-03-20', 'Europe/Moscow'));
        $this->assertSame([], $service->resolveSlotsForDate($setting, '2026-03-21', 'Europe/Moscow'));
    }
}
