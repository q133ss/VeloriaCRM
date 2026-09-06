<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Services\ClientIdentityService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Fills one master's calendar with a working schedule, clients, services,
 * bookings across the current month and a couple of waiting-list entries,
 * so /calendar can be reviewed with realistic data.
 *
 * Target the account you are logged in as:
 *   php artisan db:seed --class=CalendarDemoSeeder
 *   CALENDAR_DEMO_EMAIL=maria.sokolova@veloria.test php artisan db:seed --class=CalendarDemoSeeder
 */
class CalendarDemoSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('CALENDAR_DEMO_EMAIL', 'admin@email.net');
        $master = User::where('email', $email)->first();

        if (! $master) {
            $this->command?->error("Пользователь {$email} не найден.");

            return;
        }

        $this->command?->info("Наполняю календарь для: {$master->name} ({$master->email})");

        $this->seedSchedule($master);
        $services = $this->seedServices($master);
        $clients = $this->seedClients($master);
        $this->seedOrders($master, $services, $clients);
        $this->seedWaitlist($master, $services, $clients);

        $this->command?->info('Готово.');
    }

    private function seedSchedule(User $master): void
    {
        $hours = ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

        Setting::updateOrCreate(
            ['user_id' => $master->id],
            [
                // Sat is a short day, Sun is off — gives the day panel something to show.
                'work_days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'],
                'work_hours' => [
                    'mon' => $hours,
                    'tue' => $hours,
                    'wed' => $hours,
                    'thu' => $hours,
                    'fri' => $hours,
                    'sat' => ['10:00', '11:00', '12:00', '13:00', '14:00'],
                ],
                'cancel_policy' => ['hours' => 24],
                'notification_prefs' => ['sms' => true, 'email' => true, 'telegram' => false],
            ]
        );
    }

    /** @return array<string, Service> */
    private function seedServices(User $master): array
    {
        $category = ServiceCategory::firstOrCreate(
            ['user_id' => $master->id, 'name' => 'Основные услуги'],
        );

        $definitions = [
            ['Стрижка женская', 2500, 60],
            ['Окрашивание', 6500, 150],
            ['Укладка', 1500, 45],
            ['Маникюр с покрытием', 2800, 90],
            ['Коррекция бровей', 1200, 30],
        ];

        $services = [];

        foreach ($definitions as [$name, $price, $duration]) {
            $services[$name] = Service::updateOrCreate(
                ['user_id' => $master->id, 'name' => $name],
                [
                    'category_id' => $category->id,
                    'base_price' => $price,
                    'cost' => round($price * 0.3),
                    'duration_min' => $duration,
                ]
            );
        }

        return $services;
    }

    /**
     * orders.client_id points at a User account, not at the master's Client card,
     * so go through the same identity service the order flow uses — it creates
     * both and links them.
     *
     * @return array<string, array{user: User, card: Client}>
     */
    private function seedClients(User $master): array
    {
        $identity = app(ClientIdentityService::class);

        $definitions = [
            ['Ольга Ким', '+79161234501', 'olga.kim@example.test', ['постоянная']],
            ['Марина Белова', '+79161234502', 'marina.belova@example.test', ['новая']],
            ['Ирина Кравцова', '+79161234503', 'irina.kravtsova@example.test', ['vip']],
            ['Дарья Носова', '+79161234504', 'daria.nosova@example.test', []],
            ['Анна Лебедева', '+79161234505', 'anna.lebedeva@example.test', ['постоянная']],
            ['Юлия Титова', '+79161234506', 'yulia.titova@example.test', []],
        ];

        $clients = [];

        foreach ($definitions as [$name, $phone, $mail, $tags]) {
            $resolved = $identity->resolve($master->id, $phone, $name, $mail, ['tags' => $tags]);
            $clients[$name] = $resolved;
        }

        return $clients;
    }

    /**
     * @param  array<string, Service>  $services
     * @param  array<string, array{user: User, card: Client}>  $clients
     */
    private function seedOrders(User $master, array $services, array $clients): void
    {
        $today = Carbon::today();

        // [days from today, time, client, [services], status, note]
        $plan = [
            [-9, '11:00', 'Ольга Ким', ['Стрижка женская', 'Укладка'], 'completed', 'Просила не укорачивать чёлку.'],
            [-6, '15:00', 'Ирина Кравцова', ['Окрашивание'], 'completed', null],
            [-2, '10:00', 'Дарья Носова', ['Маникюр с покрытием'], 'no_show', 'Не пришла, не предупредила.'],
            [-1, '12:00', 'Анна Лебедева', ['Коррекция бровей'], 'completed', null],

            [0, '10:00', 'Марина Белова', ['Стрижка женская'], 'confirmed', 'Первый визит, пришла по рекомендации.'],
            [0, '14:00', 'Ольга Ким', ['Укладка'], 'in_progress', null],
            [0, '17:00', 'Юлия Титова', ['Маникюр с покрытием'], 'new', 'Просит записать на вечер, если освободится.'],

            [1, '09:00', 'Ирина Кравцова', ['Окрашивание', 'Укладка'], 'confirmed', 'Аллергия на аммиак — безаммиачный состав.'],
            [1, '15:00', 'Дарья Носова', ['Коррекция бровей'], 'new', null],

            [3, '11:00', 'Анна Лебедева', ['Стрижка женская'], 'confirmed', null],
            [4, '12:00', 'Марина Белова', ['Маникюр с покрытием'], 'new', null],
            [4, '16:00', 'Ольга Ким', ['Окрашивание'], 'confirmed', 'Хочет тон светлее прошлого раза.'],
            [6, '10:00', 'Юлия Титова', ['Укладка'], 'new', null],
            [8, '14:00', 'Ирина Кравцова', ['Стрижка женская', 'Укладка'], 'confirmed', null],
            [11, '11:00', 'Дарья Носова', ['Маникюр с покрытием'], 'new', null],
            [14, '15:00', 'Анна Лебедева', ['Окрашивание'], 'new', 'Записалась заранее перед отпуском.'],
            [18, '12:00', 'Марина Белова', ['Коррекция бровей'], 'new', null],

            [2, '13:00', 'Ольга Ким', ['Стрижка женская'], 'cancelled', 'Отменила накануне, перенос на следующую неделю.'],

            // A day built to show a dead gap: 10:00, then nothing until 16:00.
            [5, '10:00', 'Анна Лебедева', ['Стрижка женская'], 'confirmed', null],
            [5, '16:00', 'Ирина Кравцова', ['Укладка'], 'confirmed', null],

            // Two no-shows for the same person, so the attendance line has something
            // to say about her next booking.
            [-14, '11:00', 'Юлия Титова', ['Маникюр с покрытием'], 'no_show', null],
            [-21, '15:00', 'Юлия Титова', ['Коррекция бровей'], 'no_show', null],
            [7, '12:00', 'Юлия Титова', ['Маникюр с покрытием'], 'confirmed', null],
        ];

        // Measured durations, so the booking form can say what a haircut really
        // takes. Below three samples the hint stays silent by design.
        // Eight of them: enough for the duration hint, and enough completed work
        // overall for the gap cards to price an idle window.
        foreach ([-40, -37, -34, -31, -28, -25, -22, -19] as $offset) {
            $plan[] = [$offset, '10:00', 'Ольга Ким', ['Стрижка женская'], 'completed', null];
        }

        foreach ($plan as [$offset, $time, $clientName, $serviceNames, $status, $note]) {
            $client = $clients[$clientName]['user'];
            $scheduledAt = $today->copy()->addDays($offset)->setTimeFromTimeString($time);

            $payload = collect($serviceNames)->map(fn (string $name) => [
                'id' => $services[$name]->id,
                'name' => $services[$name]->name,
                'price' => (float) $services[$name]->base_price,
                'duration' => (int) $services[$name]->duration_min,
            ])->all();

            Order::updateOrCreate(
                [
                    'master_id' => $master->id,
                    'client_id' => $client->id,
                    'scheduled_at' => $scheduledAt,
                ],
                [
                    'services' => $payload,
                    'status' => $status,
                    'note' => $note,
                    'duration' => collect($payload)->sum('duration'),
                    'duration_forecast' => collect($payload)->sum('duration'),
                    'total_price' => collect($payload)->sum('price'),
                    // A real haircut runs well over the hour the price list claims.
                    'duration' => $status === 'completed' ? collect($payload)->sum('duration') + 80 : null,
                    'confirmed_at' => in_array($status, ['confirmed', 'in_progress', 'completed'], true) ? $scheduledAt->copy()->subDay() : null,
                    'cancelled_at' => $status === 'cancelled' ? $scheduledAt->copy()->subDay() : null,
                ]
            );
        }
    }

    /**
     * @param  array<string, Service>  $services
     * @param  array<string, array{user: User, card: Client}>  $clients
     */
    private function seedWaitlist(User $master, array $services, array $clients): void
    {
        $today = Carbon::today();

        $entries = [
            ['Юлия Титова', 'Окрашивание', 1, ['09:00', '13:00'], 3, 'Готова прийти в любой день на этой неделе.'],
            ['Дарья Носова', 'Стрижка женская', 1, ['15:00', '19:00'], 2, 'Только после работы.'],
            ['Марина Белова', 'Маникюр с покрытием', 3, ['10:00', '14:00'], 1, null],
        ];

        foreach ($entries as [$clientName, $serviceName, $dayOffset, $window, $priority, $notes]) {
            $card = $clients[$clientName]['card'];

            WaitlistEntry::updateOrCreate(
                [
                    'user_id' => $master->id,
                    'client_id' => $card->id,
                    'service_id' => $services[$serviceName]->id,
                ],
                [
                    'client_user_id' => $clients[$clientName]['user']->id,
                    'preferred_slots' => [],
                    'preferred_dates' => [$today->copy()->addDays($dayOffset)->toDateString()],
                    'preferred_time_windows' => [['start' => $window[0], 'end' => $window[1]]],
                    'flexibility_days' => 3,
                    'priority_manual' => $priority,
                    'status' => 'pending',
                    'source' => 'manual',
                    'notes' => $notes,
                    'expires_at' => $today->copy()->addDays(30),
                ]
            );
        }
    }
}
