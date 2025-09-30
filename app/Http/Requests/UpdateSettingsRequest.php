<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
            'phone' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:255'],
            'time_format' => ['required', Rule::in(['12h','24h'])],
            'current_password' => ['nullable', 'required_with:new_password', 'current_password'],
            'new_password' => ['nullable', 'min:8', 'confirmed'],
            'notifications' => ['array'],
            'notifications.email' => ['boolean'],
            'notifications.telegram' => ['boolean'],
            'notifications.sms' => ['boolean'],
            'integrations' => ['array'],
            'integrations.smsaero.email' => ['nullable', 'email'],
            'integrations.smsaero.api_key' => ['nullable', 'string', 'max:255'],
            'integrations.smtp.host' => ['nullable', 'string', 'max:255'],
            'integrations.smtp.port' => ['nullable', 'integer', 'between:1,65535'],
            'integrations.smtp.username' => ['nullable', 'string', 'max:255'],
            'integrations.smtp.password' => ['nullable', 'string', 'max:255'],
            'integrations.smtp.encryption' => ['nullable', 'string', Rule::in(['tls', 'ssl', 'starttls', 'none'])],
            'integrations.smtp.from_address' => ['nullable', 'email'],
            'integrations.smtp.from_name' => ['nullable', 'string', 'max:255'],
            'integrations.whatsapp.api_key' => ['nullable', 'string', 'max:255'],
            'integrations.whatsapp.sender' => ['nullable', 'string', 'max:255'],
            'integrations.telegram.bot_token' => ['nullable', 'string', 'max:255'],
            'integrations.telegram.sender' => ['nullable', 'string', 'max:255'],
            'integrations.yookassa.shop_id' => ['nullable', 'string', 'max:255'],
            'integrations.yookassa.secret_key' => ['nullable', 'string', 'max:255'],
            'work_days' => ['array'],
            'work_hours' => ['array'],
            'holidays' => ['array'],
            'holidays.*' => ['date'],
            'address' => ['nullable', 'string', 'max:255'],
            'map_point' => ['nullable', 'array'],
            'map_point.lat' => ['nullable', 'numeric'],
            'map_point.lng' => ['nullable', 'numeric'],
            'reminder_message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
