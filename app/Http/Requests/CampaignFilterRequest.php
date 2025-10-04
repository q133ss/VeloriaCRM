<?php

namespace App\Http\Requests;

class CampaignFilterRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string'],
            'channel' => ['nullable', 'string', 'in:sms,email,whatsapp'],
            'segment' => ['nullable', 'string', 'in:all,new,loyal,sleeping,by_service,by_master,custom,selected'],
        ];
    }
}
