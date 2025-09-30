<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PromotionFormRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('percent')) {
            $this->merge([
                'percent' => is_numeric($this->input('percent')) ? (float) $this->input('percent') : $this->input('percent'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['order_percent', 'service_percent', 'category_percent', 'free_service'])],
            'percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'service_category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'gift_description' => ['nullable', 'string', 'max:255'],
            'promo_code' => ['nullable', 'string', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('type');
            $percent = $this->input('percent');

            if (in_array($type, ['order_percent', 'service_percent', 'category_percent'], true)) {
                if (! is_numeric($percent) || $percent <= 0) {
                    $validator->errors()->add('percent', __('marketing.validation.value_required'));
                }
            }

            if ($type === 'service_percent' && ! $this->filled('service_id')) {
                $validator->errors()->add('service_id', __('validation.required', ['attribute' => 'service_id']));
            }

            if ($type === 'category_percent' && ! $this->filled('service_category_id')) {
                $validator->errors()->add('service_category_id', __('validation.required', ['attribute' => 'service_category_id']));
            }

            if ($type === 'free_service' && ! $this->filled('service_id')) {
                $validator->errors()->add('service_id', __('validation.required', ['attribute' => 'service_id']));
            }
        });
    }
}
