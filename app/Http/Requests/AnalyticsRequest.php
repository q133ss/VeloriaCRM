<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('grouping')) {
            $this->merge([
                'grouping' => strtolower((string) $this->input('grouping')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'compare_from' => ['nullable', 'date'],
            'compare_to' => ['nullable', 'date'],
            'grouping' => ['nullable', Rule::in(['day', 'week', 'month'])],
        ];
    }
}
