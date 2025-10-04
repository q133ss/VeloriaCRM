<?php

namespace App\Services\Marketing;

use App\Models\Setting;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class MarketingChannelSender
{
    public function __construct(private readonly MailManager $mailManager)
    {
    }

    /**
     * @throws RuntimeException
     */
    public function send(Setting $settings, string $channel, string $recipient, ?string $subject, string $content): void
    {
        switch ($channel) {
            case 'email':
                $this->sendEmail($settings, $recipient, $subject, $content);
                break;
            case 'sms':
                $this->sendSms($settings, $recipient, $content);
                break;
            case 'whatsapp':
                $this->sendWhatsapp($settings, $recipient, $content);
                break;
            default:
                throw new RuntimeException(sprintf('Unsupported channel [%s]', $channel));
        }
    }

    protected function sendEmail(Setting $settings, string $recipient, ?string $subject, string $content): void
    {
        $mailerName = 'marketing_campaign_'.$settings->user_id;

        $this->mailManager->extend($mailerName, function () use ($settings) {
            return $this->mailManager->createTransport('smtp', [
                'host' => $settings->smtp_host,
                'port' => $settings->smtp_port,
                'username' => $settings->smtp_username,
                'password' => $settings->smtp_password,
                'encryption' => $settings->smtp_encryption ?? null,
                'timeout' => config('mail.mailers.smtp.timeout'),
                'auth_mode' => null,
            ]);
        });

        $fromAddress = $settings->smtp_from_address ?? $settings->smtp_username;
        $fromName = $settings->smtp_from_name ?? config('app.name');
        $subject = $subject ?: __('marketing.campaigns.default_subject');

        $sent = Mail::mailer($mailerName)->raw($content, function ($message) use ($recipient, $subject, $fromAddress, $fromName) {
            $message->to($recipient)->subject($subject);

            if ($fromAddress) {
                $message->from($fromAddress, $fromName ?: $fromAddress);
            }
        });

        if (! $sent instanceof SentMessage) {
            throw new RuntimeException('Failed to send email campaign message.');
        }
    }

    protected function sendSms(Setting $settings, string $recipient, string $content): void
    {
        $text = Str::limit($content, 700);
        $response = Http::withBasicAuth((string) $settings->smsaero_email, (string) $settings->smsaero_api_key)
            ->asForm()
            ->post('https://gate.smsaero.ru/v2/sms/send', [
                'number' => ltrim($recipient, '+'),
                'text' => $text,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('SmsAero request failed: '.$response->body());
        }

        $json = $response->json();
        if (! Arr::get($json, 'success')) {
            throw new RuntimeException('SmsAero responded with error.');
        }
    }

    protected function sendWhatsapp(Setting $settings, string $recipient, string $content): void
    {
        $endpoint = sprintf('https://graph.facebook.com/v17.0/%s/messages', $settings->whatsapp_sender);
        $response = Http::withToken((string) $settings->whatsapp_api_key)
            ->post($endpoint, [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($recipient, '+'),
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $content,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('WhatsApp request failed: '.$response->body());
        }

        if (! Arr::get($response->json(), 'messages')) {
            throw new RuntimeException('WhatsApp API did not confirm message delivery.');
        }
    }
}
