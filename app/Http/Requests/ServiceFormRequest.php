<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Validation\Rule;

class ServiceFormRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $upsell = $this->input('upsell_suggestions');

        if (is_string($upsell)) {
            $upsell = collect(preg_split('/\r\n|\r|\n/', $upsell))
                ->map(fn ($value) => trim($value ?? ''))
                ->filter()
                ->values()
                ->all();
        }

        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'category_id' => $this->filled('category_id') ? (int) $this->input('category_id') : null,
            'base_price' => $this->filled('base_price') ? (float) $this->input('base_price') : null,
            'cost' => $this->filled('cost') ? (float) $this->input('cost') : null,
            'duration_min' => $this->filled('duration_min') ? (int) $this->input('duration_min') : null,
            'upsell_suggestions' => is_array($upsell) ? array_values(array_filter(array_map('trim', $upsell))) : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user('sanctum') !== null;
    }

    public function rules(): array
    {
        $userId = $this->user('sanctum')?->id ?? 0;
        $service = $this->route('service');
        $serviceId = $service instanceof Service ? $service->id : (is_numeric($service) ? (int) $service : 0);

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'name')->where(fn ($query) => $query->where('user_id', $userId))->ignore($serviceId),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('service_categories', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'base_price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'duration_min' => ['required', 'integer', 'min:5', 'max:1440'],
            'upsell_suggestions' => ['nullable', 'array', 'max:10'],
            'upsell_suggestions.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('services.validation.form.name.required'),
            'name.string' => __('services.validation.form.name.string'),
            'name.max' => __('services.validation.form.name.max'),
            'name.unique' => __('services.validation.form.name.unique'),
            'category_id.integer' => __('services.validation.form.category.integer'),
            'category_id.exists' => __('services.validation.form.category.exists'),
            'base_price.required' => __('services.validation.form.base_price.required'),
            'base_price.numeric' => __('services.validation.form.base_price.numeric'),
            'base_price.min' => __('services.validation.form.base_price.min'),
            'base_price.max' => __('services.validation.form.base_price.max'),
            'cost.numeric' => __('services.validation.form.cost.numeric'),
            'cost.min' => __('services.validation.form.cost.min'),
            'cost.max' => __('services.validation.form.cost.max'),
            'duration_min.required' => __('services.validation.form.duration.required'),
            'duration_min.integer' => __('services.validation.form.duration.integer'),
            'duration_min.min' => __('services.validation.form.duration.min'),
            'duration_min.max' => __('services.validation.form.duration.max'),
            'upsell_suggestions.array' => __('services.validation.form.upsell.array'),
            'upsell_suggestions.max' => __('services.validation.form.upsell.max'),
            'upsell_suggestions.*.string' => __('services.validation.form.upsell.string'),
            'upsell_suggestions.*.max' => __('services.validation.form.upsell.item_max'),
        ];
    }
}
