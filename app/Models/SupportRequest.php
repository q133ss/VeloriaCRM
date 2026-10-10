<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A message from someone who is not signed in: a visitor who wants to be
 * called back, or a master who cannot log in. Unlike SupportTicket there is no
 * account to answer into, so the contact they left is the whole thread.
 */
class SupportRequest extends Model
{
    public const TYPE_PHONE = 'phone';
    public const TYPE_EMAIL = 'email';
    public const TYPE_TELEGRAM = 'telegram';

    protected $fillable = [
        'name',
        'contact_type',
        'contact',
        'message',
        'status',
        'ip',
        'user_agent',
    ];

    public static function contactTypes(): array
    {
        return [self::TYPE_PHONE, self::TYPE_EMAIL, self::TYPE_TELEGRAM];
    }
}
