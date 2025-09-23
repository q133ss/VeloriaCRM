<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Validation\Rule;

class OrderFormRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        $statusKeys = array_keys(Order::statusLabels());
        $userId = $this->user('sanctum')?->id ?? 0;

        return [
            'client_phone' => ['required', 'string', 'max:20'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'scheduled_at' => ['required', 'date'],
            'services' => ['nullable', 'array'],
            'services.*' => [
                'integer',
                Rule::exists('services', 'id')->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                }),
            ],
            'note' => ['nullable', 'string'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in($statusKeys)],
            'source' => ['nullable', 'string', 'max:50'],
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
            'client_email.email' => 'Укажите корректный email клиента.',
            'client_email.max' => 'Email клиента должен быть не длиннее :max символов.',
            'scheduled_at.required' => 'Укажите дату и время записи.',
            'scheduled_at.date' => 'Дата записи указана в неверном формате.',
            'services.array' => 'Список услуг должен быть массивом.',
            'services.*.integer' => 'Выберите корректную услугу.',
            'services.*.exists' => 'Некоторые услуги не найдены или недоступны.',
            'note.string' => 'Комментарий должен быть текстом.',
            'total_price.numeric' => 'Сумма должна быть числом.',
            'total_price.min' => 'Сумма не может быть отрицательной.',
            'status.required' => 'Выберите статус записи.',
            'status.in' => 'Выбран недопустимый статус.',
            'source.string' => 'Источник должен быть строкой.',
            'source.max' => 'Источник не должен превышать :max символов.',
        ];
    }
}
