<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LandingStoreRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $settings = $this->input('settings', []);

        if (is_array($settings)) {
            if (array_key_exists('show_all_services', $settings)) {
                $settings['show_all_services'] = filter_var($settings['show_all_services'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }

            if (array_key_exists('discount_percent', $settings) && $settings['discount_percent'] !== null) {
                $settings['discount_percent'] = is_numeric($settings['discount_percent'])
                    ? (float) $settings['discount_percent']
                    : $settings['discount_percent'];
            }
        }

        $merge = ['settings' => $settings];

        if ($this->has('is_active')) {
            $merge['is_active'] = filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['general', 'promotion', 'service'])],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:landings,slug'],
            'landing' => ['nullable', 'string', 'max:255'],
            'settings' => ['required', 'array'],
            'settings.primary_color' => ['required', 'string', 'max:50'],
            'settings.background_type' => ['required', 'string', Rule::in(['preset', 'upload'])],
            'settings.background_value' => ['nullable', 'string', 'max:255'],
            'settings.greeting' => ['nullable', 'string'],
            'settings.show_all_services' => ['nullable', 'boolean'],
            'settings.service_ids' => ['nullable', 'array'],
            'settings.service_ids.*' => ['integer', 'exists:services,id'],
            'settings.service_names' => ['nullable', 'array'],
            'settings.service_names.*' => ['nullable', 'string', 'max:255'],
            'settings.promotion_id' => ['nullable', 'integer', 'exists:promotions,id'],
            'settings.promotion_name' => ['nullable', 'string', 'max:255'],
            'settings.headline' => ['nullable', 'string', 'max:255'],
            'settings.description' => ['nullable', 'string'],
            'settings.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'settings.promo_code' => ['nullable', 'string', 'max:100'],
            'settings.ends_at' => ['nullable', 'date'],
            'settings.service_id' => ['nullable', 'integer', 'exists:services,id'],
            'settings.service_name' => ['nullable', 'string', 'max:255'],
            'settings.service_description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('type');
            $settings = $this->input('settings', []);

            if ($type === 'general') {
                $showAll = filter_var(data_get($settings, 'show_all_services'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
                $serviceIds = data_get($settings, 'service_ids', []);

                if (! $showAll && empty($serviceIds)) {
                    $validator->errors()->add('settings.service_ids', __('landings.validation.service_ids_required'));
                }
            }

            if ($type === 'promotion') {
                foreach ([
                    'promotion_id' => 'promotion_required',
                    'headline' => 'headline_required',
                    'description' => 'description_required',
                    'discount_percent' => 'discount_required',
                    'promo_code' => 'promo_code_required',
                    'ends_at' => 'ends_at_required',
                ] as $field => $messageKey) {
                    if (! filled(data_get($settings, $field))) {
                        $validator->errors()->add('settings.' . $field, __('landings.validation.' . $messageKey));
                    }
                }
            }

            if ($type === 'service') {
                if (! filled(data_get($settings, 'service_id'))) {
                    $validator->errors()->add('settings.service_id', __('landings.validation.service_id_required'));
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'title.required' => __('landings.validation.title_required'),
            'title.string' => __('landings.validation.title_string'),
            'title.max' => __('landings.validation.title_max'),
            'type.required' => __('landings.validation.type_required'),
            'type.in' => __('landings.validation.type_invalid'),
            'slug.regex' => __('landings.validation.slug_invalid'),
            'slug.unique' => __('landings.validation.slug_unique'),
            'settings.required' => __('landings.validation.settings_required'),
            'settings.array' => __('landings.validation.settings_array'),
            'settings.primary_color.required' => __('landings.validation.primary_color_required'),
            'settings.background_type.required' => __('landings.validation.background_type_required'),
            'settings.background_type.in' => __('landings.validation.background_type_invalid'),
            'settings.background_value.max' => __('landings.validation.background_value_max'),
            'settings.service_ids.array' => __('landings.validation.service_ids_array'),
            'settings.service_ids.*.exists' => __('landings.validation.service_ids_exists'),
            'settings.promotion_id.exists' => __('landings.validation.promotion_exists'),
            'settings.headline.max' => __('landings.validation.headline_max'),
            'settings.discount_percent.numeric' => __('landings.validation.discount_numeric'),
            'settings.discount_percent.min' => __('landings.validation.discount_min'),
            'settings.discount_percent.max' => __('landings.validation.discount_max'),
            'settings.promo_code.max' => __('landings.validation.promo_code_max'),
            'settings.ends_at.date' => __('landings.validation.ends_at_date'),
            'settings.service_id.exists' => __('landings.validation.service_exists'),
        ];
    }
}
