<?php

namespace App\Jobs;

use App\Models\MasterMood;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDailyMasterMoodCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Основная логика отправки уведомлений мастерам.
     */
    public function handle(NotificationService $notificationService): void
    {
        // Отправляем уведомление батчами, чтобы не держать весь список мастеров в памяти.
        User::query()->select(['id', 'name'])->chunkById(100, function ($masters) use ($notificationService) {
            foreach ($masters as $master) {
                $message = "Как вы себя чувствуете?\n\n" .
                    "Выберите подходящий вариант ответа:\n" .
                    collect(MasterMood::MOOD_OPTIONS)
                        ->map(fn ($label) => "• {$label}")
                        ->implode("\n");

                // Сервис уведомлений уже умеет доставлять сообщение в real-time фронтенд.
                $notificationService->send(
                    $master->id,
                    'Как вы себя чувствуете?',
                    $message
                );
            }
        });
    }
}
