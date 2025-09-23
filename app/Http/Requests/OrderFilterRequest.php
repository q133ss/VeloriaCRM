<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Validation\Rule;

class OrderFilterRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        $statusKeys = array_keys(Order::statusLabels());
        $periodKeys = array_keys(Order::periodOptions());

        return [
            'period' => ['nullable', Rule::in($periodKeys)],
            'status' => ['nullable', Rule::in(array_merge(['all'], $statusKeys))],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.in' => 'Выбран неверный период.',
            'status.in' => 'Выбран неверный статус.',
            'search.string' => 'Поисковый запрос должен быть строкой.',
            'search.max' => 'Поисковый запрос не должен превышать :max символов.',
            'page.integer' => 'Страница должна быть числом.',
            'page.min' => 'Страница должна быть не меньше 1.',
            'per_page.integer' => 'Количество записей должно быть числом.',
            'per_page.min' => 'Количество записей не может быть меньше 1.',
            'per_page.max' => 'Количество записей не может быть больше :max.',
        ];
    }
}

