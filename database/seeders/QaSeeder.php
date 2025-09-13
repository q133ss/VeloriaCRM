<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\MessageTemplate;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanUser;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class QaSeeder extends Seeder
{
    /**
     * Run the database seeds for QA environment.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'user@email.net'],
            [
                'name' => 'QA Manager',
                'password' => 'password',
                'phone' => '+79991112233',
            ]
        );

        $planId = Plan::where('name', 'pro')->value('id');
        if ($planId) {
            PlanUser::updateOrCreate(
                ['user_id' => $user->id],
                ['plan_id' => $planId, 'ends_at' => now()->addMonth()]
            );
        }

        $anna = Client::updateOrCreate(
            ['user_id' => $user->id, 'phone' => '+79990001122'],
            [
                'name' => 'Анна Иванова',
                'email' => 'anna@example.com',
                'birthday' => '1990-03-12',
                'tags' => ['VIP', 'hair'],
                'allergies' => ['pollen'],
                'preferences' => ['tea' => 'green', 'music' => 'jazz'],
                'notes' => 'Предпочитает утренние визиты.',
                'last_visit_at' => now()->subDays(10),
                'loyalty_level' => 'gold',
            ]
        );

        $ivan = Client::updateOrCreate(
            ['user_id' => $user->id, 'phone' => '+79990002233'],
            [
                'name' => 'Иван Петров',
                'email' => 'ivan@example.com',
                'birthday' => '1985-07-22',
                'tags' => ['regular'],
                'allergies' => [],
                'preferences' => ['coffee' => 'black'],
                'notes' => 'Любит тихую музыку.',
                'last_visit_at' => now()->subDays(30),
                'loyalty_level' => 'silver',
            ]
        );

        $hair = ServiceCategory::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Парикмахерские услуги'],
            []
        );

        $nails = ServiceCategory::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Маникюр'],
            []
        );

        $styling = Service::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Укладка'],
            [
                'category_id' => $hair->id,
                'base_price' => 800,
                'cost' => 300,
                'duration_min' => 30,
                'upsell_suggestions' => [],
            ]
        );

        $coloring = Service::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Окрашивание'],
            [
                'category_id' => $hair->id,
                'base_price' => 2500,
                'cost' => 1000,
                'duration_min' => 120,
                'upsell_suggestions' => [],
            ]
        );

        $gel = Service::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Покрытие гель-лаком'],
            [
                'category_id' => $nails->id,
                'base_price' => 700,
                'cost' => 200,
                'duration_min' => 40,
                'upsell_suggestions' => [],
            ]
        );

        $cut = Service::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Стрижка женская'],
            [
                'category_id' => $hair->id,
                'base_price' => 1500,
                'cost' => 500,
                'duration_min' => 60,
                'upsell_suggestions' => [$styling->id, $coloring->id],
            ]
        );

        $manicure = Service::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Классический маникюр'],
            [
                'category_id' => $nails->id,
                'base_price' => 1200,
                'cost' => 400,
                'duration_min' => 50,
                'upsell_suggestions' => [$gel->id],
            ]
        );

        MessageTemplate::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'reminder', 'channel' => 'sms'],
            [
                'content' => 'Здравствуйте, {{name}}! Напоминаем о записи {{date}} в {{time}}.',
                'variables' => ['name', 'date', 'time'],
            ]
        );

        MessageTemplate::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'thanks', 'channel' => 'email'],
            [
                'content' => 'Спасибо, {{name}}, что посетили нас. Будем рады видеть вас снова!',
                'variables' => ['name'],
            ]
        );

        Setting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'work_days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
                'work_hours' => [
                    'mon' => ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'],
                    'tue' => ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'],
                    'wed' => ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'],
                    'thu' => ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'],
                    'fri' => ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'],
                ],
                'cancel_policy' => ['hours' => 24],
                'deposit_policy' => ['enabled' => true, 'percent' => 20],
                'notification_prefs' => ['sms' => true, 'email' => true, 'telegram' => false],
                'branding' => ['color' => '#FF5733'],
                'address' => 'Москва, Тверская ул., 1',
                'map_point' => ['lat' => 55.7558, 'lng' => 37.6173],
            ]
        );

        Payment::updateOrCreate(
            ['provider_payment_id' => 'pay_0001'],
            [
                'user_id' => $user->id,
                'client_id' => $anna->id,
                'provider' => 'yookassa',
                'amount' => 1500,
                'status' => 'succeeded',
                'metadata' => ['service' => 'Стрижка женская'],
                'paid_at' => now()->subDays(5),
            ]
        );

        Payment::updateOrCreate(
            ['provider_payment_id' => 'pay_0002'],
            [
                'user_id' => $user->id,
                'client_id' => $ivan->id,
                'provider' => 'yookassa',
                'amount' => 1200,
                'status' => 'succeeded',
                'metadata' => ['service' => 'Классический маникюр'],
                'paid_at' => now()->subDays(15),
            ]
        );

        $startAnna = now()->startOfDay()->addDays(2)->setTime(10, 0);
        Appointment::updateOrCreate(
            ['user_id' => $user->id, 'client_id' => $anna->id, 'starts_at' => $startAnna],
            [
                'service_ids' => [$cut->id],
                'ends_at' => $startAnna->copy()->addHour(),
                'status' => 'confirmed',
                'deposit_amount' => 300,
                'risk_no_show' => 0.1,
                'fit_score' => 0.8,
            ]
        );

        $annaPast = now()->subDays(10)->setTime(11, 0);
        Appointment::updateOrCreate(
            ['user_id' => $user->id, 'client_id' => $anna->id, 'starts_at' => $annaPast],
            [
                'service_ids' => [$cut->id],
                'ends_at' => $annaPast->copy()->addHour(),
                'status' => 'completed',
                'deposit_amount' => 300,
                'risk_no_show' => 0.05,
                'fit_score' => 0.9,
            ]
        );

        $startIvan = now()->startOfDay()->addDays(3)->setTime(14, 0);
        Appointment::updateOrCreate(
            ['user_id' => $user->id, 'client_id' => $ivan->id, 'starts_at' => $startIvan],
            [
                'service_ids' => [$manicure->id],
                'ends_at' => $startIvan->copy()->addMinutes(50),
                'status' => 'scheduled',
                'deposit_amount' => 0,
                'risk_no_show' => 0.2,
                'fit_score' => 0.7,
            ]
        );

        $ivanPast = now()->subDays(30)->setTime(15, 0);
        Appointment::updateOrCreate(
            ['user_id' => $user->id, 'client_id' => $ivan->id, 'starts_at' => $ivanPast],
            [
                'service_ids' => [$manicure->id],
                'ends_at' => $ivanPast->copy()->addMinutes(50),
                'status' => 'completed',
                'deposit_amount' => 0,
                'risk_no_show' => 0.15,
                'fit_score' => 0.75,
            ]
        );
    }
}
