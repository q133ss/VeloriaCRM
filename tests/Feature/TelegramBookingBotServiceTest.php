<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\Telegram\TelegramBookingBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TelegramBookingBotServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_callback_creates_order_and_master_notification(): void
    {
        Queue::fake();

        $master = User::factory()->create([
            'timezone' => 'Europe/Moscow',
        ]);

        $service = Service::query()->create([
            'user_id' => $master->id,
            'name' => 'Маникюр',
            'duration_min' => 60,
            'base_price' => 2500,
            'cost' => 800,
        ]);

        $token = 'test-bot-token';
        Setting::query()->create([
            'user_id' => $master->id,
            'telegram_bot_token' => $token,
            'work_hours' => [
                'mon' => ['10:00', '11:00', '12:00'],
                'tue' => ['10:00', '11:00', '12:00'],
                'wed' => ['10:00', '11:00', '12:00'],
                'thu' => ['10:00', '11:00', '12:00'],
                'fri' => ['10:00', '11:00', '12:00'],
                'sat' => ['10:00', '11:00', '12:00'],
                'sun' => ['10:00', '11:00', '12:00'],
            ],
            'notification_prefs' => [
                'email' => false,
                'sms' => false,
                'telegram' => false,
            ],
        ]);

        $sessionKey = 'telegram:booking:session:' . hash('sha256', $token) . ':10001';
        $bookingDate = Carbon::now('Europe/Moscow')->addDays(2)->format('Y-m-d');

        Cache::put($sessionKey, [
            'chat_id' => '10001',
            'master_id' => $master->id,
            'service_id' => $service->id,
            'date' => $bookingDate,
            'time' => '10:00',
            'client' => [
                'id' => 10001,
                'name' => 'Telegram Client',
            ],
        ], now()->addHours(2));

        Http::fake([
            'https://api.telegram.org/bot' . $token . '/getUpdates' => Http::response([
                'ok' => true,
                'result' => [
                    [
                        'update_id' => 701,
                        'callback_query' => [
                            'id' => 'cq-1',
                            'data' => 'tgbook:confirm',
                            'from' => [
                                'id' => 10001,
                                'first_name' => 'Telegram',
                                'last_name' => 'Client',
                            ],
                            'message' => [
                                'chat' => [
                                    'id' => 10001,
                                ],
                            ],
                        ],
                    ],
                    [
                        'update_id' => 702,
                        'message' => [
                            'message_id' => 2,
                            'from' => [
                                'id' => 10001,
                                'first_name' => 'Telegram',
                                'last_name' => 'Client',
                            ],
                            'chat' => [
                                'id' => 10001,
                            ],
                            'contact' => [
                                'phone_number' => '+7 (999) 123-45-67',
                                'user_id' => 10001,
                            ],
                        ],
                    ],
                ],
            ], 200),
            'https://api.telegram.org/bot' . $token . '/answerCallbackQuery' => Http::response(['ok' => true], 200),
            'https://api.telegram.org/bot' . $token . '/sendMessage' => Http::response(['ok' => true], 200),
        ]);

        app(TelegramBookingBotService::class)->pollOnce(0);

        $this->assertDatabaseHas('orders', [
            'master_id' => $master->id,
            'source' => 'telegram_bot',
            'status' => 'new',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $master->id,
            'title' => __('telegram.notifications.order_title'),
        ]);
    }
}
