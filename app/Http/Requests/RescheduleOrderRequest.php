<?php

namespace App\Http\Requests;

class RescheduleOrderRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.required' => 'Укажите новую дату и время.',
            'scheduled_at.date' => 'Дата указана в неверном формате.',
        ];
    }
}
