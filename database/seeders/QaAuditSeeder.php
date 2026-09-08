<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Landing;
use App\Models\MarketingCampaign;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanUser;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Services\ClientIdentityService;
use App\Services\ScheduleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * A stand for the production-readiness audit.
 *
 * The demo data that shipped with the repo sits almost entirely on the admin
 * account, so a normal master logs in to empty screens and half the product is
 * never exercised. This seeder builds four masters that differ only in the one
 * axis the product gates on — the plan — plus a support-role admin, and gives
 * three of them a book deep enough for lists, filters, charts and segments to
 * have something to say.
 *
 * Everything is addressed by email and rebuilt in place, so the seeder can be
 * run repeatedly without piling up duplicates.
 */
class QaAuditSeeder extends Seeder
{
    private const PASSWORD = 'password';

    /** Statuses in the order a real book fills up with them. */
    private const STATUS_MIX = [
        'completed', 'completed', 'completed', 'completed',
        'confirmed', 'confirmed',
        'cancelled', 'no_show', 'new',
    ];

    public function run(): void
    {
        $identities = app(ClientIdentityService::class);
        $schedule = app(ScheduleService::class);

        $lite = $this->master('qa.lite@veloria.test', 'Лена Лайт', '+79200000001', 'lite');
        $pro = $this->master('qa.pro@veloria.test', 'Полина Про', '+79200000002', 'pro');
        $elite = $this->master('qa.elite@veloria.test', 'Элина Элит', '+79200000003', 'elite');

        // Deliberately left bare: onboarding, first-run wizard and every empty
        // state are read off this account.
        $this->master('qa.empty@veloria.test', 'Настя Новичок', '+79200000004', null);

        foreach ([$lite, $pro, $elite] as $master) {
            $this->schedule($master, $schedule);
            $services = $this->catalog($master);
            $clients = $this->clients($master, $identities);
            $this->orders($master, $clients, $services);
            $this->payments($master, $clients);
            $this->waitlist($master, $clients, $services);
        }

        // Paid-plan modules only exist for pro and elite; lite is the control
        // group that must NOT see them.
        foreach ([$pro, $elite] as $master) {
            $this->landings($master);
            $this->marketing($master);
        }

        $this->supportAdmin();
    }

