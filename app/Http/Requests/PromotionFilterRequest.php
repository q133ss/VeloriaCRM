<?php

namespace App\Http\Requests;

class PromotionFilterRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:draft,active,scheduled,archived,expired'],
            'type' => ['nullable', 'string', 'in:percentage,fixed,gift,bogo,loyalty'],
            'audience' => ['nullable', 'string', 'in:all,new,loyal,custom'],
        ];
    }
}
