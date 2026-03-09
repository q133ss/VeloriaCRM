<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class WaitlistStoreRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        $userId = $this->user('sanctum')?->id ?? 0;

        return [
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')->where(fn ($query) => $query->where('user_id', $userId))],
            'client_phone' => ['nullable', 'required_without:client_id', 'string', 'max:20'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where(fn ($query) => $query->where('user_id', $userId))],
            'preferred_dates' => ['required', 'array', 'min:1'],
            'preferred_dates.*' => ['date'],
            'preferred_time_windows' => ['nullable', 'array'],
            'preferred_time_windows.*.start' => ['required_with:preferred_time_windows', 'date_format:H:i'],
            'preferred_time_windows.*.end' => ['required_with:preferred_time_windows', 'date_format:H:i'],
            'preferred_slots' => ['nullable', 'array'],
            'preferred_slots.*' => ['string', 'max:32'],
            'flexibility_days' => ['nullable', 'integer', 'min:0', 'max:14'],
            'priority_manual' => ['nullable', 'integer', 'min:0', 'max:5'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'source' => ['nullable', 'string', 'max:40'],
        ];
    }
}
