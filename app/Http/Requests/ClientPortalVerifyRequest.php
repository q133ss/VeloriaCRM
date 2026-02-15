<?php

namespace App\Http\Requests;

class ClientPortalVerifyRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verification_id' => ['required', 'string', 'uuid'],
            'code' => ['required', 'string', 'regex:/^\\d{6}$/'],
        ];
    }
}

