<?php

namespace App\Console;

use App\Jobs\SendDailyMasterMoodCheckJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Определение расписания команд приложения.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Ежедневно в 21:00 отправляем мастерам вопрос о самочувствии.
        $schedule->job(new SendDailyMasterMoodCheckJob())
            ->dailyAt('21:00')
            ->name('send-daily-master-mood-check')
            ->withoutOverlapping();
    }

    /**
     * Регистрация команд приложения.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
