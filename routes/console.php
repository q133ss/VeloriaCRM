<?php

use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:notify-late-start', function (NotificationService $notifications) {
    $now = Carbon::now();
    $threshold = $now->copy()->subMinutes(10);
    $sent = 0;

    Order::query()
        ->with('master')
        ->whereNotNull('scheduled_at')
        ->where('scheduled_at', '<=', $threshold)
        ->whereNull('actual_started_at')
        ->whereIn('status', ['new', 'confirmed'])
        ->whereNull('start_confirmation_notified_at')
        ->orderBy('id')
        ->chunkById(100, function ($orders) use ($notifications, $now, &$sent) {
            foreach ($orders as $order) {
                if (!$order->master_id) {
                    continue;
                }

                $actionUrl = route('orders.start-confirmation', ['order' => $order->id], false);

                $notifications->send(
                    $order->master_id,
                    'Подтвердите начало процедуры',
                    'Пожалуйста, подтвердите, что процедура началась!',
                    $actionUrl
                );

                $order->forceFill([
                    'start_confirmation_notified_at' => $now,
                ])->save();

                $sent++;
            }
        });

    $this->info('Отправлено уведомлений: ' . $sent);
})->purpose('Уведомляет мастеров о записях, которые не были запущены вовремя');

Schedule::command('orders:notify-late-start')->everyFiveMinutes();
