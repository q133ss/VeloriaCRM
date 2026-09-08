<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Validation\Rule;

class ServiceFormRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        // The column is documented as «ID сопутствующих услуг» and the seeder
        // fills it with ids, but the rules used to accept any string, so free
        // text could land in a list of foreign keys. Ids it is.
        $upsell = $this->input('upsell_suggestions');

        if (is_string($upsell)) {
            $upsell = preg_split('/[\s,]+/', $upsell) ?: [];
        }

        // Anything that is not a number is left as it came, so the rules can
        // say so out loud instead of quietly dropping what the caller sent.
        $upsell = is_array($upsell)
            ? collect($upsell)
                ->reject(fn ($value) => $value === null || $value === '')
                ->map(fn ($value) => is_numeric($value) ? (int) $value : $value)
                ->unique()
                ->values()
                ->all()
            : null;

        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'category_id' => $this->filled('category_id') ? (int) $this->input('category_id') : null,
            'base_price' => $this->filled('base_price') ? $this->normalizeNumeric($this->input('base_price')) : null,
            'cost' => $this->filled('cost') ? $this->normalizeNumeric($this->input('cost')) : null,
            'duration_min' => $this->filled('duration_min') ? (int) $this->input('duration_min') : null,
            'upsell_suggestions' => $upsell,
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
            'upsell_suggestions.*' => [
                'integer',
                Rule::exists('services', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
                Rule::notIn([$serviceId]),
            ],
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
            'upsell_suggestions.*.integer' => __('services.validation.form.upsell.item'),
            'upsell_suggestions.*.exists' => __('services.validation.form.upsell.item'),
            'upsell_suggestions.*.not_in' => __('services.validation.form.upsell.self'),
        ];
    }
}
