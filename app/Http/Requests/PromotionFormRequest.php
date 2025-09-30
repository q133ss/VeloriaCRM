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
        if ($this->has('value')) {
            $this->merge([
                'value' => is_numeric($this->input('value')) ? (float) $this->input('value') : $this->input('value'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['percentage', 'fixed', 'gift', 'bogo', 'loyalty'])],
            'value' => ['nullable', 'numeric', 'min:0'],
            'gift_description' => ['nullable', 'string', 'max:255'],
            'promo_code' => ['nullable', 'string', 'max:100'],
            'audience' => ['required', 'string', Rule::in(['all', 'new', 'loyal', 'custom'])],
            'conditions' => ['nullable', 'array'],
            'conditions.service_ids' => ['nullable', 'array'],
            'conditions.service_ids.*' => ['integer', 'exists:services,id'],
            'conditions.product_ids' => ['nullable', 'array'],
            'conditions.product_ids.*' => ['integer'],
            'conditions.client_tags' => ['nullable', 'array'],
            'conditions.client_tags.*' => ['string'],
            'conditions.minimum_orders' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'scheduled', 'active', 'archived'])],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('type');
            $value = $this->input('value');

            if (in_array($type, ['percentage', 'fixed', 'loyalty'], true) && ! is_numeric($value)) {
                $validator->errors()->add('value', __('marketing.validation.value_required'));
            }

            if ($type === 'percentage' && is_numeric($value) && ($value <= 0 || $value > 100)) {
                $validator->errors()->add('value', __('marketing.validation.percentage_range'));
            }

            if ($type === 'gift' && ! $this->filled('gift_description')) {
                $validator->errors()->add('gift_description', __('marketing.validation.gift_required'));
            }
        });
    }
}
