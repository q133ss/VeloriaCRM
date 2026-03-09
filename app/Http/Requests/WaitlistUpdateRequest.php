<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class WaitlistUpdateRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        $userId = $this->user('sanctum')?->id ?? 0;

        return [
            'service_id' => ['sometimes', 'integer', Rule::exists('services', 'id')->where(fn ($query) => $query->where('user_id', $userId))],
            'preferred_dates' => ['sometimes', 'array', 'min:1'],
            'preferred_dates.*' => ['date'],
            'preferred_time_windows' => ['sometimes', 'array'],
            'preferred_time_windows.*.start' => ['required_with:preferred_time_windows', 'date_format:H:i'],
            'preferred_time_windows.*.end' => ['required_with:preferred_time_windows', 'date_format:H:i'],
            'preferred_slots' => ['sometimes', 'array'],
            'preferred_slots.*' => ['string', 'max:32'],
            'flexibility_days' => ['sometimes', 'integer', 'min:0', 'max:14'],
            'priority_manual' => ['sometimes', 'integer', 'min:0', 'max:5'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'status' => ['sometimes', Rule::in(['pending', 'notified', 'booked', 'expired'])],
        ];
    }
}
