<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientMagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $link,
        public readonly int $expiresMinutes,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject(__('client_portal.email.magic_link_subject'))
            ->view('emails.client_magic_link');
    }
}
