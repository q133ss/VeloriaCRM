<?php

namespace App\Http\Requests;

class ClientPortalWaitlistRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer'],
            'preferred_dates' => ['required', 'array', 'min:1'],
            'preferred_dates.*' => ['date'],
            'preferred_time_windows' => ['nullable', 'array'],
            'preferred_time_windows.*.start' => ['required_with:preferred_time_windows', 'date_format:H:i'],
            'preferred_time_windows.*.end' => ['required_with:preferred_time_windows', 'date_format:H:i'],
            'flexibility_days' => ['nullable', 'integer', 'min:0', 'max:14'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
