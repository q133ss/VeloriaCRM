<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateIntegrationsRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'integrations' => ['required', 'array'],
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
        ];
    }
}
