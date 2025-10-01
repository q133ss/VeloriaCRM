<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            QaSeeder::class,
        ]);

        $stylist = User::updateOrCreate(
            ['email' => 'maria.sokolova@veloria.test'],
            [
                'name' => 'Мария Соколова',
                'phone' => '+79990005544',
                'password' => 'password',
                'timezone' => 'Europe/Moscow',
                'time_format' => '24h',
            ]
        );

        $colorist = User::updateOrCreate(
            ['email' => 'alexey.morozov@veloria.test'],
            [
                'name' => 'Алексей Морозов',
                'phone' => '+79990007711',
                'password' => 'password',
                'timezone' => 'Europe/Moscow',
                'time_format' => '24h',
            ]
        );

        $nailMaster = User::updateOrCreate(
            ['email' => 'natalia.zueva@veloria.test'],
            [
                'name' => 'Наталья Зуева',
                'phone' => '+79990006622',
                'password' => 'password',
                'timezone' => 'Europe/Moscow',
                'time_format' => '24h',
            ]
        );

        $elena = User::updateOrCreate(
            ['email' => 'elena.morozova@client.test'],
            [
                'name' => 'Елена Морозова',
                'phone' => '+79990008855',
                'password' => 'password',
                'timezone' => 'Europe/Moscow',
                'time_format' => '24h',
            ]
        );

        $dmitry = User::updateOrCreate(
            ['email' => 'dmitry.fadeev@client.test'],
            [
                'name' => 'Дмитрий Фадеев',
                'phone' => '+79990009944',
                'password' => 'password',
                'timezone' => 'Europe/Moscow',
                'time_format' => '24h',
            ]
        );

        $svetlana = User::updateOrCreate(
            ['email' => 'svetlana.krylova@client.test'],
            [
                'name' => 'Светлана Крылова',
                'phone' => '+79990004433',
                'password' => 'password',
                'timezone' => 'Europe/Moscow',
                'time_format' => '24h',
            ]
        );

        $services = Service::whereIn('name', [
            'Стрижка женская',
            'Укладка',
            'Окрашивание',
            'Покрытие гель-лаком',
            'Классический маникюр',
        ])->get()->keyBy('name');

        $femaleHaircut = $services->get('Стрижка женская');
        $stylingService = $services->get('Укладка');
        $coloringService = $services->get('Окрашивание');
        $gelPolishService = $services->get('Покрытие гель-лаком');
        $manicureService = $services->get('Классический маникюр');

        $completedScheduledAt = Carbon::now()->subDays(2)->setTime(11, 0);
        $completedStart = $completedScheduledAt->copy()->addMinutes(5);
        $completedFinish = $completedStart->copy()->addMinutes(95);

        Order::updateOrCreate(
            [
                'master_id' => $stylist->id,
                'client_id' => $elena->id,
                'scheduled_at' => $completedScheduledAt,
            ],
            [
                'services' => [
                    [
                        'id' => $services->get('Стрижка женская')?->id,
                        'name' => 'Стрижка женская',
                        'price' => 1500,
                        'duration' => 60,
                    ],
                    [
                        'id' => $services->get('Укладка')?->id,
                        'name' => 'Укладка',
                        'price' => 800,
                        'duration' => 30,
                    ],
                ],
                'actual_started_at' => $completedStart,
                'note' => 'Просила использовать кератиновый состав и зафиксировать укладку спреем.',
                'duration' => 95,
                'duration_forecast' => 90,
                'actual_finished_at' => $completedFinish,
                'total_price' => 2300,
                'status' => 'completed',
                'rescheduled_from' => $completedScheduledAt->copy()->subDay()->setTime(13, 0),
                'reschedule_count' => 1,
                'cancellation_reason' => null,
                'client_lateness' => 5,
                'confirmed_at' => $completedScheduledAt->copy()->subDay()->setTime(9, 15),
                'cancelled_at' => null,
                'reminded_at' => $completedScheduledAt->copy()->subHours(6),
                'payment_method' => 'card',
                'payment_status' => 'paid',
                'duration_optimistic' => 85,
                'duration_pessimistic' => 110,
                'confidence_level' => 0.87,
                'source' => 'telegram',
                'prepaid_amount' => 500,
                'is_reminder_sent' => true,
                'complexity_level' => 4,
                'recommended_services' => [
                    [
                        'title' => 'Обновить оттенок',
                        'insight' => 'Клиент интересовалась обновлением цвета на прошлом визите.',
                        'action' => 'Подготовьте палитру и обсудите более стойкие техники окрашивания.',
                        'confidence' => 0.62,
                        'service' => [
                            'id' => $coloringService?->id,
                            'name' => $coloringService?->name,
                            'price' => $coloringService ? (float) $coloringService->base_price : null,
                            'duration' => $coloringService ? (int) $coloringService->duration_min : null,
                        ],
                    ],
                ],
            ]
        );

        $upcomingScheduledAt = Carbon::now()->addDays(1)->setTime(16, 30);

        Order::updateOrCreate(
            [
                'master_id' => $colorist->id,
                'client_id' => $dmitry->id,
                'scheduled_at' => $upcomingScheduledAt,
            ],
            [
                'services' => [
                    [
                        'id' => $services->get('Окрашивание')?->id,
                        'name' => 'Окрашивание',
                        'price' => 2500,
                        'duration' => 120,
                    ],
                ],
                'actual_started_at' => null,
                'note' => 'Попросил сохранить длину и освежить оттенок без резких переходов.',
                'duration' => null,
                'duration_forecast' => 140,
                'actual_finished_at' => null,
                'total_price' => 2500,
                'status' => 'confirmed',
                'rescheduled_from' => $upcomingScheduledAt->copy()->subHours(2),
                'reschedule_count' => 1,
                'cancellation_reason' => null,
                'client_lateness' => null,
                'confirmed_at' => Carbon::now()->subHours(3),
                'cancelled_at' => null,
                'reminded_at' => null,
                'payment_method' => 'card',
                'payment_status' => 'pending',
                'duration_optimistic' => 125,
                'duration_pessimistic' => 160,
                'confidence_level' => 0.74,
                'source' => 'instagram',
                'prepaid_amount' => 700,
                'is_reminder_sent' => false,
                'complexity_level' => 5,
                'recommended_services' => [
                    [
                        'title' => 'Закрепить результат укладкой',
                        'insight' => 'После окрашивания клиенту поможет укладка, чтобы увидеть финальный образ.',
                        'action' => 'Предложите стайлинг или домашние средства для поддержания формы.',
                        'confidence' => 0.54,
                        'service' => [
                            'id' => $stylingService?->id,
                            'name' => $stylingService?->name,
                            'price' => $stylingService ? (float) $stylingService->base_price : null,
                            'duration' => $stylingService ? (int) $stylingService->duration_min : null,
                        ],
                    ],
                ],
            ]
        );

        $cancelledScheduledAt = Carbon::now()->addDays(3)->setTime(12, 0);

        Order::updateOrCreate(
            [
                'master_id' => $nailMaster->id,
                'client_id' => $svetlana->id,
                'scheduled_at' => $cancelledScheduledAt,
            ],
            [
                'services' => [
                    [
                        'id' => $services->get('Классический маникюр')?->id,
                        'name' => 'Классический маникюр',
                        'price' => 1200,
                        'duration' => 50,
                    ],
                    [
                        'id' => $services->get('Покрытие гель-лаком')?->id,
                        'name' => 'Покрытие гель-лаком',
                        'price' => 700,
                        'duration' => 40,
                    ],
                ],
                'actual_started_at' => null,
                'note' => 'Клиент просила подобрать нюдовый оттенок и сохранить короткую длину.',
                'duration' => null,
                'duration_forecast' => 90,
                'actual_finished_at' => null,
                'total_price' => 1900,
                'status' => 'cancelled',
                'rescheduled_from' => $cancelledScheduledAt->copy()->subDays(2)->setTime(17, 30),
                'reschedule_count' => 2,
                'cancellation_reason' => 'Срочная командировка, попросила перенести визит на следующий месяц.',
                'client_lateness' => null,
                'confirmed_at' => $cancelledScheduledAt->copy()->subDays(3)->setTime(10, 0),
                'cancelled_at' => Carbon::now()->subDay()->setTime(18, 45),
                'reminded_at' => $cancelledScheduledAt->copy()->subDays(1)->setTime(9, 0),
                'payment_method' => 'cash',
                'payment_status' => 'refunded',
                'duration_optimistic' => 80,
                'duration_pessimistic' => 110,
                'confidence_level' => 0.68,
                'source' => 'vk',
                'prepaid_amount' => 300,
                'is_reminder_sent' => true,
                'complexity_level' => 3,
                'recommended_services' => [],
            ]
        );

        $this->call(LearningSeeder::class);
    }
}
