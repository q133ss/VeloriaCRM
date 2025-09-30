<?php

namespace App\Http\Requests;

class CalendarEventRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        return [
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ];
    }

    public function messages(): array
    {
        return [
            'start.required' => __('calendar.validation.start_required'),
            'start.date' => __('calendar.validation.start_date'),
            'end.required' => __('calendar.validation.end_required'),
            'end.date' => __('calendar.validation.end_date'),
            'end.after_or_equal' => __('calendar.validation.end_after'),
        ];
    }
}
