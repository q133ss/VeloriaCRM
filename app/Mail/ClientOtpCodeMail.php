<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientOtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly int $expiresMinutes,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject(__('client_portal.email.otp_subject'))
            ->view('emails.client_otp_code');
    }
}

