<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Validation\Rule;

class ClientFilterRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('loyalty') && $this->input('loyalty') === '') {
            $this->merge(['loyalty' => null]);
        }

        if ($this->has('search')) {
            $search = trim((string) $this->input('search'));
            $this->merge(['search' => $search !== '' ? $search : null]);
        }

        if ($this->has('sort')) {
            $sort = trim((string) $this->input('sort'));
            $this->merge(['sort' => $sort !== '' ? $sort : null]);
        }

        if ($this->has('direction')) {
            $direction = strtolower(trim((string) $this->input('direction')));
            $this->merge(['direction' => $direction !== '' ? $direction : null]);
        }
    }

    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'loyalty' => ['nullable', 'string', Rule::in(array_keys(Client::loyaltyLevels()))],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', Rule::in(['name', 'last_visit_at', 'created_at'])],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'Поисковый запрос должен быть строкой.',
            'search.max' => 'Поисковый запрос не должен превышать :max символов.',
            'loyalty.in' => 'Выбран неверный уровень лояльности.',
            'page.integer' => 'Номер страницы должен быть числом.',
            'page.min' => 'Номер страницы не может быть меньше 1.',
            'per_page.integer' => 'Количество элементов на странице должно быть числом.',
            'per_page.min' => 'Количество элементов на странице не может быть меньше 1.',
            'per_page.max' => 'Количество элементов на странице не может быть больше :max.',
            'sort.in' => 'Выбран неверный параметр сортировки.',
            'direction.in' => 'Направление сортировки задано неверно.',
        ];
    }
}
