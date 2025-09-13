<?php

namespace App\Http\Requests;

class LoginRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => __('validation.required', ['attribute' => __('auth.email')]),
            'email.email' => __('validation.email', ['attribute' => __('auth.email')]),
            'password.required' => __('validation.required', ['attribute' => __('auth.password')]),
        ];
    }
}
