<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ReminderService
{
    public function send(string $channel, string $text, Client $client, ?Setting $settings = null): void
    {
        $channel = strtolower($channel);

        if ($channel === 'sms') {
            $this->assertSmsConfigured($settings);
            $this->sendSms($text, $client, $settings);

            return;
        }

        if ($channel === 'whatsapp') {
            $this->assertWhatsappConfigured($settings);
            $this->sendWhatsapp($text, $client, $settings);

            return;
        }

        throw new RuntimeException('Неподдерживаемый канал напоминания.');
    }

    public function isChannelConfigured(string $channel, ?Setting $settings): bool
    {
        return match (strtolower($channel)) {
            'sms' => $this->isSmsConfigured($settings),
            'whatsapp' => $this->isWhatsappConfigured($settings),
            default => false,
        };
    }

    public function isSmsConfigured(?Setting $settings): bool
    {
        return $settings !== null
            && filled($settings->smsaero_email)
            && filled($settings->smsaero_api_key);
    }

    public function isWhatsappConfigured(?Setting $settings): bool
    {
        return $settings !== null
            && filled($settings->whatsapp_api_key)
            && filled($settings->whatsapp_sender);
    }

    protected function assertSmsConfigured(?Setting $settings): void
    {
        if (! $this->isSmsConfigured($settings)) {
            throw new RuntimeException('Укажите API для SMS в настройках.');
        }
    }

    protected function assertWhatsappConfigured(?Setting $settings): void
    {
        if (! $this->isWhatsappConfigured($settings)) {
            throw new RuntimeException('Укажите API для WhatsApp в настройках.');
        }
    }

    protected function sendSms(string $text, Client $client, Setting $settings): void
    {
        if (! filled($client->phone)) {
            throw new RuntimeException('У клиента отсутствует номер телефона для SMS.');
        }

        $endpoint = rtrim(config('services.smsaero.endpoint', 'https://gate.smsaero.ru/v2'), '/');
        $sign = config('services.smsaero.sign');

        if (empty($sign)) {
            Log::info('Отправка SMS-напоминания пропущена (не указан sign).', [
                'client_id' => $client->id,
                'phone' => $client->phone,
            ]);

            return;
        }

        $response = Http::withBasicAuth($settings->smsaero_email, $settings->smsaero_api_key)
            ->asJson()
            ->post($endpoint.'/sms/send', [
                'number' => $client->phone,
                'text' => $text,
                'sign' => $sign,
            ]);

        if ($response->failed()) {
            $message = data_get($response->json(), 'message');
            $details = $message ?: $response->body();

            Log::warning('Не удалось отправить SMS-напоминание.', [
                'client_id' => $client->id,
                'response' => $details,
            ]);

            throw new RuntimeException($message ?: 'Не удалось отправить SMS.');
        }
    }

    protected function sendWhatsapp(string $text, Client $client, Setting $settings): void
    {
        if (! filled($client->phone)) {
            throw new RuntimeException('У клиента отсутствует номер телефона для WhatsApp.');
        }

        $endpoint = config('services.whatsapp_reminders.endpoint');

        if (empty($endpoint)) {
            Log::info('Отправка WhatsApp-напоминания пропущена (не указан endpoint).', [
                'client_id' => $client->id,
                'phone' => $client->phone,
            ]);

            return;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$settings->whatsapp_api_key,
            'Accept' => 'application/json',
        ])->post($endpoint, [
            'to' => $client->phone,
            'from' => $settings->whatsapp_sender,
            'text' => $text,
        ]);

        if ($response->failed()) {
            $message = data_get($response->json(), 'message');
            $details = $message ?: $response->body();

            Log::warning('Не удалось отправить WhatsApp-напоминание.', [
                'client_id' => $client->id,
                'response' => $details,
            ]);

            throw new RuntimeException($message ?: 'Не удалось отправить сообщение в WhatsApp.');
        }
    }
}

