<?php

namespace App\Http\Requests;

class PromotionFilterRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('archived')) {
            $this->merge([
                'archived' => filter_var($this->input('archived'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'in:order_percent,service_percent,category_percent,free_service'],
            'archived' => ['nullable', 'boolean'],
        ];
    }
}
