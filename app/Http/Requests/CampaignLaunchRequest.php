<?php

namespace App\Http\Requests;

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
}
