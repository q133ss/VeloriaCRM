<?php

namespace App\Http\Requests;

class CalendarDayRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => __('calendar.validation.date_required'),
            'date.date' => __('calendar.validation.date_date'),
        ];
    }
}
