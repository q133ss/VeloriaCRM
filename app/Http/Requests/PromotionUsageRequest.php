<?php

namespace App\Http\Requests;

class PromotionUsageRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('revenue')) {
            $this->merge([
                'revenue' => is_numeric($this->input('revenue')) ? (float) $this->input('revenue') : $this->input('revenue'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'revenue' => ['nullable', 'numeric'],
            'used_at' => ['required', 'date'],
            'context' => ['nullable', 'array'],
        ];
    }
}
