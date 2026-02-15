<?php

namespace App\Http\Requests;

class ClientPortalServicesRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}

