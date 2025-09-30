<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class CampaignLaunchRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['nullable', 'date'],
            'mode' => ['nullable', 'string', 'in:immediate,schedule,test'],
            'test_group_size' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('mode') === 'test' && ! $this->filled('test_group_size')) {
                $validator->errors()->add('test_group_size', __('marketing.campaigns.test_group_missing'));
            }
        });
    }
}
