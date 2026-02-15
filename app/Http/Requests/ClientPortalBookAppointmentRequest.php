<?php

namespace App\Http\Requests;

class ClientPortalBookAppointmentRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'string', 'regex:/^\\d{2}:\\d{2}$/'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

