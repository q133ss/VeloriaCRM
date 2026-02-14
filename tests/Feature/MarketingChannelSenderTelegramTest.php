<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\Marketing\MarketingChannelSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketingChannelSenderTelegramTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_telegram_channel_uses_bot_api(): void
    {
        $user = User::factory()->create();

        $settings = Setting::query()->create([
            'user_id' => $user->id,
            'telegram_bot_token' => 'telegram-token-123',
        ]);

        Http::fake([
            'https://api.telegram.org/bottelegram-token-123/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 10],
            ], 200),
        ]);

        app(MarketingChannelSender::class)->send(
            $settings,
            'telegram',
            '123456789',
            null,
            'Test message'
        );

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://api.telegram.org/bottelegram-token-123/sendMessage') {
                return false;
            }

            $data = $request->data();

            return ($data['chat_id'] ?? null) === '123456789'
                && ($data['text'] ?? null) === 'Test message';
        });
    }
}
