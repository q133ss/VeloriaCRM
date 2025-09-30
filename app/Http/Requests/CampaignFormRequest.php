<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class CampaignFormRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_ab_test')) {
            $this->merge([
                'is_ab_test' => filter_var($this->input('is_ab_test'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'template_id' => ['nullable', 'integer', 'exists:message_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'string', 'in:sms,email,telegram,whatsapp'],
            'segment' => ['required', 'string', 'in:all,new,loyal,sleeping,by_service,by_master,custom'],
            'segment_filters' => ['nullable', 'array'],
            'segment_filters.service_ids' => ['nullable', 'array'],
            'segment_filters.service_ids.*' => ['integer', 'exists:services,id'],
            'segment_filters.master_ids' => ['nullable', 'array'],
            'segment_filters.master_ids.*' => ['integer', 'exists:users,id'],
            'segment_filters.tags' => ['nullable', 'array'],
            'segment_filters.tags.*' => ['string'],
            'is_ab_test' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:50'],
            'scheduled_at' => ['nullable', 'date'],
            'subject' => ['nullable', 'string', 'max:255'],
            'content' => ['required_without:variants', 'string'],
            'test_group_size' => ['nullable', 'integer', 'min:0'],
            'variants' => ['nullable', 'array', 'max:5'],
            'variants.*.label' => ['required_with:variants', 'string', 'max:10'],
            'variants.*.subject' => ['nullable', 'string', 'max:255'],
            'variants.*.content' => ['required_with:variants', 'string'],
            'variants.*.sample_size' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $isAbTest = (bool) ($this->input('is_ab_test') ?? false);
            $variants = $this->input('variants');

            if ($isAbTest) {
                if (! is_array($variants) || count($variants) < 2) {
                    $validator->errors()->add('variants', __('marketing.validation.ab_test_variants'));
                }
            }
        });
    }
}
