<?php

namespace App\Http\Requests;

class QuickOrderRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        return [
            'client_phone' => ['required', 'string', 'max:20'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_phone.required' => 'Укажите телефон клиента.',
            'client_phone.string' => 'Телефон клиента должен быть строкой.',
            'client_phone.max' => 'Телефон клиента должен быть не длиннее :max символов.',
            'client_name.string' => 'Имя клиента должно быть строкой.',
            'client_name.max' => 'Имя клиента должно быть не длиннее :max символов.',
            'scheduled_at.required' => 'Укажите дату и время визита.',
            'scheduled_at.date' => 'Дата визита указана в неверном формате.',
            'note.string' => 'Комментарий должен быть текстом.',
            'note.max' => 'Комментарий не должен превышать :max символов.',
        ];
    }
}
