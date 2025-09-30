<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ServiceFilterRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->filled('search') ? trim((string) $this->input('search')) : null,
            'price_min' => $this->filled('price_min') ? (float) $this->input('price_min') : null,
            'price_max' => $this->filled('price_max') ? (float) $this->input('price_max') : null,
            'duration_min' => $this->filled('duration_min') ? (int) $this->input('duration_min') : null,
            'duration_max' => $this->filled('duration_max') ? (int) $this->input('duration_max') : null,
            'category_id' => $this->filled('category_id') ? (int) $this->input('category_id') : null,
            'sort' => $this->filled('sort') ? (string) $this->input('sort') : null,
            'direction' => $this->filled('direction') ? strtolower((string) $this->input('direction')) : null,
        ]);
    }

    public function rules(): array
    {
        $userId = $this->user('sanctum')?->id ?? 0;

        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('service_categories', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
            'duration_min' => ['nullable', 'integer', 'min:0'],
            'duration_max' => ['nullable', 'integer', 'min:0'],
            'sort' => ['nullable', Rule::in(['name', 'base_price', 'duration_min', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => __('services.validation.filters.search.string'),
            'search.max' => __('services.validation.filters.search.max'),
            'category_id.integer' => __('services.validation.filters.category.integer'),
            'category_id.exists' => __('services.validation.filters.category.exists'),
            'price_min.numeric' => __('services.validation.filters.price.numeric'),
            'price_min.min' => __('services.validation.filters.price.min'),
            'price_max.numeric' => __('services.validation.filters.price.numeric'),
            'price_max.min' => __('services.validation.filters.price.min'),
            'duration_min.integer' => __('services.validation.filters.duration.integer'),
            'duration_min.min' => __('services.validation.filters.duration.min'),
            'duration_max.integer' => __('services.validation.filters.duration.integer'),
            'duration_max.min' => __('services.validation.filters.duration.min'),
            'sort.in' => __('services.validation.filters.sort.in'),
            'direction.in' => __('services.validation.filters.direction.in'),
        ];
    }
}
