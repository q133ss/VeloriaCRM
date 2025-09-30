<?php

namespace App\Http\Requests;

use App\Models\ServiceCategory;
use Illuminate\Validation\Rule;

class ServiceCategoryFormRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        $userId = $this->user('sanctum')?->id ?? 0;
        $category = $this->route('category');
        $categoryId = $category instanceof ServiceCategory ? $category->id : (is_numeric($category) ? (int) $category : 0);

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('service_categories', 'name')->where(fn ($query) => $query->where('user_id', $userId))->ignore($categoryId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('services.validation.category.name.required'),
            'name.string' => __('services.validation.category.name.string'),
            'name.max' => __('services.validation.category.name.max'),
            'name.unique' => __('services.validation.category.name.unique'),
        ];
    }
}
