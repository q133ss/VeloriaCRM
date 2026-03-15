<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ClientPortalBookAppointmentRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $masterId = (int) ($this->user()?->user_id ?? 0);

        return [
            'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where(fn ($query) => $query->where('user_id', $masterId)),
            ],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'string', 'regex:/^\\d{2}:\\d{2}$/'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
