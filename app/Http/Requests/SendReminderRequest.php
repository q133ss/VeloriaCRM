<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel' => ['required', 'string', 'in:sms,whatsapp'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}

