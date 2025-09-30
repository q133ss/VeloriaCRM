<?php

namespace App\Http\Requests;

class CampaignWinnerRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer', 'exists:marketing_campaign_variants,id'],
        ];
    }
}
