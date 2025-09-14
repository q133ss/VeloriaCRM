<?php

namespace App\Http\Requests;

class RegisterRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => __('auth.name')]),
            'email.required' => __('validation.required', ['attribute' => __('auth.email')]),
            'email.email' => __('validation.email', ['attribute' => __('auth.email')]),
            'email.unique' => __('validation.unique', ['attribute' => __('auth.email')]),
            'password.required' => __('validation.required', ['attribute' => __('auth.password')]),
            'password.min' => __('validation.min.string', ['attribute' => __('auth.password'), 'min' => 8]),
            'password.confirmed' => __('validation.confirmed', ['attribute' => __('auth.password')]),
        ];
    }
}
