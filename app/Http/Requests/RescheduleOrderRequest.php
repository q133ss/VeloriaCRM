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
            'scheduled_at' => ['required', 'date', 'before:+2 years'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.required' => 'Укажите новую дату и время.',
            'scheduled_at.date' => 'Дата указана в неверном формате.',
            'scheduled_at.before' => 'Дата записи слишком далеко: выберите день в пределах ближайших двух лет.',
        ];
    }
}
