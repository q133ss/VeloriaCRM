<?php

namespace App\Http\Requests;

class ClientPortalDeviceTokenRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expo_push_token' => ['required', 'string', 'max:255'],
        ];
    }
}
