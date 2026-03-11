<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LandingPublicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['required', 'string', 'max:32'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'service_id' => ['nullable', 'integer'],
            'preferred_date' => ['nullable', 'date'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
