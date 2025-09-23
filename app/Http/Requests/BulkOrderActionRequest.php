<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class BulkOrderActionRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        $userId = $this->user('sanctum')?->id ?? 0;

        return [
            'orders' => ['required', 'array'],
            'orders.*' => [
                'integer',
                Rule::exists('orders', 'id')->where(function ($query) use ($userId) {
                    $query->where('master_id', $userId);
                }),
            ],
            'action' => ['required', Rule::in(['confirm', 'remind', 'cancel'])],
        ];
    }

    public function messages(): array
    {
        return [
            'orders.required' => 'Выберите хотя бы одну запись.',
            'orders.array' => 'Некорректный формат выбранных записей.',
            'orders.*.integer' => 'Неверный идентификатор записи.',
            'orders.*.exists' => 'Некоторые записи недоступны или уже удалены.',
            'action.required' => 'Выберите действие.',
            'action.in' => 'Выбранное действие недоступно.',
        ];
    }
}
