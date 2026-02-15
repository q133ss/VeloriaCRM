<?php

namespace App\Http\Requests;

class ClientPortalLoginRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'master_id' => ['required', 'integer', 'exists:users,id'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }
}
