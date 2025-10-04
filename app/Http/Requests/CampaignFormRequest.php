<?php

namespace App\Http\Requests;

use App\Models\Setting;
use App\Services\Marketing\MarketingCampaignService;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
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

        if ($this->has('variants') && is_array($this->input('variants'))) {
            $variants = array_values($this->input('variants'));

            foreach ($variants as $index => &$variant) {
                if (! isset($variant['label']) || $variant['label'] === '') {
                    $variant['label'] = chr(65 + $index);
                }
            }

            $this->merge(['variants' => $variants]);
        }
    }

    public function rules(): array
    {
        $availableChannels = $this->availableChannelValues();

        return [
            'template_id' => ['nullable', 'integer', 'exists:message_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'channel' => array_merge(
                ['required', 'string'],
                $this->channelValidationRules($availableChannels)
            ),
            'segment' => ['required', 'string', 'in:all,new,loyal,sleeping,by_service,by_master,custom,selected'],
            'segment_filters' => ['nullable', 'array'],
            'segment_filters.service_ids' => ['nullable', 'array'],
            'segment_filters.service_ids.*' => ['integer', 'exists:services,id'],
            'segment_filters.master_ids' => ['nullable', 'array'],
            'segment_filters.master_ids.*' => ['integer', 'exists:users,id'],
            'segment_filters.tags' => ['nullable', 'array'],
            'segment_filters.tags.*' => ['string'],
            'segment_filters.client_ids' => ['nullable', 'array'],
            'segment_filters.client_ids.*' => ['integer', 'exists:clients,id'],
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

            if ($this->input('segment') === 'selected') {
                $clientIds = Arr::get($this->input('segment_filters', []), 'client_ids', []);

                if (! is_array($clientIds) || empty(array_filter($clientIds))) {
                    $validator->errors()->add('segment_filters.client_ids', __('marketing.validation.selected_clients_required'));
                }
            }
        });
    }

    protected function availableChannelValues(): array
    {
        $user = $this->user('sanctum') ?? $this->user();
        if (! $user) {
            return [];
        }

        $settings = Setting::where('user_id', $user->id)->first();

        return collect(app(MarketingCampaignService::class)->availableChannels($settings))
            ->pluck('value')
            ->all();
    }

    protected function channelValidationRules(array $availableChannels): array
    {
        if (! empty($availableChannels)) {
            return [Rule::in($availableChannels)];
        }

        return [
            function (string $attribute, $value, $fail) {
                $fail(__('marketing.campaigns.no_channels_available'));
            },
        ];
    }
}