    private function master(string $email, string $name, string $phone, ?string $plan): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => self::PASSWORD,
                'phone' => $phone,
                'timezone' => 'Europe/Moscow',
                'time_format' => '24h',
                'is_admin' => false,
                'status' => User::STATUS_ACTIVE,
            ],
        );

        PlanUser::where('user_id', $user->id)->delete();

        if ($plan) {
            $planId = Plan::where('name', $plan)->value('id');

            if ($planId) {
                PlanUser::create([
                    'user_id' => $user->id,
                    'plan_id' => $planId,
                    'ends_at' => Carbon::now()->addMonth(),
                ]);
            }
        }

        return $user;
    }

    private function supportAdmin(): void
    {
        User::updateOrCreate(
            ['email' => 'qa.support@veloria.test'],
            [
                'name' => 'Саша Саппорт',
                'password' => self::PASSWORD,
                'phone' => '+79200000005',
                'is_admin' => true,
                'admin_role' => User::ADMIN_ROLE_SUPPORT,
                'status' => User::STATUS_ACTIVE,
            ],
        );
    }

    private function schedule(User $master, ScheduleService $schedule): void
    {
        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $slots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

        $workHours = [];
        foreach ($days as $day) {
            $workHours[$day] = $day === 'sat' ? array_slice($slots, 0, 5) : $slots;
        }

        $rules = $schedule->normalizeRules(null, $days, $workHours);
        $legacy = $schedule->deriveLegacyFields($rules);

        Setting::updateOrCreate(
            ['user_id' => $master->id],
            [
                'schedule_rules' => $rules,
                'work_days' => $legacy['work_days'],
                'work_hours' => $legacy['work_hours'],
                'notification_prefs' => ['sms' => true, 'email' => true, 'telegram' => false],
                'address' => 'Москва, Тверская ул., 1',
                'reminder_message' => 'Здравствуйте! Напоминаем о записи завтра.',
            ],
        );
    }

    /**
     * @return array<int, Service>
     */
    private function catalog(User $master): array
    {
        $categories = [];
        foreach (['Волосы', 'Ногти', 'Уход'] as $name) {
            $categories[$name] = ServiceCategory::updateOrCreate(
                ['user_id' => $master->id, 'name' => $name],
                [],
            );
        }

        $definitions = [
            ['Стрижка женская', 'Волосы', 2000, 700, 60],
            ['Окрашивание в один тон', 'Волосы', 4500, 1800, 120],
            ['Укладка', 'Волосы', 1200, 300, 45],
            ['Маникюр классический', 'Ногти', 1500, 400, 60],
            ['Покрытие гель-лаком', 'Ногти', 1800, 500, 90],
            ['Педикюр', 'Ногти', 2200, 600, 90],
            ['Чистка лица', 'Уход', 3500, 1200, 90],
            ['Массаж лица', 'Уход', 2500, 600, 45],
        ];

        $services = [];
        foreach ($definitions as [$name, $category, $price, $cost, $duration]) {
            $services[] = Service::updateOrCreate(
                ['user_id' => $master->id, 'name' => $name],
                [
                    'category_id' => $categories[$category]->id,
                    'base_price' => $price,
                    'cost' => $cost,
                    'duration_min' => $duration,
                    'upsell_suggestions' => [],
                ],
            );
        }

        return $services;
    }

    /**
     * @return array<int, array{user: User, card: Client}>
     */
    private function clients(User $master, ClientIdentityService $identities): array
    {
        $names = [
            'Анна Крылова', 'Мария Гусева', 'Ольга Панина', 'Дарья Седова', 'Ирина Лапина',
            'Екатерина Жукова', 'Светлана Тихая', 'Наталья Юдина', 'Полина Ершова', 'Алина Рогова',
            'Вера Соловьёва', 'Юлия Маркова', 'Ксения Балашова', 'Татьяна Ким', 'Людмила Орлова',
        ];

        $tags = [['VIP'], ['постоянный'], [], ['новый'], ['VIP', 'постоянный']];
        $levels = ['gold', 'silver', 'bronze', null];

        $seed = crc32($master->email) % 1000;
        $clients = [];

        foreach ($names as $index => $name) {
            // Phones are unique per master so the two accounts never collide on
            // the same person and hide a real duplicate-client defect.
            $phone = sprintf('+79%09d', 100000000 + $seed * 100 + $index);

            $pair = $identities->resolve(
                $master->id,
                $phone,
                $name,
                sprintf('client%d.%s', $index, str_replace('@', '.at.', $master->email)),
                [
                    'birthday' => Carbon::now()->subYears(20 + $index)->subDays($index * 11)->toDateString(),
                    'tags' => $tags[$index % count($tags)],
                    'allergies' => $index % 4 === 0 ? ['аммиак'] : [],
                    'preferences' => ['чай' => $index % 2 ? 'зелёный' : 'чёрный'],
                    'notes' => $index % 3 === 0 ? 'Просит записывать только на утро.' : null,
                    'loyalty_level' => $levels[$index % count($levels)],
                ],
            );

            $clients[] = $pair;
        }

        return $clients;
    }

    /**
     * A book spread over five months: four back, one forward, so period filters,
     * comparisons with the previous period and the «sleeping clients» segment
     * all have something to chew on.
     *
     * @param  array<int, array{user: User, card: Client}>  $clients
     * @param  array<int, Service>  $services
     */
    private function orders(User $master, array $clients, array $services): void
    {
        Order::where('master_id', $master->id)->delete();

        $now = Carbon::now();
        $rows = [];

        for ($i = 0; $i < 90; $i++) {
            $pair = $clients[$i % count($clients)];
            $service = $services[$i % count($services)];
            $second = $i % 5 === 0 ? $services[($i + 3) % count($services)] : null;

            // Days run from 120 back to 20 forward.
            $offset = 120 - (int) round($i * (140 / 90));
            $scheduled = $now->copy()->subDays($offset)->startOfDay()->addHours(9 + ($i % 9));

            if (in_array((int) $scheduled->dayOfWeek, [0], true)) {
                $scheduled->addDay();
            }

            $status = $scheduled->isFuture()
                ? (['new', 'confirmed'])[$i % 2]
                : self::STATUS_MIX[$i % count(self::STATUS_MIX)];

            $items = [[
                'id' => $service->id,
                'name' => $service->name,
                'price' => (float) $service->base_price,
                'duration' => $service->duration_min,
            ]];

            if ($second) {
                $items[] = [
                    'id' => $second->id,
                    'name' => $second->name,
                    'price' => (float) $second->base_price,
                    'duration' => $second->duration_min,
                ];
            }

            $duration = array_sum(array_column($items, 'duration'));
            $total = array_sum(array_column($items, 'price'));

            $rows[] = [
                'master_id' => $master->id,
                'client_id' => $pair['user']->id,
                'services' => json_encode($items, JSON_UNESCAPED_UNICODE),
                'scheduled_at' => $scheduled,
                'duration' => $duration,
                'total_price' => $total,
                'status' => $status,
                'note' => $i % 7 === 0 ? 'Просила не опаздывать, у неё поезд.' : null,
                'source' => 'manual',
                'prepaid_amount' => 0,
                'reschedule_count' => $i % 11 === 0 ? 1 : 0,
                'is_reminder_sent' => false,
                'cancellation_reason' => $status === 'cancelled' ? 'Клиент перенёс на другой месяц.' : null,
                'cancelled_at' => $status === 'cancelled' ? $scheduled->copy()->subDay() : null,
                'confirmed_at' => in_array($status, ['confirmed', 'completed'], true) ? $scheduled->copy()->subDay() : null,
                'actual_started_at' => $status === 'completed' ? $scheduled->copy() : null,
                'actual_finished_at' => $status === 'completed' ? $scheduled->copy()->addMinutes($duration) : null,
                'created_at' => $scheduled->copy()->subDays(3),
                'updated_at' => $scheduled->copy()->subDays(3),
            ];
        }

        foreach (array_chunk($rows, 50) as $chunk) {
            Order::insert($chunk);
        }

        // Keeps the client cards honest: the list and the card both show a last
        // visit, and «sleeping» is computed off it.
        foreach ($clients as $pair) {
            $last = Order::where('master_id', $master->id)
                ->where('client_id', $pair['user']->id)
                ->where('status', 'completed')
                ->max('scheduled_at');

            if ($last) {
                $pair['card']->forceFill(['last_visit_at' => $last])->save();
            }
        }
    }

    /**
     * @param  array<int, array{user: User, card: Client}>  $clients
     */
    private function payments(User $master, array $clients): void
    {
        Payment::where('user_id', $master->id)->delete();

        foreach ([0, 1, 2, 3] as $i) {
            Payment::create([
                'user_id' => $master->id,
                'client_id' => $clients[$i]['card']->id,
                'provider' => 'yookassa',
                'provider_payment_id' => sprintf('qa_%d_%d', $master->id, $i),
                'amount' => [1500, 2400, 900, 3200][$i],
                'status' => $i === 3 ? 'refunded' : 'succeeded',
                // The retail branch of the revenue split is only reachable
                // through this metadata key.
                'metadata' => $i === 2
                    ? ['type' => 'retail', 'service' => 'Шампунь домой']
                    : ['service' => 'Стрижка женская'],
                'paid_at' => Carbon::now()->subDays(5 + $i * 9),
            ]);
        }
    }

    /**
     * @param  array<int, array{user: User, card: Client}>  $clients
     * @param  array<int, Service>  $services
     */
    private function waitlist(User $master, array $clients, array $services): void
    {
        WaitlistEntry::where('user_id', $master->id)->delete();

        foreach ([0, 1, 2] as $i) {
            WaitlistEntry::create([
                'user_id' => $master->id,
                'client_id' => $clients[$i + 5]['card']->id,
                'client_user_id' => $clients[$i + 5]['user']->id,
                'service_id' => $services[$i]->id,
                'preferred_slots' => [],
                'preferred_dates' => [
                    Carbon::now()->addDays($i + 1)->toDateString(),
                    Carbon::now()->addDays($i + 4)->toDateString(),
                ],
                'preferred_time_windows' => [['from' => '10:00', 'to' => '14:00']],
                'flexibility_days' => 2,
                'priority' => $i,
                'status' => 'pending',
                'source' => 'manual',
                'notes' => $i === 0 ? 'Готова прийти в любое окно на этой неделе.' : null,
            ]);
        }
    }

    private function landings(User $master): void
    {
        Landing::where('user_id', $master->id)->delete();

        $slugPrefix = str_contains($master->email, 'elite') ? 'qa-elite' : 'qa-pro';

        Landing::create([
            'user_id' => $master->id,
            'title' => 'Запись на стрижку',
            'type' => 'service',
            'landing' => 'landings.templates.service',
            'slug' => $slugPrefix . '-service',
            'settings' => ['show_all_services' => false, 'service_ids' => []],
            'is_active' => true,
            'views' => 42,
        ]);

        // Draft on purpose: `?preview=1` skips the is_active check, and this is
        // the page that proves whether it also skips ownership.
        Landing::create([
            'user_id' => $master->id,
            'title' => 'Черновик акции',
            'type' => 'promotion',
            'landing' => 'landings.templates.promotion',
            'slug' => $slugPrefix . '-draft',
            'settings' => ['show_all_services' => true],
            'is_active' => false,
            'views' => 0,
        ]);
    }

    private function marketing(User $master): void
    {
        MarketingCampaign::where('user_id', $master->id)->delete();
        Promotion::where('user_id', $master->id)->delete();

        MarketingCampaign::create([
            'user_id' => $master->id,
            'name' => 'Возвращаем спящих',
            'channel' => 'email',
            'segment' => 'sleeping',
            'status' => 'draft',
            'subject' => 'Давно вас не видели',
            'content' => 'Здравствуйте! Возвращайтесь, для вас скидка 15%.',
        ]);

        MarketingCampaign::create([
            'user_id' => $master->id,
            'name' => 'Осенняя рассылка',
            'channel' => 'sms',
            'segment' => 'all',
            'status' => 'sent',
            'content' => 'Осенние окна открыты, записывайтесь.',
            'delivered_count' => 12,
            'read_count' => 7,
            'click_count' => 3,
        ]);

        Promotion::create([
            'user_id' => $master->id,
            'name' => 'Скидка новым',
            'type' => 'order_percent',
            'percent' => 15,
            'promo_code' => strtoupper($slug = substr(md5($master->email), 0, 6)),
            'starts_at' => Carbon::now()->subWeek(),
            'ends_at' => Carbon::now()->addMonth(),
            'usage_limit' => 50,
            'usage_count' => 4,
        ]);
    }
}
