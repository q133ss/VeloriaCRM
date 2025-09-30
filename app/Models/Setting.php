<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_days',
        'work_hours',
        'cancel_policy',
        'deposit_policy',
        'notification_prefs',
        'branding',
        'address',
        'map_point',
        'smsaero_email',
        'smsaero_api_key',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'smtp_from_address',
        'smtp_from_name',
        'whatsapp_api_key',
        'whatsapp_sender',
        'telegram_bot_token',
        'telegram_sender',
        'yookassa_shop_id',
        'yookassa_secret_key',
        'reminder_message',
    ];

    protected function casts(): array
    {
        return [
            'work_days' => 'array',
            'work_hours' => 'array',
            'cancel_policy' => 'array',
            'deposit_policy' => 'array',
            'notification_prefs' => 'array',
            'branding' => 'array',
            'map_point' => 'array',
            'smtp_port' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
