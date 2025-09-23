<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
