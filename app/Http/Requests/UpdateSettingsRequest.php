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
            'current_password' => ['required_with:new_password', 'current_password'],
            'new_password' => ['nullable', 'min:8', 'confirmed'],
            'notifications' => ['array'],
            'notifications.email' => ['boolean'],
            'notifications.telegram' => ['boolean'],
            'notifications.sms' => ['boolean'],
            'integrations' => ['array'],
            'integrations.smsaero.email' => ['nullable', 'email'],
            'integrations.smsaero.api_key' => ['nullable', 'string', 'max:255'],
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
        ];
    }
}
